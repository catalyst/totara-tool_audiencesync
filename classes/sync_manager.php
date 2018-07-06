<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Sync functionality. Most of the code was taken from core functionality.
 *
 * @package    tool_audiencesync
 * @copyright  2018 Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_audiencesync;

use context_system;
use null_progress_trace;
use cohort;

defined('MOODLE_INTERNAL') || die;

abstract class sync_manager {

    /**
     * Run audience sync for a single user.
     *
     * 1. Add to required audeinces.
     * 2. Apply enrolment based on audeince rules.
     * 3. Apply program and certification assignments.
     *
     * @param int $userid User ID.
     */
    public static function syn_user($userid) {
        global $DB;

        $user = $DB->get_record('user', array('id' => $userid));

        if (!empty($user)) {
            self::apply_cohort_memberships($user->id);
            self::apply_cohort_enrolments($user->id);
            self::apply_cohort_program_assignments($user->id);
        }
    }

    /**
     * Queue sync_user_adhoc_task.
     *
     * @param int $userid User ID.
     *
     * @return boolean - True if the config was saved.
     */
    public static function queue_sync_user_adhoc_task($userid) {
        $adhocktask = new sync_user_adhoc_task();
        $adhocktask->set_custom_data($userid);
        $adhocktask->set_component('local_alh');

        return \core\task\manager::queue_adhoc_task($adhocktask);
    }

    /**
     * Apply user's cohort membership rules.
     *
     * @param int $userid User ID.
     */
    public static function apply_cohort_memberships($userid) {
        global $DB;

        $cohorts = $DB->get_records('cohort', array('cohorttype' => cohort::TYPE_DYNAMIC));
        $trace = new null_progress_trace();
        $cohortbrokenrules = totara_cohort_broken_rules(null, null, $trace);

        foreach ($cohorts as $cohort) {
            try {
                if (array_key_exists($cohort->id, $cohortbrokenrules)) {
                    continue;
                }

                if (totara_cohort_is_active($cohort)) {
                    totara_cohort_update_dynamic_cohort_members($cohort->id, $userid, true, true);
                }
            } catch (\Exception $e) {
                $eventdata = array(
                    'context' => context_system::instance(),
                    'userid' => $userid,
                    'other' => array(
                        'error' => $e->getMessage(),
                        'cohortid' => $cohort->id,
                    ),
                );

                $event = event\apply_cohort_memberships_failed::create($eventdata);
                $event->trigger();
            }
        }
    }

    /**
     * Apply enrolment based on cohort memberships.
     *
     * @param int $userid User ID.
     *
     * @return bool
     */
    public static function apply_cohort_enrolments($userid) {
        global $DB;

        if (!enrol_is_enabled('cohort')) {
            return true;
        }

        $sql = " SELECT e.*, r.id as roleexists
                   FROM {enrol} e
              LEFT JOIN {role} r
                     ON r.id = e.roleid
             INNER JOIN {cohort_members} cm
                     ON e.customint1 = cm.cohortid
                  WHERE e.enrol = 'cohort'
                    AND cm.userid = :uid";

        $instances = $DB->get_records_sql($sql, array('uid' => $userid));

        $plugin = enrol_get_plugin('cohort');
        foreach ($instances as $instance) {
            try {
                if ($instance->status != ENROL_INSTANCE_ENABLED ) {
                    // No roles for disabled instances.
                    $instance->roleid = 0;
                } else if ($instance->roleid and !$instance->roleexists) {
                    // Invalid role - let's just enrol, they will have to create new sync and delete this one.
                    $instance->roleid = 0;
                }
                unset($instance->roleexists);
                // No problem if already enrolled.
                $plugin->enrol_user($instance, $userid, $instance->roleid, 0, 0, ENROL_USER_ACTIVE);

                // Sync groups.
                if ($instance->customint2) {
                    if (!groups_is_member($instance->customint2, $userid)) {
                        $group = $DB->get_record('groups', array('id' => $instance->customint2, 'courseid' => $instance->courseid));
                        if ($group) {
                            groups_add_member($group->id, $userid, 'enrol_cohort', $instance->id);
                        }
                    }
                }
            } catch (\Exception $e) {
                $eventdata = array(
                    'context' => context_system::instance(),
                    'userid' => $userid,
                    'other' => array(
                        'error' => $e->getMessage(),
                        'courseid' => $instance->courseid,
                    ),
                );

                $event = event\apply_cohort_enrolments_failed::create($eventdata);
                $event->trigger();
            }
        }

        return true;
    }

    /**
     * Apply any program/certification assignments for the user based on audience rules.
     *
     * @param int $userid User ID.
     */
    public static function apply_cohort_program_assignments($userid) {
        global $DB;

        $now = time();

        // Now check for audience assignments.
        $audsql = 'SELECT pa.*
                     FROM {prog_assignment} pa
                LEFT JOIN {prog_user_assignment} pua
                       ON pua.assignmentid = pa.id
                      AND pua.userid = :uid
                    WHERE pa.assignmenttype = ' . ASSIGNTYPE_COHORT . '
                      AND pua.id IS NULL
                      AND EXISTS ( SELECT 1
                                     FROM {cohort_members} cm
                                    WHERE cm.cohortid = pa.assignmenttypeid
                                      AND cm.userid = :cuid
                                  )';
        $audparams = array('uid' => $userid, 'cuid' => $userid);

        if ($progassignments = $DB->get_records_sql($audsql, $audparams)) {
            $programs = array();
            foreach ($progassignments as $progassign) {
                try {
                    $assigndata = array();

                    if (empty($programs[$progassign->programid])) {
                        $program = new \program($progassign->programid);
                        $programs[$program->id] = $program;
                        $assigndata['needscompletionrecord'] = true;
                    } else {
                        $program = $programs[$progassign->programid];
                        $assigndata['needscompletionrecord'] = false;
                    }
                    $context = \context_program::instance($program->id);

                    // Check the program is available before creating any assignments.
                    if ((empty($program->availablefrom) || $program->availablefrom < $now) &&
                        (empty($program->availableuntil) || $program->availableuntil > $now)) {

                        // Calculate the timedue for the program assignment.
                        $assigndata['timedue'] = $program->make_timedue($userid, $progassign, false);

                        // Check for exceptions, we can assume there aren't any dismissed ones at this point.
                        if ($program->update_exceptions($userid, $progassign, $assigndata['timedue'])) {
                            $assigndata['exceptions'] = PROGRAM_EXCEPTION_RAISED;
                        } else {
                            $assigndata['exceptions'] = PROGRAM_EXCEPTION_NONE;
                        }

                        // Assign the user.
                        $program->assign_learners_bulk(array($userid => $assigndata), $progassign);
                        if (!empty($program->certifid)) {
                            // Should be happening on a program_assigned event handler,
                            // but we need to do this to make sure that it happens before the completion update.
                            // There shouldn't be any issues calling it twice, since just returns straight away if the record exists.
                            certif_create_completion($program->id, $userid);
                        }

                        // Create future assignment records, user_confirmation happens before login_completion so this should
                        // be caught by the login event and run through the regular code.
                        if ($progassign->completionevent == COMPLETION_EVENT_FIRST_LOGIN && $assigndata['timedue'] === false) {
                            $program->create_future_assignments_bulk($program->id, array($userid), $progassign->id);

                            $eventdata = array('objectid' => $program->id, 'context' => $context, 'userid' => $userid);
                            $event = \totara_program\event\program_future_assigned::create($eventdata);
                            $event->trigger();
                        }

                        // Finally trigger a program assignment event.
                        $eventdata = array('objectid' => $program->id, 'context' => $context, 'userid' => $userid);
                        $event = \totara_program\event\program_assigned::create($eventdata);
                        $event->trigger();

                        // For each program (not assignment) update the user completion.
                        if ($assigndata['needscompletionrecord']) {
                            // It is unlikely they have any progress at this point but it creates the courseset records.
                            prog_update_completion($userid, $program);
                        }
                    }
                } catch (\Exception $e) {
                    $eventdata = array(
                        'context' => context_system::instance(),
                        'userid' => $userid,
                        'other' => array(
                            'error' => $e->getMessage(),
                            'programid' => $progassign->programid,
                        ),
                    );

                    $event = event\apply_program_assignments_failed::create($eventdata);
                    $event->trigger();
                }
            }
        }

    }

}

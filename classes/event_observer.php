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
 * Event observers.
 *
 * @package    tool_audiencesync
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_audiencesync;

defined('MOODLE_INTERNAL') || die();

/**
 * Class event_observer for all related event observers.
 *
 * @package tool_audiencesync
 */
class event_observer {

    /**
     * Triggered via user_created event.
     *
     * @param \core\event\user_created $event
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function user_created(\core\event\user_created $event) {
        if (self::should_run_sync_user($event->objectid)) {
            if (!empty(get_config('tool_audiencesync', 'sync_via_adhoc'))) {
                sync_manager::queue_sync_user_adhoc_task($event->objectid);
            } else {
                sync_manager::sync_user($event->objectid);
            }
        }
    }

    /**
     * Check if we should run a sync for a user.
     *
     * @param int $userid User ID.
     *
     * @return bool
     * @throws \dml_exception
     */
    public static function should_run_sync_user($userid) {
        if (empty(get_config('tool_audiencesync', 'enabled'))) {
            return false;
        }

        if (empty(get_config('tool_audiencesync', 'sync_during_hrsync')) && self::is_user_created_by_hr_sync()) {
            return false;
        }

        return true;
    }

    /**
     * Check if a user was created via HR sync.
     *
     * @return bool
     */
    public static function is_user_created_by_hr_sync() {
        $backtrace = debug_backtrace();
        foreach ($backtrace as $bt) {
            if (isset($bt['object']) and is_object($bt['object'])) {
                if ($bt['object'] instanceof \totara_sync_element_user) {
                    if ($bt['function'] == 'create_user') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

}

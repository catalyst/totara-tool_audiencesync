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
 * Lang strings.
 *
 * @package    tool_audiencesync
 * @copyright  2018 Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'Extended audience sync';
$string['apply_program_assignments_failed'] = 'Apply program assignments failed';
$string['apply_cohort_memberships_failed'] = 'Apply cohort memberships failed';
$string['apply_cohort_enrolments_failed'] = 'Apply cohort enrolments failed';
$string['settings_enabled'] = 'Enable sync on user creation';
$string['settings_enabled_desc'] = 'Enable or disable audience sync after a new user is created.';
$string['settings_sync_via_adhoc'] = 'Process sync via adhoc task';
$string['settings_sync_via_adhoc_desc'] = 'Instead of running a sync at runtime, a new adhoc task will be generated to sync a new user.';
$string['settings_sync_during_hrsync'] = 'Sync during HR sync';
$string['settings_sync_during_hrsync_desc'] = 'If enabled, audience sync will be run for new users during HR sync. This will increase HR sync time.';

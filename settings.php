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
 * Settings.
 *
 * @package    tool_audiencesync
 * @copyright  2018 Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (is_siteadmin()) {
    $settings = new admin_settingpage('tool_audiencesync', get_string('pluginname', 'tool_audiencesync'));
    $ADMIN->add('tools', $settings);

    $name = 'tool_audiencesync/enabled';
    $title = get_string('settings_enabled', 'tool_audiencesync');
    $description = get_string('settings_enabled_desc', 'tool_audiencesync');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $settings->add($setting);

    $name = 'tool_audiencesync/sync_via_adhoc';
    $title = get_string('settings_sync_via_adhoc', 'tool_audiencesync');
    $description = get_string('settings_sync_via_adhoc_desc', 'tool_audiencesync');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $settings->add($setting);

    $name = 'tool_audiencesync/sync_during_hrsync';
    $title = get_string('settings_sync_during_hrsync', 'tool_audiencesync');
    $description = get_string('settings_sync_during_hrsync_desc', 'tool_audiencesync');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $settings->add($setting);
}

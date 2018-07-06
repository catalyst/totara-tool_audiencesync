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
 * ALH Customisations Local plugin. Ad hoc task to apply learning rules.
 *
 * @package    tool_audiencesync
 * @copyright  2018 Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_audiencesync;

defined('MOODLE_INTERNAL') || die();

/**
 * Ad hoc task to sync a user.
 * @package tool_audiencesync
 */
class sync_user_adhoc_task extends \core\task\adhoc_task {

    /**
     * Sync a user.
     */
    public function execute() {
        sync_manager::syn_user($this->get_custom_data());
    }

}

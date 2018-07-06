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
 * Event triggered when program assignments failed.
 *
 * @package    tool_audiencesync
 * @copyright  2018 Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_audiencesync\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Class apply_program_assignments_failed
 * @package tool_audiencesync
 */
class apply_program_assignments_failed extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return get_string('apply_program_assignments_failed', 'tool_audiencesync');
    }

    public function get_description() {
        return "Failed assign user '{$this->userid}' to program '{$this->other['programid']}': '{$this->other['error']}'";
    }

}

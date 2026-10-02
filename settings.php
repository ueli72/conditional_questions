<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Admin settings for the conditional questions plugin.
 *
 * @package    local_conditional_questions
 * @copyright  2026 Ueli Leutwyler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_conditional_questions',
        get_string('pluginname', 'local_conditional_questions')
    );
    if ($ADMIN->fulltree) {
        $message = \local_conditional_questions\compatibility::question_action_hook_available() &&
            \local_conditional_questions\compatibility::filter_hook_available() &&
            \local_conditional_questions\compatibility::attempt_maxmark_available()
            ? get_string('coreok', 'local_conditional_questions')
            : get_string('corenotpatched', 'local_conditional_questions');
        $settings->add(new admin_setting_heading(
            'local_conditional_questions/core_status',
            get_string('corestatus', 'local_conditional_questions'),
            $message
        ));
    }
    $ADMIN->add('localplugins', $settings);
}

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

namespace local_conditional_questions;

/**
 * Checks the optional Moodle core extension points used by this plugin.
 *
 * @package    local_conditional_questions
 * @copyright  2026 Ueli Leutwyler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class compatibility {
    /**
     * Whether the quiz_attempts.maxmark column added by the core patch exists.
     */
    public static function attempt_maxmark_available(): bool {
        global $DB;
        return $DB->get_manager()->field_exists(
            new \xmldb_table('quiz_attempts'),
            new \xmldb_field('maxmark'),
        );
    }

    /**
     * Check the deliberately small public contract used by the plugin.
     *
     * @param string $classname hook class name
     * @return bool
     */
    public static function supports(string $classname): bool {
        if (!class_exists($classname)) {
            return false;
        }
        if ($classname === '\\mod_quiz\\hook\\edit_menu_actions') {
            return method_exists($classname, 'add_action') && method_exists($classname, 'get_structure');
        }
        if ($classname === '\\mod_quiz\\hook\\edit_question_actions') {
            return method_exists($classname, 'add_action') && method_exists($classname, 'get_structure') &&
                method_exists($classname, 'get_slot');
        }
        if ($classname === '\\mod_quiz\\hook\\before_attempt_questions_created') {
            return method_exists($classname, 'get_quiz') && method_exists($classname, 'get_userid') &&
                method_exists($classname, 'remove_question');
        }
        if ($classname === '\\mod_quiz\\hook\\before_attempt_saved') {
            return method_exists($classname, 'get_quiz') && method_exists($classname, 'get_question_usage') &&
                method_exists($classname, 'get_attempt');
        }
        if ($classname === '\\mod_quiz\\hook\\before_attempt_grade_rescaled') {
            return method_exists($classname, 'get_attempt') && method_exists($classname, 'set_maxmark');
        }
        if ($classname === '\\mod_quiz\\hook\\before_final_grade_calculated') {
            return method_exists($classname, 'get_quiz') && method_exists($classname, 'get_attempts') &&
                method_exists($classname, 'set_attempts');
        }
        if ($classname === '\\mod_quiz\\hook\\after_all_final_grades_recomputed') {
            return method_exists($classname, 'get_quiz');
        }
        return false;
    }

    /**
     * Whether the quiz editing question-actions hook is available.
     */
    public static function question_action_hook_available(): bool {
        return self::supports('\\mod_quiz\\hook\\edit_question_actions');
    }

    /**
     * Whether the before_attempt_questions_created hook is available.
     */
    public static function filter_hook_available(): bool {
        return self::supports('\\mod_quiz\\hook\\before_attempt_questions_created');
    }
}

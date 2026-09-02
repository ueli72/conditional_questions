<?php
namespace local_conditional_questions;

defined('MOODLE_INTERNAL') || die();

/** Checks the optional Moodle core extension points used by this plugin. */
final class compatibility {
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

    public static function question_action_hook_available(): bool {
        return self::supports('\\mod_quiz\\hook\\edit_question_actions');
    }

    public static function filter_hook_available(): bool {
        return self::supports('\\mod_quiz\\hook\\before_attempt_questions_created');
    }
}

<?php
namespace local_conditional_questions;

defined('MOODLE_INTERNAL') || die();

/** Hook callbacks for local_conditional_questions. */
final class hook_callbacks {
    public static function fix_bulk_final_grades(\mod_quiz\hook\after_all_final_grades_recomputed $hook): void {
        global $DB;

        $calculator = $hook->get_quiz()->get_grade_calculator();
        $useridlist = $DB->get_fieldset_select('quiz_attempts', 'DISTINCT userid',
            'quiz = :quizid AND state = :state AND preview = 0', [
                'quizid' => $hook->get_quiz()->get_quizid(),
                'state' => \mod_quiz\quiz_attempt::FINISHED,
            ]);
        foreach ($useridlist as $userid) {
            $calculator->recompute_final_grade((int) $userid);
        }
    }

    public static function normalise_final_grade_attempts(\mod_quiz\hook\before_final_grade_calculated $hook): void {
        $quiz = $hook->get_quiz()->get_quiz();
        $attempts = [];
        foreach ($hook->get_attempts() as $attempt) {
            if (!empty($attempt->maxmark) && $attempt->maxmark > 0 && $quiz->sumgrades > 0) {
                $attempt = clone $attempt;
                $attempt->sumgrades = $attempt->sumgrades * $quiz->sumgrades / $attempt->maxmark;
            }
            $attempts[] = $attempt;
        }
        $hook->set_attempts($attempts);
    }

    public static function set_rescaling_maxmark(\mod_quiz\hook\before_attempt_grade_rescaled $hook): void {
        $attempt = $hook->get_attempt();
        if ($attempt && !empty($attempt->maxmark)) {
            $hook->set_maxmark((float) $attempt->maxmark);
        }
    }

    public static function set_attempt_maxmark(\mod_quiz\hook\before_attempt_saved $hook): void {
        $maxmark = 0.0;
        foreach ($hook->get_question_usage()->get_slots() as $slot) {
            $maxmark += $hook->get_question_usage()->get_question_max_mark($slot);
        }
        $hook->get_attempt()->maxmark = $maxmark;
    }

    public static function add_question_action(\mod_quiz\hook\edit_question_actions $hook): void {
        global $DB;

        $structure = $hook->get_structure();
        if (!$structure->can_be_edited() || !$structure->has_use_capability($hook->get_slot())) {
            return;
        }
        $url = new \moodle_url('/local/conditional_questions/index.php', [
            'cmid' => $structure->get_cmid(),
            'slotid' => $structure->get_slot_by_number($hook->get_slot())->id,
        ]);
        $question = $structure->get_question_in_slot($hook->get_slot());
        $configured = $DB->record_exists('local_cq_rule', [
            'quizid' => $structure->get_quizid(),
            'questionid' => $question->questionid,
        ]);
        $title = get_string($configured ? 'changequestioncondition' : 'configurequestion',
            'local_conditional_questions');
        $linkclass = 'conditional-question-action' . ($configured ? ' conditional-question-configured' : '');
        $iconclass = 'icon fa fa-code-branch fa-fw' . ($configured ? ' text-success' : '');
        $hook->add_action('local_conditional_questions', \html_writer::link(
            $url,
            \html_writer::tag('i', '', ['class' => $iconclass, 'title' => $title, 'aria-label' => $title]),
            ['class' => $linkclass, 'title' => $title, 'aria-label' => $title]
        ));
    }

    public static function filter_questions(\mod_quiz\hook\before_attempt_questions_created $hook): void {
        global $DB;

        $quiz = $hook->get_quiz();
        $userid = $hook->get_userid();
        $rules = $DB->get_records('local_cq_rule', ['quizid' => $quiz->get_quizid()]);
        foreach ($rules as $rule) {
            if (!self::condition_is_met($rule, $quiz, $userid)) {
                $hook->remove_question((int) $rule->questionid);
            }
        }
    }

    private static function condition_is_met(\stdClass $rule, \mod_quiz\quiz_settings $quiz, int $userid): bool {
        global $DB;
        switch ($rule->conditiontype) {
            case 'group':
                return array_key_exists((int) $rule->conditionvalue,
                    groups_get_all_groups($quiz->get_courseid(), $userid));
            case 'activity':
                return (bool) $DB->get_record_select('course_modules_completion',
                    'coursemoduleid = :cmid AND userid = :userid AND completionstate IN (:complete, :pass)', [
                        'cmid' => $rule->conditionvalue,
                        'userid' => $userid,
                        'complete' => COMPLETION_COMPLETE,
                        'pass' => COMPLETION_COMPLETE_PASS,
                    ], 'id');
            case 'competency':
                return $DB->record_exists('competency_usercomp', [
                    'userid' => $userid,
                    'competencyid' => $rule->conditionvalue,
                    'proficiency' => 1,
                ]);
            case 'badge':
                return (bool) $DB->get_record_sql(
                    'SELECT id FROM {badge_issued}
                      WHERE userid = :userid AND badgeid = :badgeid
                        AND (dateexpire IS NULL OR dateexpire = 0 OR dateexpire > :now)',
                    ['userid' => $userid, 'badgeid' => $rule->conditionvalue, 'now' => time()]
                );
            default:
                return false;
        }
    }
}

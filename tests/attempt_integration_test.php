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
 * Integration tests for question filtering and attempt grade rescaling.
 *
 * @package    local_conditional_questions
 * @copyright  2026 Ueli Leutwyler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class attempt_integration_test extends \advanced_testcase {
    /**
     * An unmet condition removes the question and the attempt maximum is normalised.
     *
     * @covers \local_conditional_questions\hook_callbacks::filter_questions
     * @covers \local_conditional_questions\hook_callbacks::set_attempt_maxmark
     */
    public function test_unmet_condition_removes_question_and_normalises_maximum(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id, 'grade' => 100, 'sumgrades' => 2]);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category(['contextid' => \context_course::instance($course->id)->id]);
        $questionone = $questiongenerator->create_question('shortanswer', null, ['category' => $category->id]);
        $questiontwo = $questiongenerator->create_question('shortanswer', null, ['category' => $category->id]);
        \quiz_add_quiz_question($questionone->id, $quiz, 1);
        \quiz_add_quiz_question($questiontwo->id, $quiz, 1);
        \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();

        $DB->insert_record('local_conditional_questions_rule', (object) [
            'quizid' => $quiz->id,
            'questionid' => $questiontwo->id,
            'conditiontype' => 'group',
            'conditionvalue' => $group->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $quizobj = \mod_quiz\quiz_settings::create($quiz->id, $student->id);
        $attempt = \quiz_prepare_and_start_new_attempt($quizobj, 1, null, false, [], [], $student->id);
        $quba = \question_engine::load_questions_usage_by_activity($attempt->uniqueid);

        $this->assertCount(1, $quba->get_slots());
        $storedattempt = $DB->get_record('quiz_attempts', ['id' => $attempt->id], '*', MUST_EXIST);
        $this->assertEquals(1.0, (float) $storedattempt->maxmark);
        $this->assertEquals(100.0, \quiz_rescale_grade(1.0, $quiz, false, $storedattempt));
    }

    /**
     * A met activity-completion condition keeps the question in the attempt.
     *
     * @covers \local_conditional_questions\hook_callbacks::filter_questions
     */
    public function test_activity_completion_condition(): void {
        $setup = $this->create_quiz_setup();
        $activity = $this->getDataGenerator()->create_module('page', [
            'course' => $setup['course']->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ]);
        $cm = get_fast_modinfo($setup['course'], $setup['student']->id)->get_cm($activity->cmid);
        (new \completion_info($setup['course']))->update_state($cm, COMPLETION_COMPLETE, $setup['student']->id);
        global $DB;
        $completion = $DB->get_record('course_modules_completion', [
            'coursemoduleid' => $activity->cmid,
            'userid' => $setup['student']->id,
        ]);
        if ($completion) {
            $completion->completionstate = COMPLETION_COMPLETE;
            $DB->update_record('course_modules_completion', $completion);
        } else {
            $DB->insert_record('course_modules_completion', (object) [
                'coursemoduleid' => $activity->cmid,
                'userid' => $setup['student']->id,
                'completionstate' => COMPLETION_COMPLETE,
                'timemodified' => time(),
            ]);
        }

        $attempt = $this->start_conditional_attempt($setup, 'activity', $activity->cmid);
        $this->assertCount(1, \question_engine::load_questions_usage_by_activity($attempt->uniqueid)->get_slots());
    }

    /**
     * A proficient competency condition keeps the question in the attempt.
     *
     * @covers \local_conditional_questions\hook_callbacks::filter_questions
     */
    public function test_competency_condition(): void {
        $setup = $this->create_quiz_setup();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_competency');
        $framework = $generator->create_framework();
        $competency = $generator->create_competency(['competencyframeworkid' => $framework->get('id')]);
        $generator->create_user_competency([
            'userid' => $setup['student']->id,
            'competencyid' => $competency->get('id'),
            'proficiency' => 1,
            'grade' => 1,
        ]);

        $attempt = $this->start_conditional_attempt($setup, 'competency', $competency->get('id'));
        $this->assertCount(1, \question_engine::load_questions_usage_by_activity($attempt->uniqueid)->get_slots());
    }

    /**
     * An unexpired badge condition keeps the question in the attempt.
     *
     * @covers \local_conditional_questions\hook_callbacks::filter_questions
     */
    public function test_badge_condition(): void {
        $setup = $this->create_quiz_setup();
        $generator = $this->getDataGenerator()->get_plugin_generator('core_badges');
        $badge = $generator->create_badge(['courseid' => null, 'type' => BADGE_TYPE_SITE]);
        $generator->create_issued_badge(['badgeid' => $badge->id, 'userid' => $setup['student']->id]);

        $attempt = $this->start_conditional_attempt($setup, 'badge', $badge->id);
        $this->assertCount(1, \question_engine::load_questions_usage_by_activity($attempt->uniqueid)->get_slots());
    }

    /**
     * Create a course with an enrolled student and a single-question quiz.
     *
     * @return array{course: \stdClass, student: \stdClass, quiz: \stdClass, question: \stdClass}
     */
    private function create_quiz_setup(): array {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id, 'grade' => 100, 'sumgrades' => 1]);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category([
            'contextid' => \context_course::instance($course->id)->id,
        ]);
        $question = $questiongenerator->create_question('shortanswer', null, ['category' => $category->id]);
        \quiz_add_quiz_question($question->id, $quiz, 1);
        \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
        return compact('course', 'student', 'quiz', 'question');
    }

    /**
     * Store a rule of the given type and start a new attempt as the student.
     *
     * @param array $setup prepared course, student, quiz and question
     * @param string $type the condition type to configure
     * @param int $value the condition target id
     * @return \stdClass the created attempt record
     */
    private function start_conditional_attempt(array $setup, string $type, int $value): \stdClass {
        global $DB;
        $DB->insert_record('local_conditional_questions_rule', (object) [
            'quizid' => $setup['quiz']->id,
            'questionid' => $setup['question']->id,
            'conditiontype' => $type,
            'conditionvalue' => $value,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        return \quiz_prepare_and_start_new_attempt(
            \mod_quiz\quiz_settings::create($setup['quiz']->id, $setup['student']->id),
            1,
            null,
            false,
            [],
            [],
            $setup['student']->id,
        );
    }
}

<?php
namespace local_conditional_questions;

defined('MOODLE_INTERNAL') || die();

/** Tests for conditional question hook callbacks. */
final class hook_callbacks_test extends \advanced_testcase {
    public function test_unmet_group_condition_removes_question(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $DB->insert_record('local_cq_rule', (object) [
            'quizid' => 99,
            'questionid' => 123,
            'conditiontype' => 'group',
            'conditionvalue' => $group->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $quiz = $this->createMock(\mod_quiz\quiz_settings::class);
        $quiz->method('get_quizid')->willReturn(99);
        $quiz->method('get_courseid')->willReturn($course->id);
        $hook = new \mod_quiz\hook\before_attempt_questions_created($quiz, $user->id, [
            123 => (object) ['questionid' => 123],
            456 => (object) ['questionid' => 456],
        ]);

        hook_callbacks::filter_questions($hook);

        $this->assertArrayNotHasKey(123, $hook->get_questions());
        $this->assertArrayHasKey(456, $hook->get_questions());
    }
}

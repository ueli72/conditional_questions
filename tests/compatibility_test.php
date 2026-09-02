<?php
namespace local_conditional_questions;

defined('MOODLE_INTERNAL') || die();

/** Tests for core integration detection. */
final class compatibility_test extends \basic_testcase {
    public function test_supported_hooks_are_detected(): void {
        $this->assertTrue(compatibility::question_action_hook_available());
        $this->assertTrue(compatibility::filter_hook_available());
        $this->assertTrue(compatibility::attempt_maxmark_available());
    }

    public function test_unknown_hook_is_not_supported(): void {
        $this->assertFalse(compatibility::supports('local_conditional_questions\\missing_hook'));
    }

    public function test_question_action_callback_adds_configuration_link(): void {
        $structure = $this->createMock(\mod_quiz\structure::class);
        $structure->method('get_cmid')->willReturn(17);
        $structure->method('can_be_edited')->willReturn(true);
        $structure->method('has_use_capability')->willReturn(true);
        $structure->method('get_slot_by_number')->willReturn((object) ['id' => 23]);
        $pageurl = new \moodle_url('/mod/quiz/edit.php', ['cmid' => 17]);
        $hook = new \mod_quiz\hook\edit_question_actions($structure, 1, $pageurl);

        hook_callbacks::add_question_action($hook);

        $this->assertArrayHasKey('local_conditional_questions', $hook->get_actions());
        $this->assertStringContainsString('slotid=23', $hook->get_actions()['local_conditional_questions']);
    }
}

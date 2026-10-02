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
 * Tests for core integration detection.
 *
 * @package    local_conditional_questions
 * @copyright  2026 Ueli Leutwyler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_conditional_questions\compatibility
 */
final class compatibility_test extends \basic_testcase {
    /**
     * All required hooks and the patched maxmark column are detected as available.
     */
    public function test_supported_hooks_are_detected(): void {
        $this->assertTrue(compatibility::question_action_hook_available());
        $this->assertTrue(compatibility::filter_hook_available());
        $this->assertTrue(compatibility::attempt_maxmark_available());
    }

    public function test_unknown_hook_is_not_supported(): void {
        $this->assertFalse(compatibility::supports('local_conditional_questions\\missing_hook'));
    }

    /**
     * The configuration action is added to the question editing menu.
     *
     * @covers \local_conditional_questions\hook_callbacks::add_question_action
     */
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

    /**
     * A configured condition is highlighted as such in the question editing menu.
     *
     * @covers \local_conditional_questions\hook_callbacks::add_question_action
     */
    public function test_question_action_is_highlighted_when_configured(): void {
        global $DB;

        $this->resetAfterTest();
        $structure = $this->createMock(\mod_quiz\structure::class);
        $structure->method('get_cmid')->willReturn(17);
        $structure->method('get_quizid')->willReturn(99);
        $structure->method('can_be_edited')->willReturn(true);
        $structure->method('has_use_capability')->willReturn(true);
        $structure->method('get_slot_by_number')->willReturn((object) ['id' => 23]);
        $structure->method('get_question_in_slot')->willReturn((object) ['questionid' => 42]);
        $DB->insert_record('local_conditional_questions_rule', (object) [
            'quizid' => 99,
            'questionid' => 42,
            'conditiontype' => 'group',
            'conditionvalue' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $hook = new \mod_quiz\hook\edit_question_actions(
            $structure,
            1,
            new \moodle_url('/mod/quiz/edit.php', ['cmid' => 17]),
        );

        hook_callbacks::add_question_action($hook);

        $this->assertStringContainsString(
            'conditional-question-configured',
            $hook->get_actions()['local_conditional_questions']
        );
        $this->assertStringContainsString(
            'text-success',
            $hook->get_actions()['local_conditional_questions']
        );
    }
}

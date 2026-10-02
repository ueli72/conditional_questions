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
 * Tests for conditional question hook callbacks.
 *
 * @package    local_conditional_questions
 * @copyright  2026 Ueli Leutwyler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks_test extends \advanced_testcase {
    /**
     * An unmet group condition removes only the configured question from the attempt.
     *
     * @covers \local_conditional_questions\hook_callbacks::filter_questions
     */
    public function test_unmet_group_condition_removes_question(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $DB->insert_record('local_conditional_questions_rule', (object) [
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

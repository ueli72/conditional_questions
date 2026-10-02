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
 * Hook implementations registered by the conditional questions plugin.
 *
 * @package    local_conditional_questions
 * @copyright  2026 Ueli Leutwyler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [];
$hook = '\\mod_quiz\\hook\\edit_question_actions';
if (\local_conditional_questions\compatibility::supports($hook)) {
    $callbacks[] = [
        'hook' => $hook,
        'callback' => '\\local_conditional_questions\\hook_callbacks::add_question_action',
    ];
}

$hook = '\\mod_quiz\\hook\\before_attempt_questions_created';
if (\local_conditional_questions\compatibility::supports($hook)) {
    $callbacks[] = [
        'hook' => $hook,
        'callback' => '\\local_conditional_questions\\hook_callbacks::filter_questions',
    ];
}

$hook = '\\mod_quiz\\hook\\before_attempt_saved';
if (\local_conditional_questions\compatibility::supports($hook)) {
    $callbacks[] = [
        'hook' => $hook,
        'callback' => '\\local_conditional_questions\\hook_callbacks::set_attempt_maxmark',
    ];
}

$hook = '\\mod_quiz\\hook\\before_attempt_grade_rescaled';
if (\local_conditional_questions\compatibility::supports($hook)) {
    $callbacks[] = [
        'hook' => $hook,
        'callback' => '\\local_conditional_questions\\hook_callbacks::set_rescaling_maxmark',
    ];
}

$hook = '\\mod_quiz\\hook\\before_final_grade_calculated';
if (\local_conditional_questions\compatibility::supports($hook)) {
    $callbacks[] = [
        'hook' => $hook,
        'callback' => '\\local_conditional_questions\\hook_callbacks::normalise_final_grade_attempts',
    ];
}

$hook = '\\mod_quiz\\hook\\after_all_final_grades_recomputed';
if (\local_conditional_questions\compatibility::supports($hook)) {
    $callbacks[] = [
        'hook' => $hook,
        'callback' => '\\local_conditional_questions\\hook_callbacks::fix_bulk_final_grades',
    ];
}

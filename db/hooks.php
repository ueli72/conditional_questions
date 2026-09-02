<?php
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

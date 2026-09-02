<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_conditional_questions',
        get_string('pluginname', 'local_conditional_questions'));
    if ($ADMIN->fulltree) {
        $message = \local_conditional_questions\compatibility::question_action_hook_available() &&
            \local_conditional_questions\compatibility::filter_hook_available() &&
            \local_conditional_questions\compatibility::attempt_maxmark_available()
            ? get_string('coreok', 'local_conditional_questions')
            : get_string('corenotpatched', 'local_conditional_questions');
        $settings->add(new admin_setting_heading('local_conditional_questions/core_status',
            get_string('corestatus', 'local_conditional_questions'), $message));
    }
    $ADMIN->add('localplugins', $settings);
}

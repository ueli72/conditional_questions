<?php
namespace local_conditional_questions\form;

defined('MOODLE_INTERNAL') || die();

/** Configuration form for one condition per quiz question. */
final class conditions extends \moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $this->questions = $this->_customdata['questions'];
        $this->targets = $this->_customdata['targets'];
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'slotid');
        $mform->setType('slotid', PARAM_INT);
        foreach ($this->questions as $question) {
            $id = (int) $question->questionid;
            $mform->addElement('html', \html_writer::tag('h2', format_string($question->name),
                ['class' => 'text-start mb-3']));
            $mform->addElement('html', \html_writer::tag('h3',
                get_string('conditionheading', 'local_conditional_questions'), ['class' => 'text-start mb-3']));
            $mform->addElement('select', 'condition_' . $id,
                get_string('condition', 'local_conditional_questions'), [
                    '' => get_string('none'),
                    'group' => get_string('group'),
                    'activity' => get_string('activity'),
                    'competency' => get_string('competency', 'local_conditional_questions'),
                    'badge' => get_string('badge', 'local_conditional_questions'),
                ], ['class' => 'conditional-condition', 'data-question-id' => $id]);
            foreach (['group', 'activity', 'competency', 'badge'] as $type) {
                $elementtype = in_array($type, ['competency', 'badge']) ? 'autocomplete' : 'select';
                $options = $this->targets[$type];
                $attributes = ['class' => 'conditional-target', 'data-condition-type' => $type,
                    'data-question-id' => $id];
                if ($elementtype === 'autocomplete') {
                    $elementoptions = [
                        'multiple' => false,
                        'noselectionstring' => get_string('selecttarget', 'local_conditional_questions'),
                    ];
                } else {
                    $elementoptions = $attributes;
                }
                $mform->addElement($elementtype, 'target_' . $type . '_' . $id,
                    get_string('target', 'local_conditional_questions'), $options,
                    $elementtype === 'autocomplete' ? $elementoptions : $attributes);
            }
            $mform->setType('condition_' . $id, PARAM_ALPHANUMEXT);
            foreach (['group', 'activity', 'competency', 'badge'] as $type) {
                $mform->setType('target_' . $type . '_' . $id, PARAM_INT);
            }
        }
        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /** @var array */
    private array $questions;
    /** @var array */
    private array $targets;
}

# Conditional questions

This Moodle 5.2 local plugin adds one optional visibility condition to an
individual question already present in a quiz. Configure it from the condition
action in that question's quiz editing row. A condition is evaluated when a new
attempt is created. If it is not met, the question is not added to the question
usage and cannot be displayed or graded.

The current condition types are course-group membership, completed activity,
completed competency, and awarded badge. Global and course competencies, as well
as global and course badges, are available. Competencies and badges use
searchable autocomplete controls.

The plugin requires the generic `mod_quiz` hooks supplied by the core patch:

- `mod_quiz\hook\edit_menu_actions`
- `mod_quiz\hook\edit_question_actions`
- `mod_quiz\hook\before_attempt_questions_created`
- `mod_quiz\hook\before_attempt_saved`
- `mod_quiz\hook\before_attempt_grade_rescaled`
- `mod_quiz\hook\before_final_grade_calculated`
- `mod_quiz\hook\after_all_final_grades_recomputed`

Apply the patch from the Moodle root with:

```text
php public/local/conditional_questions/cli/apply_core_patch.php --apply
```

The patch changes only:

- `public/mod/quiz/classes/hook/edit_menu_actions.php`: generic add-menu hook;
- `public/mod/quiz/classes/hook/edit_question_actions.php`: generic per-question action hook;
- `public/mod/quiz/classes/hook/before_attempt_questions_created.php`: generic pre-attempt question filter;
- `public/mod/quiz/classes/output/edit_renderer.php`: dispatches the menu and per-question action hooks;
- `public/mod/quiz/locallib.php`: dispatches the pre-attempt, save, and rescaling hooks and rebuilds the layout;
- `public/mod/quiz/classes/grade_calculator.php`: dispatches final-grade hooks;
- `public/mod/quiz/view.php`, report/output files, and `classes/privacy/provider.php`: pass attempt context to the rescaling hook;
- `public/mod/quiz/db/install.xml`, `db/upgrade.php`, `version.php`: add the generic effective attempt maximum;
- `public/mod/quiz/backup/moodle2/backup_quiz_stepslib.php`: includes the field in backups.

The plugin itself does not modify Moodle core files.

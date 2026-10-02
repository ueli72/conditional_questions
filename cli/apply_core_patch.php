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
 * Check or apply the Moodle core extension required by local_conditional_questions.
 * Run from the Moodle root:
 *   php public/local/conditional_questions/cli/apply_core_patch.php --check
 *   php public/local/conditional_questions/cli/apply_core_patch.php --apply
 *
 * @package    local_conditional_questions
 * @copyright  2026 Ueli Leutwyler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

$mode = $argv[1] ?? '--check';
if (!in_array($mode, ['--check', '--apply'], true)) {
    fwrite(STDERR, "Usage: php public/local/conditional_questions/cli/apply_core_patch.php [--check|--apply]\n");
    exit(2);
}

$root = dirname(__DIR__, 4);
$patch = __DIR__ . '/core_patch.patch';
$already = is_file($root . '/public/mod/quiz/classes/hook/edit_menu_actions.php') &&
    is_file($root . '/public/mod/quiz/classes/hook/edit_question_actions.php') &&
    is_file($root . '/public/mod/quiz/classes/hook/before_attempt_questions_created.php') &&
    is_file($root . '/public/mod/quiz/classes/hook/before_attempt_saved.php') &&
    is_file($root . '/public/mod/quiz/classes/hook/before_attempt_grade_rescaled.php') &&
    is_file($root . '/public/mod/quiz/classes/hook/before_final_grade_calculated.php') &&
    is_file($root . '/public/mod/quiz/classes/hook/after_all_final_grades_recomputed.php') &&
    strpos(
        (string) file_get_contents($root . '/public/mod/quiz/classes/output/edit_renderer.php'),
        'mod_quiz\\hook\\edit_menu_actions'
    ) !== false &&
    strpos(
        (string) file_get_contents($root . '/public/mod/quiz/locallib.php'),
        'before_attempt_questions_created'
    ) !== false &&
    strpos(
        (string) file_get_contents($root . '/public/mod/quiz/db/install.xml'),
        'NAME="maxmark"'
    ) !== false;
if ($already) {
    fwrite(STDOUT, "Conditional questions core patch is already applied.\n");
    exit(0);
}
$command = 'git -C ' . escapeshellarg($root) . ' apply ' .
    ($mode === '--check' ? '--check ' : '') . escapeshellarg($patch) . ' 2>&1';
$output = [];
$status = 0;
exec($command, $output, $status);

if ($status === 0) {
    fwrite(STDOUT, $mode === '--check'
        ? "Conditional questions core patch can be applied.\n"
        : "Conditional questions core patch applied.\n");
    exit(0);
}

if ($mode === '--check' && $already) {
    fwrite(STDOUT, "Conditional questions core patch is already applied.\n");
    exit(0);
}

fwrite(STDERR, "Core patch was not applied:\n" . implode("\n", $output) . "\n");
exit(1);

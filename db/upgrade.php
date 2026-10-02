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
 * Upgrade code for the conditional questions plugin.
 *
 * @package    local_conditional_questions
 * @copyright  2026 Ueli Leutwyler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion the version the plugin was upgraded from
 * @return bool
 */
function xmldb_local_conditional_questions_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026100200) {
        // Rename local_cq_rule to the component-prefixed table name.
        $table = new xmldb_table('local_cq_rule');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'local_conditional_questions_rule');
        }
        upgrade_plugin_savepoint(true, 2026100200, 'local', 'conditional_questions');
    }

    return true;
}

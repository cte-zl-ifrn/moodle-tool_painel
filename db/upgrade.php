<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade steps for tool_painel.
 *
 * @package    tool_painel
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the tool_painel plugin.
 *
 * @param  int  $oldversion  The version we are upgrading from.
 * @return bool
 */
function xmldb_tool_painel_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    // Example upgrade step (uncomment and adapt when a schema change is needed):
    // if ($oldversion < 2024010101) {
    //     // Add a new column to tool_painel_log.
    //     $table = new xmldb_table('tool_painel_log');
    //     $field = new xmldb_field('extra', XMLDB_TYPE_TEXT, null, null, null, null, null, 'ipaddress');
    //     if (!$dbman->field_exists($table, $field)) {
    //         $dbman->add_field($table, $field);
    //     }
    //     upgrade_plugin_savepoint(true, 2024010101, 'tool', 'painel');
    // }

    return true;
}

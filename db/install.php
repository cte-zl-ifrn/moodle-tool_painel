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
 * Post-install hook for tool_painel.
 *
 * @package    tool_painel
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Called after the plugin tables and capabilities have been set up.
 */
function xmldb_tool_painel_install(): void {
    global $DB;

    // Set sensible default values for plugin settings.
    set_config('coursetypefield',   'tipo_curso',  'tool_painel');
    set_config('prefix_fic',        'FIC-',        'tool_painel');
    set_config('prefix_coordenacao','COORD-',      'tool_painel');
    set_config('prefix_laboratorio','LAB-',        'tool_painel');
    set_config('prefix_modelo',     'MODELO-',     'tool_painel');
    set_config('prefix_diario',     '',            'tool_painel');
    set_config('enablelogging',     0,             'tool_painel');
}

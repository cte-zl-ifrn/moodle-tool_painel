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
 * English language strings for tool_painel.
 *
 * @package    tool_painel
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Core.
$string['pluginname'] = 'Painel AVA';
$string['pluginname_desc'] = 'Painel AVA is an admin tool that provides an external API to retrieve course data grouped by type, including custom fields and user roles.';

// Capabilities.
$string['painel:view']              = 'View the Painel AVA panel';
$string['painel:viewothercourses']  = 'View another user\'s course list';

// Settings.
$string['settings_coursetypefield']            = 'Course type custom field';
$string['settings_coursetypefield_desc']       = 'Shortname of the course custom field used to identify the course type (e.g. tipo_curso).';
$string['settings_prefix_diario']              = 'Prefix – Diário';
$string['settings_prefix_diario_desc']         = 'Shortname prefix used to identify "Diário" (regular) courses. Leave blank if you rely only on the custom field.';
$string['settings_prefix_fic']                 = 'Prefix – FIC';
$string['settings_prefix_fic_desc']            = 'Shortname prefix used to identify FIC (Formação Inicial e Continuada) courses.';
$string['settings_prefix_coordenacao']         = 'Prefix – Coordination room';
$string['settings_prefix_coordenacao_desc']    = 'Shortname prefix used to identify coordination room courses.';
$string['settings_prefix_laboratorio']         = 'Prefix – Laboratory';
$string['settings_prefix_laboratorio_desc']    = 'Shortname prefix used to identify laboratory courses.';
$string['settings_prefix_modelo']              = 'Prefix – Model';
$string['settings_prefix_modelo_desc']         = 'Shortname prefix used to identify model/template courses.';
$string['settings_enablelogging']              = 'Enable API call logging';
$string['settings_enablelogging_desc']         = 'When enabled, every call to the external API will create an event in the Moodle log.';

// Events.
$string['event_user_courses_requested'] = 'User course list requested';

// Tasks.
$string['task_sync_courses'] = 'Painel AVA – synchronise course data';

// Errors.
$string['invaliduser'] = 'The specified user does not exist or has been deleted.';

// Misc.
$string['coursetype_diario']      = 'Regular (Diário)';
$string['coursetype_fic']         = 'FIC (Formação Inicial e Continuada)';
$string['coursetype_coordenacao'] = 'Coordination room';
$string['coursetype_laboratorio'] = 'Laboratory';
$string['coursetype_modelo']      = 'Model course';
$string['coursetype_outros']      = 'Other';

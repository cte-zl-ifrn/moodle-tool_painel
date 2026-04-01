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
 * Admin settings for tool_painel.
 *
 * @package    tool_painel
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('tool_painel', get_string('pluginname', 'tool_painel'));

    $ADMIN->add('tools', $settings);

    // Custom field shortname used to identify the course type.
    $settings->add(new admin_setting_configtext(
        'tool_painel/coursetypefield',
        get_string('settings_coursetypefield', 'tool_painel'),
        get_string('settings_coursetypefield_desc', 'tool_painel'),
        'tipo_curso',
        PARAM_ALPHANUMEXT
    ));

    // Shortname prefix for "Diário" courses.
    $settings->add(new admin_setting_configtext(
        'tool_painel/prefix_diario',
        get_string('settings_prefix_diario', 'tool_painel'),
        get_string('settings_prefix_diario_desc', 'tool_painel'),
        '',
        PARAM_RAW
    ));

    // Shortname prefix for "FIC" courses.
    $settings->add(new admin_setting_configtext(
        'tool_painel/prefix_fic',
        get_string('settings_prefix_fic', 'tool_painel'),
        get_string('settings_prefix_fic_desc', 'tool_painel'),
        'FIC-',
        PARAM_RAW
    ));

    // Shortname prefix for "Sala de Coordenação" courses.
    $settings->add(new admin_setting_configtext(
        'tool_painel/prefix_coordenacao',
        get_string('settings_prefix_coordenacao', 'tool_painel'),
        get_string('settings_prefix_coordenacao_desc', 'tool_painel'),
        'COORD-',
        PARAM_RAW
    ));

    // Shortname prefix for "Laboratório" courses.
    $settings->add(new admin_setting_configtext(
        'tool_painel/prefix_laboratorio',
        get_string('settings_prefix_laboratorio', 'tool_painel'),
        get_string('settings_prefix_laboratorio_desc', 'tool_painel'),
        'LAB-',
        PARAM_RAW
    ));

    // Shortname prefix for "Modelo" courses.
    $settings->add(new admin_setting_configtext(
        'tool_painel/prefix_modelo',
        get_string('settings_prefix_modelo', 'tool_painel'),
        get_string('settings_prefix_modelo_desc', 'tool_painel'),
        'MODELO-',
        PARAM_RAW
    ));

    // Enable web service logging.
    $settings->add(new admin_setting_configcheckbox(
        'tool_painel/enablelogging',
        get_string('settings_enablelogging', 'tool_painel'),
        get_string('settings_enablelogging_desc', 'tool_painel'),
        0
    ));
}

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
 * Strings em Português do Brasil para tool_painel.
 *
 * @package    tool_painel
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Core.
$string['pluginname'] = 'Painel AVA';
$string['pluginname_desc'] = 'O Painel AVA é uma ferramenta administrativa que fornece uma API externa para recuperar dados de cursos agrupados por tipo, incluindo campos personalizados e papéis do usuário.';

// Capabilities.
$string['painel:view']              = 'Visualizar o Painel AVA';
$string['painel:viewothercourses']  = 'Visualizar a lista de cursos de outro usuário';

// Settings.
$string['settings_coursetypefield']            = 'Campo personalizado de tipo de curso';
$string['settings_coursetypefield_desc']       = 'Nome curto do campo personalizado do curso usado para identificar o tipo de curso (ex.: tipo_curso).';
$string['settings_prefix_diario']              = 'Prefixo – Diário';
$string['settings_prefix_diario_desc']         = 'Prefixo do nome curto usado para identificar cursos "Diário" (regulares). Deixe em branco se utilizar apenas o campo personalizado.';
$string['settings_prefix_fic']                 = 'Prefixo – FIC';
$string['settings_prefix_fic_desc']            = 'Prefixo do nome curto usado para identificar cursos FIC (Formação Inicial e Continuada).';
$string['settings_prefix_coordenacao']         = 'Prefixo – Sala de Coordenação';
$string['settings_prefix_coordenacao_desc']    = 'Prefixo do nome curto usado para identificar salas de coordenação.';
$string['settings_prefix_laboratorio']         = 'Prefixo – Laboratório';
$string['settings_prefix_laboratorio_desc']    = 'Prefixo do nome curto usado para identificar laboratórios.';
$string['settings_prefix_modelo']              = 'Prefixo – Modelo';
$string['settings_prefix_modelo_desc']         = 'Prefixo do nome curto usado para identificar cursos modelo/template.';
$string['settings_enablelogging']              = 'Habilitar log de chamadas da API';
$string['settings_enablelogging_desc']         = 'Quando habilitado, cada chamada à API externa criará um evento no log do Moodle.';

// Events.
$string['event_user_courses_requested'] = 'Lista de cursos do usuário solicitada';

// Tasks.
$string['task_sync_courses'] = 'Painel AVA – sincronizar dados dos cursos';

// Errors.
$string['invaliduser'] = 'O usuário especificado não existe ou foi excluído.';

// Misc.
$string['coursetype_diario']      = 'Diário';
$string['coursetype_fic']         = 'FIC (Formação Inicial e Continuada)';
$string['coursetype_coordenacao'] = 'Sala de Coordenação';
$string['coursetype_laboratorio'] = 'Laboratório';
$string['coursetype_modelo']      = 'Curso Modelo';
$string['coursetype_outros']      = 'Outros';

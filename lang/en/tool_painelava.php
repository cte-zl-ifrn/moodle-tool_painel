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
 * English language strings for tool_painelava.
 *
 * @package    tool_painelava
 * @copyright  2024 IFRN
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Core.
$string['pluginname'] = 'Painel AVA';
$string['pluginname_desc'] = 'Painel AVA is an admin tool that provides an external API to retrieve course data grouped by type, including custom fields and user roles.';

// Capabilities.
$string['painelava:view']              = 'View the Painel AVA panel';
$string['painelava:viewothercourses']  = 'View another user\'s course list';

// Settings.
$string['auth_token_header'] = 'Painel AVA';
$string['auth_token_header_desc'] = 'Which will be the token used by Painel AVA to authenticate itself to this Moodle installation';

$string["auth_token"] = 'Auth token';
$string["auth_token_desc"] = 'Which will be the token used by Painel AVA to authenticate itself to this Moodle installation';

$string['painel_url'] = 'Painel AVA URL';
$string['painel_url_desc'] = '(ex: https://ava.ifrn.edu.br)';

// Events.
$string['event_user_courses_requested'] = 'User course list requested';

// Tasks.
$string['task_sync_courses'] = 'Painel AVA – synchronise course data';

// Errors.
$string['invaliduser'] = 'The specified user does not exist or has been deleted.';

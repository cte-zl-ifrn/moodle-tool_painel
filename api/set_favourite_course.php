<?php

namespace tool_painelava;

// Desabilita verificação CSRF para esta API
if (!defined('NO_MOODLE_COOKIES')) {
    define('NO_MOODLE_COOKIES', true);
}

require_once('../../../../config.php');
global $CFG;
require_once($CFG->dirroot . '/course/externallib.php');
require_once('../locallib.php');
require_once("servicelib.php");

class set_favourite_course_service extends \tool_painelava\service
{

    function do_call()
    {
        global $DB, $USER;

        $username  = \tool_painelava\aget($_GET, 'username', '');
        $courseid  = \tool_painelava\aget($_GET, 'courseid', 0);
        $favourite = \tool_painelava\aget($_GET, 'favourite', 0);

        $USER = $DB->get_record('user', ['username' => strtolower($username)]);

        if (!$USER) {
             return ['error' => ['message' => "Usuário não encontrado", 'code' => 404]];
        }

        $course = $DB->get_record('course', ['id' => $_GET['courseid']]);

        $is_favourite = ($favourite == 1 || $favourite === 'true' || $favourite === true);

        return $this->execute($courseid, $is_favourite);
    }

    function execute($courseid, $favourite)
    {
        return \core_course_external::set_favourite_courses([['id' => $courseid, 'favourite' => $favourite]]);
    }
}
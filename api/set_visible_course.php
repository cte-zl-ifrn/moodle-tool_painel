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

class set_visible_course_service extends \tool_painelava\service
{

    function do_call()
    {
        global $DB, $USER;

        $username  = \tool_painelava\aget($_GET, 'username', '');
        $courseid  = \tool_painelava\aget($_GET, 'courseid', 0);
        $visible   = \tool_painelava\aget($_GET, 'visible', 0);

        $USER = $DB->get_record('user', ['username' => strtolower($username)]);

        if (!$USER) {
             return ['error' => ['message' => "Usuário não encontrado", 'code' => 404]];
        }

        $coursecontext = \context_course::instance($courseid);
        if (!has_capability('moodle/course:visibility', $coursecontext, $USER)) {
            throw new \Exception('Sem permissão de alterar a visibilidade deste curso.', 403);
        }

        $course = $DB->get_record('course', ['id' => $courseid]);

        return $this->execute($course, $visible);
    }

    function execute($course, $visible)
    {
        global $DB;

        $course->visible = $visible;
        $DB->update_record('course', $course);
        return ["error" => false];
    }
}
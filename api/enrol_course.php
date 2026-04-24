<?php

namespace tool_painelava;

if (!defined('NO_MOODLE_COOKIES')) {
    define('NO_MOODLE_COOKIES', true);
}

require_once('../../../../config.php');
require_once('../locallib.php');
require_once("servicelib.php");

class enrol_course_service extends \tool_painelava\service
{

    function do_call()
    {
        global $DB, $CFG, $USER;

        require_once($CFG->dirroot . '/course/externallib.php');

        $username = strtolower(\tool_painelava\aget($_GET, 'username', ''));
        $courseid = \tool_painelava\aget($_GET, 'courseid', 0);

        if (empty($username) || empty($courseid)) {
            throw new \Exception("Username e CourseID são obrigatórios.", 400);
        }

        $USER = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $context = \context_course::instance($courseid);

        // 1. Busca se já existe uma inscrição (ativa ou suspensa)
        $user_enrolment = $DB->get_record_sql("
            SELECT ue.*, e.enrol 
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            WHERE e.courseid = ? AND ue.userid = ?
        ", [$courseid, $USER->id]);

        // 2. caso exista, verificamos o status
        if ($user_enrolment) {
            if ($user_enrolment->status == 0) {
                return [
                    "status" => "already_enrolled",
                    "message" => "Usuário já possui inscrição ativa.",
                    "courseid" => $courseid
                ];
            }

            // Se estiver suspensa (status 1), vamos reativar (status 0)
            $plugin = enrol_get_plugin($user_enrolment->enrol);
            $enrol_instance = $DB->get_record('enrol', ['id' => $user_enrolment->enrolid]);
            
            $plugin->update_user_enrol($enrol_instance, $USER->id, 0);

            return [
                "status" => "reactivated",
                "message" => "Sua inscrição foi reativada. Seu progresso anterior foi recuperado.",
                "courseid" => $courseid,
                "viewurl" => "{$CFG->wwwroot}/course/view.php?id={$courseid}"
            ];
        }
        
        // 3. Caso não exista (Nova Inscrição)
        $enrol = $DB->get_record('enrol', [
            'courseid' => $courseid, 
            'enrol' => 'manual', 
            'status' => 0
        ], '*', MUST_EXIST);

        $plugin = enrol_get_plugin('manual');
        $plugin->enrol_user($enrol, $USER->id, 5);

        return [
            "status" => "enrolled",
            "message" => "Inscrição realizada com sucesso.",
            "courseid" => $courseid,
            "viewurl" => "{$CFG->wwwroot}/course/view.php?id={$courseid}"
        ];
    }
}
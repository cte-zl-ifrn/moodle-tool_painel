<?php

namespace tool_painelava;

// Desabilita verificação CSRF para esta API
if (!defined('NO_MOODLE_COOKIES')) {
    define('NO_MOODLE_COOKIES', true);
}

require_once('../../../../config.php');
require_once('../locallib.php');
require_once("servicelib.php");

define("REGEX_CODIGO_DIARIO", '/^(\d\d\d\d\d)\.(\d*)\.(\d*)\.(.*)\.(.*\..*)$/');
define("REGEX_CODIGO_COORDENACAO", '/^ZL\.\d*/');
define("REGEX_CODIGO_PRATICA", '/^(.*)\.(\d{11,14}\d*)$/');
define("REGEX_CODIGO_DIARIO_ELEMENTS_COUNT", 6);
define("REGEX_CODIGO_DIARIO_SEMESTRE", 1);
define("REGEX_CODIGO_DIARIO_PERIODO", 2);
define("REGEX_CODIGO_DIARIO_CURSO", 3);
define("REGEX_CODIGO_DIARIO_TURMA", 4);
define("REGEX_CODIGO_DIARIO_DISCIPLINA", 5);

class get_diarios_service extends \tool_painelava\service
{

    function get_cursos($all_diarios)
    {
        global $DB;
        $result = [];
        foreach ($all_diarios as $course) {
            preg_match(REGEX_CODIGO_DIARIO, $course->shortname, $matches);
            if (count($matches) == REGEX_CODIGO_DIARIO_ELEMENTS_COUNT) {
                $curso = $matches[REGEX_CODIGO_DIARIO_CURSO];
                $result[$curso] = ['id' => $curso, 'label' => $curso];
            }
        }
        return array_values($result);
    }

    function get_disciplinas($all_diarios)
    {
        global $DB;
        $result = [];
        foreach ($all_diarios as $course) {
            preg_match(REGEX_CODIGO_DIARIO, $course->shortname, $matches);
            if (count($matches) == REGEX_CODIGO_DIARIO_ELEMENTS_COUNT) {
                $disciplina = $matches[REGEX_CODIGO_DIARIO_DISCIPLINA];
                $result[$disciplina] = ['id' => $disciplina, 'label' => "$course->fullname [$disciplina]"];
            }
        }
        return array_values($result);
    }

    function get_semestres($all_diarios)
    {
        global $DB;

        $result = [];
        foreach ($all_diarios as $course) {
            preg_match(REGEX_CODIGO_DIARIO, $course->shortname, $matches);
            if (count($matches) == REGEX_CODIGO_DIARIO_ELEMENTS_COUNT) {
                $semestre = $matches[REGEX_CODIGO_DIARIO_SEMESTRE];
                $result[$semestre] = ['id' => $semestre, 'label' => substr($semestre, 0, -1) . '.' . substr($semestre, 4, 1)];
            }
        }
        return array_values($result);
    }

    function get_all_diarios($username)
    {
        return \tool_painelava\get_recordset_as_array(
            "
            SELECT      c.id, 
                        c.shortname shortname,
                        c.fullname fullname
            FROM        {user} u
                            INNER JOIN {user_enrolments} ue ON (ue.userid = u.id)
                            INNER JOIN {enrol} e ON (e.id = ue.enrolid)
                            INNER JOIN {course} c ON (c.id = e.courseid)
            WHERE u.username = ? 
              AND ue.status = 0 
              AND e.status = 0
            ",
            [strtolower($username)]
        );
    }

    function get_diarios($username, $semestre, $situacao, $ordenacao, $disciplina, $curso, $arquetipo, $q, $page, $page_size)
    {
        global $DB, $CFG, $USER;

        require_once($CFG->dirroot . '/course/externallib.php');

        $USER = $DB->get_record('user', ['username' => strtolower($username)]);
        // $USER = $DB->get_record('user', ['username' => $_GET['username']]);
        if (!$USER) {
            return [
                'error' => ['message' => "Usuário '{$_GET['username']}' não existe", 'code' => 404],
                "semestres" => [],
                "disciplinas" => [],
                "cursos" => [],
                "diarios" => [],
                "coordenacoes" => [],
                "praticas" => [],
            ];
        }

        $all_diarios = $this->get_all_diarios($USER->username);

        $enrolled_courses = \core_course_external::get_enrolled_courses_by_timeline_classification($situacao, 0, 0, $ordenacao)['courses'];
        
        $diarios = [];
        $coordenacoes = [];
        $praticas = [];

        $cf_handler = \core_course\customfield\course_handler::create();

        foreach ($enrolled_courses as $diario) {
            unset($diario->summary);
            unset($diario->summaryformat);
            unset($diario->courseimage);
            $coursecontext = \context_course::instance($diario->id);
            $diario->can_set_visibility = has_capability('moodle/course:visibility', $coursecontext, $USER) ? 1 : 0;

            $sql = "SELECT f.shortname, d.intvalue, d.charvalue, f.type, f.configdata
                    FROM {customfield_data} d
                    JOIN {customfield_field} f ON d.fieldid = f.id
                    WHERE d.instanceid = ?";
            $cf_records = $DB->get_records_sql($sql, [$diario->id]);

            $cf = new \stdClass();
            foreach ($cf_records as $record) {
                if ($record->type === 'select') {
                    $config = json_decode($record->configdata);
                    $options = explode("\n", str_replace("\r", "", $config->options ?? ''));
                    $index = ((int)$record->intvalue) - 1;
                    $cf->{$record->shortname} = $options[$index] ?? '';
                } else {
                    $cf->{$record->shortname} = $record->charvalue;
                }
            }

            $sala_tipo = isset($cf->sala_tipo) ? strtolower(trim($cf->sala_tipo)) : '';

            if ($sala_tipo === 'coordenacoes' || preg_match(REGEX_CODIGO_COORDENACAO, $diario->shortname)) {
                $coordenacoes[] = $diario;
            } elseif ($sala_tipo === 'praticas' || preg_match(REGEX_CODIGO_PRATICA, $diario->shortname)) {
                $praticas[] = $diario;
            } else {
                // Cai aqui se for 'diarios', 'autoinscricoes' ou se for um CURSO ANTIGO
                if (!empty($semestre . $disciplina . $curso . $q)) {
                    preg_match(REGEX_CODIGO_DIARIO, $diario->shortname, $matches);
                    if (count($matches) == REGEX_CODIGO_DIARIO_ELEMENTS_COUNT) {
                        if (
                            ((empty($q)) || (!empty($q) && strpos(strtoupper($diario->shortname . ' ' . $diario->fullname), strtoupper($q)) !== false)) &&
                            ((empty($semestre)) || (!empty($semestre) && $matches[REGEX_CODIGO_DIARIO_SEMESTRE] == $semestre)) &&
                            ((empty($disciplina)) || (!empty($disciplina) && $matches[REGEX_CODIGO_DIARIO_DISCIPLINA] == $disciplina)) &&
                            ((empty($curso)) || (!empty($curso) && $matches[REGEX_CODIGO_DIARIO_CURSO] == $curso))
                        ) {
                            $diarios[] = $diario;
                        }
                    }
                } else {
                    $diarios[] = $diario;
                }
            }
        }

        $vitrine_autoinscricoes = [];
        
        // 1. Descobre o ID numérico da opção "autoinscricoes" no banco
        $campo_sala = $DB->get_record('customfield_field', ['shortname' => 'sala_tipo']);
        
        if ($campo_sala) {
            $config = json_decode($campo_sala->configdata);
            $opcoes = explode("\n", str_replace("\r", "", $config->options ?? ''));
            $indice_opcao = -1;
            
            foreach ($opcoes as $i => $opcao) {
                if (strtolower(trim($opcao)) === 'autoinscricoes') {
                    $indice_opcao = $i + 1;
                    break;
                }
            }

            if ($indice_opcao !== -1) {
                // 2. Busca todos os cursos visíveis marcados com essa opção
                $sql_vitrine = "SELECT c.id, c.fullname, c.shortname, c.summary 
                                FROM {course} c
                                JOIN {customfield_data} d ON d.instanceid = c.id
                                WHERE d.fieldid = ? AND d.intvalue = ? AND c.visible = 1";
                                
                $cursos_vitrine = $DB->get_records_sql($sql_vitrine, [$campo_sala->id, $indice_opcao]);

                // 3. Cria um mapa (Dicionário) das matrículas atuais do aluno (O(1))
                $mapa_matriculados = [];
                foreach ($all_diarios as $diario_aluno) {
                    $mapa_matriculados[$diario_aluno->id] = true;
                }

                // 4. Monta a lista final com a Flag solicitada
                foreach ($cursos_vitrine as $curso_vitrine) {
                    
                    $curso_vitrine->is_enrolled = isset($mapa_matriculados[$curso_vitrine->id]);
                    
                    $curso_vitrine->summary = trim(strip_tags($curso_vitrine->summary));
                    
                    $vitrine_autoinscricoes[] = $curso_vitrine;
                }
            }
        }


        return [
            "semestres" => $this->get_semestres($all_diarios),
            "disciplinas" => $this->get_disciplinas($all_diarios),
            "cursos" => $this->get_cursos($all_diarios, 'ASC'),
            "diarios" => $diarios,
            "coordenacoes" => $coordenacoes,
            "praticas" => $praticas,
            "vitrine_autoinscricoes" => $vitrine_autoinscricoes, 
        ];
    }

    function do_call()
    {
        return $this->get_diarios(
            \tool_painelava\aget($_GET, 'username', null),
            \tool_painelava\aget($_GET, 'semestre', null),
            \tool_painelava\aget($_GET, 'situacao', null),
            \tool_painelava\aget($_GET, 'ordenacao', null),
            \tool_painelava\aget($_GET, 'disciplina', null),
            \tool_painelava\aget($_GET, 'curso', null),
            \tool_painelava\aget($_GET, 'arquetipo', 'student'),
            \tool_painelava\aget($_GET, 'q', null),
            \tool_painelava\aget($_GET, 'page', 1),
            \tool_painelava\aget($_GET, 'page_size', 9),
        );
    }
}
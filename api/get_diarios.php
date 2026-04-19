<?php

namespace tool_painelava;

// Desabilita verificação CSRF para esta API
if (!defined('NO_MOODLE_COOKIES')) {
    define('NO_MOODLE_COOKIES', true);
}

require_once('../../../../config.php');
require_once('../locallib.php');
require_once("servicelib.php");

// define("REGEX_CODIGO_DIARIO", '/^(\d\d\d\d\d)\.(\d*)\.(\d*)\.(.*)\.(.*\..*)$/');
define("REGEX_CODIGO_COORDENACAO", '/^ZL\.\d*/');
define("REGEX_CODIGO_PRATICA", '/^(.*)\.(\d{11,14}\d*)$/');
// define("REGEX_CODIGO_DIARIO_ELEMENTS_COUNT", 6);
// define("REGEX_CODIGO_DIARIO_SEMESTRE", 1);
// define("REGEX_CODIGO_DIARIO_PERIODO", 2);
// define("REGEX_CODIGO_DIARIO_CURSO", 3);
// define("REGEX_CODIGO_DIARIO_TURMA", 4);
// define("REGEX_CODIGO_DIARIO_DISCIPLINA", 5);

class get_diarios_service extends \tool_painelava\service
{

    function get_cursos($all_diarios)
    {
        $result = [];
        foreach ($all_diarios as $course) {
            $curso_id = $course->curso_codigo ?? '';
            $curso_desc = $course->curso_descricao ?? '';
            
            if (!empty($curso_id)) {
                $result[$curso_id] = ['id' => $curso_id, 'label' => $curso_desc ?: $curso_id];
            }
        }
        return array_values($result);
    }

    function get_disciplinas($all_diarios)
    {
        $result = [];
        foreach ($all_diarios as $course) {
            $disciplina_id = $course->disciplina_id ?? '';
            $disciplina_desc = $course->disciplina_descricao ?? '';
            
            if (!empty($disciplina_id)) {
                $result[$disciplina_id] = ['id' => $disciplina_id, 'label' => $disciplina_desc ?: $disciplina_id];
            }
        }
        return array_values($result);
    }

    function get_semestres($all_diarios)
    {
        $result = [];
        foreach ($all_diarios as $course) {
            $semestre = $course->turma_ano_periodo ?? '';
            
            if (!empty($semestre)) {
                $label = str_replace('/', '.', $semestre);                 
                $result[$semestre] = ['id' => $semestre, 'label' => $label];
            }
        }
        return array_values($result);
    }

    function get_all_diarios($username)
    {
        global $DB;
        
        $courses = \tool_painelava\get_recordset_as_array(
            "
            SELECT      c.id, c.shortname, c.fullname
            FROM        {user} u
                            INNER JOIN {user_enrolments} ue ON (ue.userid = u.id)
                            INNER JOIN {enrol} e ON (e.id = ue.enrolid)
                            INNER JOIN {course} c ON (c.id = e.courseid)
            WHERE u.username = ? AND ue.status = 0 AND e.status = 0
            ",
            [strtolower($username)]
        );

        if (empty($courses)) return [];

        $course_ids = array_column($courses, 'id');
        list($insql, $inparams) = $DB->get_in_or_equal($course_ids);

        $sql_cf = "SELECT d.id as dataid, d.instanceid, f.shortname, d.charvalue
                   FROM {customfield_data} d
                   JOIN {customfield_field} f ON d.fieldid = f.id
                   WHERE d.instanceid $insql
                     AND f.shortname IN ('turma_ano_periodo', 'disciplina_id', 'disciplina_descricao', 'curso_codigo', 'curso_descricao')";
        
        $cf_records = $DB->get_records_sql($sql_cf, $inparams);
        
        $cfs = [];
        if ($cf_records) {
            foreach ($cf_records as $rec) {
                $cfs[$rec->instanceid][$rec->shortname] = trim($rec->charvalue);
            }
        }

        foreach ($courses as &$c) {
            $c->turma_ano_periodo = $cfs[$c->id]['turma_ano_periodo'] ?? '';
            $c->disciplina_id = $cfs[$c->id]['disciplina_id'] ?? '';
            $c->disciplina_descricao = $cfs[$c->id]['disciplina_descricao'] ?? '';
            $c->curso_codigo = $cfs[$c->id]['curso_codigo'] ?? '';
            $c->curso_descricao = $cfs[$c->id]['curso_descricao'] ?? '';
        }

        return $courses;
    }

    /**
     * Busca um valor dentro de um array associativo usando "dot notation".
     * Exemplo: busca 'modalidade.id' dentro do JSON de login.
     */
    private function resolve_dot_notation($array, $path) {
        $keys = explode('.', $path);
        foreach ($keys as $key) {
            if (is_array($array) && array_key_exists($key, $array)) {
                $array = $array[$key];
            } else {
                return null;
            }
        }
        return $array;
    }

    /**
     * Verifica se o aluno atende a uma restrição específica.
     */
    private function avalia_restricao($aluno_data, $chave, $valor_esperado) {
        // Busca o valor real que está no JSON do aluno
        $valor_aluno = $this->resolve_dot_notation($aluno_data, $chave);
        
        // O JSON do SUAP unificou os campos "eh_*" dentro de "tipo_usuario"
        if (strpos($chave, 'eh_') === 0 && $valor_aluno === null) {
            // Se a chave é 'eh_aluno' e não existe direto no JSON, procuramos no 'tipo_usuario'
            $tipo_usuario = strtolower($aluno_data['tipo_usuario'] ?? '');
            
            // Mapeamento básico: 'eh_aluno' -> procura por 'aluno'
            $termo_busca = str_replace('eh_', '', $chave); 
            $valor_aluno = (strpos($tipo_usuario, $termo_busca) !== false) ? 'true' : 'false';
        }

        if (is_bool($valor_aluno)) {
            $valor_aluno = $valor_aluno ? 'true' : 'false';
        }

        // Verifica se bateu (usando == para ignorar diferenças de int/string como 1 e "1")
        return (string)$valor_aluno === (string)$valor_esperado;
    }


    /**
     * Busca os cursos disponíveis para autoinscrição e aplica os filtros do perfil do usuário.
     */
    private function get_autoinscricoes($userid, $all_diarios) 
    {
        global $DB;
        $autoinscricoes = [];
        
        // 1. Descobre o ID do campo "sala_tipo"
        $campo_sala = $DB->get_record('customfield_field', ['shortname' => 'sala_tipo']);
        
        if (!$campo_sala) {
            return $autoinscricoes;
        }

        // 2. Busca todos os cursos visíveis marcados com "autoinscricoes"
        $sql_vitrine = "SELECT c.id, c.fullname, c.shortname
                        FROM {course} c
                        JOIN {customfield_data} d ON d.instanceid = c.id
                        WHERE d.fieldid = ? AND d.value = ? AND c.visible = 1";
                        
        $cursos_vitrine = $DB->get_records_sql($sql_vitrine, [$campo_sala->id, 'autoinscricoes']);

        if (empty($cursos_vitrine)) {
            return $autoinscricoes;
        }
            
        // A) Busca o JSON do aluno logado
        $sql_user_json = "SELECT d.data
                            FROM {user_info_data} d
                            JOIN {user_info_field} f ON d.fieldid = f.id
                            WHERE d.userid = ? AND f.shortname = 'last_login'";
        $json_record = $DB->get_record_sql($sql_user_json, [$userid]);

        $aluno_data = [];
        if ($json_record && !empty($json_record->data)) {
            $texto_limpo = html_entity_decode(strip_tags($json_record->data), ENT_QUOTES, 'UTF-8');
            $aluno_data = json_decode($texto_limpo, true);
        }

        $aluno_modalidade_id = $this->resolve_dot_notation($aluno_data, 'modalidade.id');
        $aluno_nivel_id = $this->resolve_dot_notation($aluno_data, 'modalidade.nivel_ensino.id');

        // B) Busca TODAS as restrições dos cursos da vitrine em lote
        $vitrine_ids = array_column($cursos_vitrine, 'id');
        list($v_insql, $v_inparams) = $DB->get_in_or_equal($vitrine_ids);
        
        $sql_cf_vitrine = "SELECT d.instanceid, f.shortname, d.charvalue
                            FROM {customfield_data} d
                            JOIN {customfield_field} f ON d.fieldid = f.id
                            WHERE d.instanceid $v_insql
                                AND f.shortname IN ('curso_modalidade_id', 'curso_nivel_ensino_id')";
        
        $cf_vitrine_records = $DB->get_records_sql($sql_cf_vitrine, $v_inparams);
        
        $cf_vitrine = [];
        if ($cf_vitrine_records) {
            foreach ($cf_vitrine_records as $rec) {
                $cf_vitrine[$rec->instanceid][$rec->shortname] = trim($rec->charvalue);
            }
        }

        // C) Monta o mapa de matrículas (para saber se o aluno já faz o curso)
        $mapa_matriculados = [];
        foreach ($all_diarios as $diario_aluno) {
            $mapa_matriculados[$diario_aluno->id] = true;
        }

        // D) Avalia curso por curso e aplica regras
        foreach ($cursos_vitrine as $curso_vitrine) {
            
            $passou_nos_filtros = true; 
            
            $curso_mod_id = $cf_vitrine[$curso_vitrine->id]['curso_modalidade_id'] ?? '';
            $curso_niv_id = $cf_vitrine[$curso_vitrine->id]['curso_nivel_ensino_id'] ?? '';

            // REGRA 1: FILTRO DE MODALIDADE
            if ($curso_mod_id !== '' && (string)$curso_mod_id !== (string)$aluno_modalidade_id) {
                $passou_nos_filtros = false;
            }

            // REGRA 2: FILTRO DE NÍVEL DE ENSINO
            if ($curso_niv_id !== '' && (string)$curso_niv_id !== (string)$aluno_nivel_id) {
                $passou_nos_filtros = false;
            }

            if ($passou_nos_filtros) {
                $curso_vitrine->is_enrolled = isset($mapa_matriculados[$curso_vitrine->id]);
                $autoinscricoes[] = $curso_vitrine;
            }
        }

        return $autoinscricoes;
    }



    function get_diarios($username, $semestre, $situacao, $ordenacao, $disciplina, $curso, $arquetipo, $q, $page, $page_size)
    {
        global $DB, $CFG, $USER;

        require_once($CFG->dirroot . '/course/externallib.php');

        $USER = $DB->get_record('user', ['username' => strtolower($username)]);
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

        $agrupamentos = [];

        foreach ($enrolled_courses as $diario) {
            unset($diario->summary);
            unset($diario->summaryformat);
            unset($diario->courseimage);
            $coursecontext = \context_course::instance($diario->id);
            $diario->can_set_visibility = has_capability('moodle/course:visibility', $coursecontext, $USER) ? 1 : 0;

            $sql = "SELECT f.shortname, d.intvalue, d.shortcharvalue, d.charvalue, d.value, f.type, f.configdata
                    FROM {customfield_data} d
                    JOIN {customfield_field} f ON d.fieldid = f.id
                    WHERE d.instanceid = ?";
            $cf_records = $DB->get_records_sql($sql, [$diario->id]);

            $cf = new \stdClass();
            foreach ($cf_records as $record) {
                $cf->{$record->shortname} = $record->value ?: $record->charvalue ?: $record->shortcharvalue;
            }

            $sala_tipo = isset($cf->sala_tipo) ? strtolower(trim($cf->sala_tipo)) : '';

            // FALLBACK DE LEGADO: Se não tiver o campo preenchido, usa a lógica de RegEx
            if (empty($sala_tipo)) {
                if (preg_match(REGEX_CODIGO_COORDENACAO, $diario->shortname)) {
                    $sala_tipo = 'coordenacoes';
                } elseif (preg_match(REGEX_CODIGO_PRATICA, $diario->shortname)) {
                    $sala_tipo = 'praticas';
                } else {
                    $sala_tipo = 'diarios';
                }
            }

            if ($sala_tipo === 'autoinscricoes') {
                continue;
            }

            if (!isset($agrupamentos[$sala_tipo])) {
                $agrupamentos[$sala_tipo] = [];
            }

            // 3. Lógica de filtragem (Aplicada apenas aos cursos do tipo 'diarios')
            if ($sala_tipo === 'diarios') {
                $c_semestre = isset($cf->turma_ano_periodo) ? trim($cf->turma_ano_periodo) : '';
                $c_disciplina = isset($cf->disciplina_id) ? trim($cf->disciplina_id) : '';
                $c_curso = isset($cf->curso_codigo) ? trim($cf->curso_codigo) : '';

                if (!empty($semestre . $disciplina . $curso . $q)) {
                    if (
                        ((empty($q)) || (!empty($q) && strpos(strtoupper($diario->shortname . ' ' . $diario->fullname), strtoupper($q)) !== false)) &&
                        ((empty($semestre)) || (!empty($semestre) && $c_semestre == $semestre)) &&
                        ((empty($disciplina)) || (!empty($disciplina) && $c_disciplina == $disciplina)) &&
                        ((empty($curso)) || (!empty($curso) && $c_curso == $curso))
                    ) {
                        $agrupamentos[$sala_tipo][] = $diario;
                    }
                } else {
                    $agrupamentos[$sala_tipo][] = $diario;
                }
            } else {
                // Outros tipos de sala entram sem filtros de busca
                $agrupamentos[$sala_tipo][] = $diario;
            }
        }

        $autoinscricoes = $this->get_autoinscricoes($USER->id, $all_diarios);

        $return_base = [
            "semestres" => $this->get_semestres($all_diarios),
            "disciplinas" => $this->get_disciplinas($all_diarios),
            "cursos" => $this->get_cursos($all_diarios),
            "autoinscricoes" => $autoinscricoes,
        ];

        // Se o usuário não estiver em nenhuma sala, garante que 'diarios' pelo menos vá vazio
        if (empty($agrupamentos)) {
            $agrupamentos['diarios'] = [];
        }

        return array_merge($return_base, $agrupamentos);
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
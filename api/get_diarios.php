<?php

namespace tool_painelava;

// Desabilita verificação CSRF para esta API
if (!defined('NO_MOODLE_COOKIES')) {
    define('NO_MOODLE_COOKIES', true);
}

require_once('../../../../config.php');
require_once('../locallib.php');
require_once("servicelib.php");

define("REGEX_CODIGO_COORDENACAO", '/^ZL\.\d*/');
define("REGEX_CODIGO_PRATICA", '/^(.*)\.(\d{11,14}\d*)$/');

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
            $cf_dados_curso = $cfs[$c->id] ?? [];
            $this->inject_custom_fields($c, $cf_dados_curso);
        }

        return $courses;
    }

    /**
     * Injeta os campos customizados padronizados no objeto do curso.
     * Aceita os dados de origem tanto como Array quanto como Objeto.
     */
    private function inject_custom_fields($curso, $cf_data) {
        $cf_data = (array) $cf_data;

        $curso->turma_ano_periodo = isset($cf_data['turma_ano_periodo']) ? trim($cf_data['turma_ano_periodo']) : '';
        $curso->disciplina_id     = isset($cf_data['disciplina_id']) ? trim($cf_data['disciplina_id']) : '';
        $curso->disciplina_sigla  = isset($cf_data['disciplina_sigla']) ? trim($cf_data['disciplina_sigla']) : '';
        $curso->curso_codigo      = isset($cf_data['curso_codigo']) ? trim($cf_data['curso_codigo']) : '';
        $curso->curso_descricao   = isset($cf_data['curso_descricao']) ? trim($cf_data['curso_descricao']) : '';
        $curso->diario_id         = isset($cf_data['diario_id']) ? trim($cf_data['diario_id']) : null;
        
        return $curso;
    }

    /**
     * Busca valores dentro de um array associativo usando "dot notation", 
     * com suporte a curingas (*) para iterar sobre listas.
     */
    private function resolve_dot_notation($array, $path) {
        $keys = explode('.', $path);
        $results = [$array];

        foreach ($keys as $key) {
            $next_results = [];
            foreach ($results as $item) {
                if ($key === '*') {
                    // Se for curinga, adiciona todos os sub-itens (para olhar dentro do array)
                    if (is_array($item)) {
                        foreach ($item as $sub_item) {
                            $next_results[] = $sub_item;
                        }
                    }
                } else {
                    if (is_array($item) && array_key_exists($key, $item)) {
                        $next_results[] = $item[$key];
                    }
                }
            }
            $results = $next_results;
        }

        return $results;
    }

    /**
     * Verifica se o aluno atende a uma restrição específica.
     */
    private function avalia_restricao($aluno_data, $chave, $valor_esperado) {
        // Tratamento especial legado do SUAP (unificação em 'tipo_usuario')
        if (strpos($chave, 'eh_') === 0 && !array_key_exists($chave, $aluno_data)) {
            $tipo_usuario = strtolower($aluno_data['tipo_usuario'] ?? '');
            $termo_busca = str_replace('eh_', '', $chave); 
            $valor_aluno = (strpos($tipo_usuario, $termo_busca) !== false) ? 'true' : 'false';
            return (string)$valor_aluno === (string)$valor_esperado;
        }

        $valores_aluno = $this->resolve_dot_notation($aluno_data, $chave);
        
        foreach ($valores_aluno as $v) {
            if (is_bool($v)) {
                $v = $v ? 'true' : 'false';
            }
            // Se bater com a restrição, já retorna verdadeiro e sai da função
            if ((string)$v === (string)$valor_esperado) {
                return true;
            }
        }

        return false;
    }


    /**
     * Busca os cursos disponíveis para autoinscrição e aplica os filtros do perfil do usuário.
     */
    private function get_autoinscricoes($userid, $all_diarios) 
    {
        global $DB, $CFG;
        $autoinscricoes = [];

        $campo_sala = $DB->get_record('customfield_field', ['shortname' => 'sala_tipo']);
        if (!$campo_sala) return $autoinscricoes;

        // Cursos visíveis marcados com "autoinscricoes"
        $sql_vitrine = "SELECT c.id, c.fullname, c.shortname
                        FROM {course} c
                        JOIN {customfield_data} d ON d.instanceid = c.id
                        WHERE d.fieldid = ? AND d.charvalue = ? AND c.visible = 1";
                        
        $cursos_vitrine = $DB->get_records_sql($sql_vitrine, [$campo_sala->id, 'autoinscricoes']);
        if (empty($cursos_vitrine)) return $autoinscricoes;
            
        // JSON do aluno logado
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

        // Busca a NOVA regra: 'restricoes_de_autoinscricao' e os campos customizados
        $vitrine_ids = array_column($cursos_vitrine, 'id');
        list($v_insql, $v_inparams) = $DB->get_in_or_equal($vitrine_ids);
        
        $sql_cf_vitrine = "SELECT d.id AS dataid, d.instanceid, f.shortname, d.value, d.charvalue
                            FROM {customfield_data} d
                            JOIN {customfield_field} f ON d.fieldid = f.id
                            WHERE d.instanceid $v_insql
                                AND f.shortname IN ('restricoes_de_autoinscricao', 'turma_ano_periodo', 'disciplina_id', 'disciplina_sigla', 'curso_codigo', 'curso_descricao', 'diario_id')";
        
        $cf_vitrine_records = $DB->get_records_sql($sql_cf_vitrine, $v_inparams);
        
        $cf_vitrine = [];
        if ($cf_vitrine_records) {
            foreach ($cf_vitrine_records as $rec) {
                $cf_vitrine[$rec->instanceid][$rec->shortname] = $rec->value ?: $rec->charvalue;
            }
        }

        $mapa_matriculados = [];
        foreach ($all_diarios as $diario_aluno) {
            $mapa_matriculados[$diario_aluno->id] = true;
        }

        // Avalia curso por curso
        foreach ($cursos_vitrine as $curso_vitrine) {

            $json_restricoes_str = $cf_vitrine[$curso_vitrine->id]['restricoes_de_autoinscricao'] ?? '';

            $texto_limpo_restricoes = html_entity_decode(strip_tags($json_restricoes_str), ENT_QUOTES, 'UTF-8');
            $restricoes = json_decode($texto_limpo_restricoes, true);

            $this->inject_custom_fields($curso_vitrine, $cf_vitrine[$curso_vitrine->id] ?? []);

            $passou_nos_filtros = false;

            if (empty($restricoes)) {
                // SE NÃO TEM RESTRIÇÃO: O curso é aberto para todos
                $passou_nos_filtros = true;
            } else {
                // SE TEM RESTRIÇÃO: O aluno precisa atender a pelo menos UMA
                foreach ($restricoes as $regra) {
                    $chave = $regra['chave'] ?? '';
                    $valor_esperado = $regra['restricao'] ?? '';
                    
                    if ($this->avalia_restricao($aluno_data, $chave, $valor_esperado)) {
                        $passou_nos_filtros = true;
                        break;
                    }
                }
            }

            if ($passou_nos_filtros) {
                $curso_vitrine->is_enrolled = isset($mapa_matriculados[$curso_vitrine->id]);
                $curso_vitrine->viewurl = $CFG->wwwroot . '/course/view.php?id=' . $curso_vitrine->id;
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
            $coursecontext = \context_course::instance($diario->id);

            $curso_limpo = new \stdClass();
            $curso_limpo->id = $diario->id;
            $curso_limpo->fullname = $diario->fullname;
            $curso_limpo->shortname = $diario->shortname;
            $curso_limpo->viewurl = $diario->viewurl;
            $curso_limpo->progress = $diario->progress ?? null;
            $curso_limpo->hasprogress = $diario->hasprogress ?? false;
            $curso_limpo->isfavourite = $diario->isfavourite ?? false;
            $curso_limpo->hidden = $diario->hidden ?? false;
            $curso_limpo->can_set_visibility = has_capability('moodle/course:visibility', $coursecontext, $USER) ? 1 : 0;

            $sql = "SELECT f.shortname, d.intvalue, d.shortcharvalue, d.charvalue, d.value, f.type, f.configdata
                    FROM {customfield_data} d
                    JOIN {customfield_field} f ON d.fieldid = f.id
                    WHERE d.instanceid = ?";
            $cf_records = $DB->get_records_sql($sql, [$diario->id]);

            $cf = new \stdClass();
            foreach ($cf_records as $record) {
                $cf->{$record->shortname} = $record->value ?: $record->charvalue ?: $record->shortcharvalue;
            }

            $this->inject_custom_fields($curso_limpo, $cf);

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
                        ((empty($q)) || (!empty($q) && strpos(strtoupper($curso_limpo->shortname . ' ' . $curso_limpo->fullname), strtoupper($q)) !== false)) &&
                        ((empty($semestre)) || (!empty($semestre) && $c_semestre == $semestre)) &&
                        ((empty($disciplina)) || (!empty($disciplina) && $c_disciplina == $disciplina)) &&
                        ((empty($curso)) || (!empty($curso) && $c_curso == $curso))
                    ) {
                        $agrupamentos[$sala_tipo][] = $curso_limpo;
                    }
                } else {
                    $agrupamentos[$sala_tipo][] = $curso_limpo;
                }
            } else {
                // Outros tipos de sala entram sem filtros de busca
                $agrupamentos[$sala_tipo][] = $curso_limpo;
            }
        }

        $autoinscricoes = $this->get_autoinscricoes($USER->id, $all_diarios);

        $return_base = [
            "semestres" => $this->get_semestres($all_diarios),
            "disciplinas" => $this->get_disciplinas($all_diarios),
            "cursos" => $this->get_cursos($all_diarios),
            "autoinscricoes" => $autoinscricoes,
        ];

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
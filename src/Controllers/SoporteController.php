<?php
declare(strict_types=1);

require_once BASE_PATH . '/lib/SafeQuery.php';

class SoporteController {
    use \SafeQuery;
    
    private $core;

    public function __construct() {
        Auth::guard();
        $this->core = EstrateGiaCore::getInstance();
    }

    public function index(): void {
        $eid = (int)($_COOKIE['empresa_activa'] ?? 1);

        $abiertos = (int)$this->safe("SELECT COUNT(*) FROM soporte_tickets WHERE id_empresa=? AND estado='ABIERTO'", [$eid]);
        $enProgreso = (int)$this->safe("SELECT COUNT(*) FROM soporte_tickets WHERE id_empresa=? AND estado='EN_PROGRESO'", [$eid]);
        $resueltosHoy = (int)$this->safe("SELECT COUNT(*) FROM soporte_tickets WHERE id_empresa=? AND estado IN('RESUELTO','CERRADO') AND DATE(fecha_resolucion)=CURDATE()", [$eid]);
        $totalCerrados = max((int)$this->safe("SELECT COUNT(*) FROM soporte_tickets WHERE id_empresa=? AND estado IN('RESUELTO','CERRADO')", [$eid]), 1);
        $dentroSLA = (int)$this->safe("SELECT COUNT(*) FROM soporte_tickets WHERE id_empresa=? AND estado IN('RESUELTO','CERRADO') AND fecha_resolucion <= fecha_limite_sla", [$eid]);
        $slaCumplido = round(($dentroSLA / $totalCerrados) * 100, 1);
        $avgMin = (float)$this->safe("SELECT AVG(TIMESTAMPDIFF(MINUTE, fecha_creacion, fecha_resolucion)) FROM soporte_tickets WHERE id_empresa=? AND estado IN('RESUELTO','CERRADO') AND fecha_resolucion IS NOT NULL", [$eid]);
        $avgHoras = round($avgMin / 60, 1);
        $ticketsPrioridad = $this->safeAll("SELECT prioridad, COUNT(*) as total FROM soporte_tickets WHERE id_empresa=? AND estado NOT IN('CERRADO') GROUP BY prioridad ORDER BY FIELD(prioridad,'CRITICA','ALTA','MEDIA','BAJA')", [$eid]);
        $recientes = $this->safeAll("SELECT t.*, u.usuario_nombre as asignado_nombre FROM soporte_tickets t LEFT JOIN sys_usuarios u ON t.asignado_a=u.usuario_id WHERE t.id_empresa=? ORDER BY COALESCE(t.updated_at,t.created_at) DESC LIMIT 15", [$eid]);

        $pageTitle = 'Soporte N1 — Mesa de Ayuda';
        ob_start(); require BASE_PATH . '/templates/soporte/index.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function tickets(): void {
        $eid = (int)($_COOKIE['empresa_activa'] ?? 1);
        $estado = $_GET['estado'] ?? '';
        $prioridad = $_GET['prioridad'] ?? '';
        $modulo = $_GET['modulo'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));

        $sql = "SELECT t.*, u.usuario_nombre as asignado_nombre FROM soporte_tickets t LEFT JOIN sys_usuarios u ON t.asignado_a=u.usuario_id WHERE t.id_empresa=?";
        $params = [$eid];
        if ($estado) { $sql .= " AND t.estado=?"; $params[] = $estado; }
        if ($prioridad) { $sql .= " AND t.prioridad=?"; $params[] = $prioridad; }
        if ($modulo) { $sql .= " AND t.modulo_afectado=?"; $params[] = $modulo; }
        $sql .= " ORDER BY FIELD(t.prioridad,'CRITICA','ALTA','MEDIA','BAJA'), COALESCE(t.updated_at,t.created_at) DESC";

        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) FROM soporte_tickets WHERE id_empresa=?";
        $countParams = [$eid];
        if ($estado) { $countSql .= " AND estado=?"; $countParams[] = $estado; }
        if ($prioridad) { $countSql .= " AND prioridad=?"; $countParams[] = $prioridad; }
        if ($modulo) { $countSql .= " AND modulo_afectado=?"; $countParams[] = $modulo; }
        $total = (int)$this->safe($countSql, $countParams);
        $totalPages = max(1, (int)ceil($total / $perPage));

        $tickets = $this->safeAll($sql . " LIMIT $perPage OFFSET $offset", $params);
        $modulos = $this->safeAll("SELECT DISTINCT modulo_afectado FROM soporte_tickets WHERE modulo_afectado IS NOT NULL AND modulo_afectado != '' ORDER BY modulo_afectado");
        $modulos = array_column($modulos, 'modulo_afectado');

        $pageTitle = 'Tickets — Soporte';
        ob_start(); require BASE_PATH . '/templates/soporte/tickets.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crearTicket(): void {
        $eid = (int)($_COOKIE['empresa_activa'] ?? 1);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $asunto = trim($_POST['asunto'] ?? '');
            $modulo = trim($_POST['modulo'] ?? '');
            $prioridad = trim($_POST['prioridad'] ?? 'MEDIA');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $creadoPor = $_SESSION['auth_user']['usuario_nombre'] ?? Auth::userName();

            if ($asunto) {
                $slaHoras = ['CRITICA' => 1, 'ALTA' => 4, 'MEDIA' => 8, 'BAJA' => 24][$prioridad] ?? 8;
                $slaDeadline = date('Y-m-d H:i:s', time() + ($slaHoras * 3600));

                $ticketId = $this->safeInsert('soporte_tickets', [
                    'id_empresa' => $eid,
                    'modulo_afectado' => $modulo,
                    'asunto' => $asunto,
                    'descripcion' => $descripcion,
                    'prioridad' => $prioridad,
                    'estado' => 'ABIERTO',
                    'nivel_actual' => 'N1',
                    'origen' => 'USUARIO',
                    'creado_por' => $creadoPor,
                    'fecha_limite_sla' => $slaDeadline
                ]);

                if ($prioridad === 'CRITICA') {
                    $this->notificarCritica($ticketId, $asunto);
                }

                $this->core->audit('INSERT', 'soporte_tickets', $ticketId, null, $_POST, "Ticket #$ticketId creado por $creadoPor");

                header('Location: /soporte/ver/' . $ticketId . '?created=1'); exit;
            }
        }

        $modulosDisponibles = ['planeacion', 'indicadores', 'procesos', 'documentos', 'calidad', 'sst', 'ambiental', 'evaluacion', 'nc', 'proveedores', 'crm', 'sistema'];
        $pageTitle = 'Crear Ticket — Soporte';
        ob_start(); require BASE_PATH . '/templates/soporte/crear.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function verTicket(int $id): void {
        $eid = (int)($_COOKIE['empresa_activa'] ?? 1);

        $ticket = $this->safeOne("SELECT t.*, u.usuario_nombre as asignado_nombre FROM soporte_tickets t LEFT JOIN sys_usuarios u ON t.asignado_a=u.usuario_id WHERE t.id=? AND t.id_empresa=?", [$id, $eid]);
        if (!$ticket) { die("Ticket no encontrado"); }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['_accion'] ?? '';
            $autor = $_SESSION['auth_user']['usuario_nombre'] ?? Auth::userName();
            $uid = Auth::userId();

            if ($accion === 'responder' && !empty($_POST['respuesta'])) {
                $this->safeInsert('soporte_respuestas', [
                    'ticket_id' => $id,
                    'id_ticket' => $id,
                    'tipo' => 'RESPUESTA',
                    'contenido' => trim($_POST['respuesta']),
                    'autor' => $autor
                ]);
                if ($ticket['estado'] === 'ABIERTO') {
                    $this->safeExec("UPDATE soporte_tickets SET estado='EN_PROGRESO' WHERE id=?", [$id]);
                }
                header("Location: /soporte/ver/$id"); exit;
            }
            if ($accion === 'cerrar' && !empty($_POST['resolucion'])) {
                $now = date('Y-m-d H:i:s');
                $minutos = $ticket['fecha_creacion'] ? round((strtotime($now) - strtotime($ticket['fecha_creacion'])) / 60) : null;
                $this->safeExec("UPDATE soporte_tickets SET estado='CERRADO', resolucion=?, fecha_resolucion=?, resuelto_por=?, tiempo_resolucion_min=? WHERE id=?",
                    [trim($_POST['resolucion']), $now, $autor, $minutos, $id]);
                $this->safeInsert('soporte_respuestas', [
                    'ticket_id' => $id,
                    'id_ticket' => $id,
                    'tipo' => 'CIERRE',
                    'contenido' => trim($_POST['resolucion']),
                    'autor' => $autor
                ]);
                header("Location: /soporte/ver/$id"); exit;
            }
            if ($accion === 'escalar') {
                $nivel = $_POST['nivel_escalar'] ?? 'N2';
                $estadoNuevo = $nivel === 'N3' ? 'ESCALADO_N3' : 'ESCALADO_N2';
                $slaExtra = $nivel === 'N3' ? 24 : 8;
                $newSLA = date('Y-m-d H:i:s', time() + ($slaExtra * 3600));
                $this->safeExec("UPDATE soporte_tickets SET estado=?, nivel_actual=?, fecha_limite_sla=? WHERE id=?",
                    [$estadoNuevo, $nivel, $newSLA, $id]);
                $this->safeInsert('soporte_respuestas', [
                    'ticket_id' => $id,
                    'id_ticket' => $id,
                    'tipo' => 'ESCALACION',
                    'contenido' => "Escalado a nivel $nivel. Nuevo SLA: $newSLA",
                    'autor' => $autor
                ]);
                header("Location: /soporte/ver/$id"); exit;
            }
            if ($accion === 'asignar' && !empty($_POST['asignar_a'])) {
                $asignar = (int)$_POST['asignar_a'];
                if (!$ticket['asignado_a']) {
                    $this->safeExec("UPDATE soporte_tickets SET asignado_a=?, estado='EN_PROGRESO' WHERE id=?", [$asignar, $id]);
                    $this->safeInsert('soporte_respuestas', [
                        'ticket_id' => $id,
                        'id_ticket' => $id,
                        'tipo' => 'NOTA_INTERNA',
                        'contenido' => "Ticket asignado a tecnico #$asignar",
                        'autor' => $autor
                    ]);
                }
                header("Location: /soporte/ver/$id"); exit;
            }
        }

        $respuestas = $this->safeAll("SELECT * FROM soporte_respuestas WHERE ticket_id=? ORDER BY created_at ASC", [$id]);
        $techs = $this->safeAll("SELECT usuario_id, usuario_nombre, usuario_apellido FROM sys_usuarios WHERE usuario_activo=1 ORDER BY usuario_nombre");

        $pageTitle = "Ticket #$id — Soporte";
        ob_start(); require BASE_PATH . '/templates/soporte/ver.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function kb(): void {
        $search = $_GET['q'] ?? '';
        $moduloFilter = $_GET['modulo'] ?? '';

        $sql = "SELECT * FROM portal_kb WHERE 1=1";
        $params = [];
        if ($search) { $sql .= " AND MATCH(titulo, contenido, tags) AGAINST(? IN BOOLEAN MODE)"; $params[] = $search; }
        if ($moduloFilter) { $sql .= " AND modulo=?"; $params[] = $moduloFilter; }
        $sql .= " ORDER BY vistas DESC, updated_at DESC LIMIT 50";

        $articulos = $this->safeAll($sql, $params);
        $modulosRaw = $this->safeAll("SELECT DISTINCT modulo FROM portal_kb WHERE modulo IS NOT NULL AND modulo != '' ORDER BY modulo");
        $modulos = array_column($modulosRaw, 'modulo');

        $pageTitle = 'Base de Conocimiento';
        ob_start(); require BASE_PATH . '/templates/soporte/kb.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function kbCrear(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titulo = trim($_POST['titulo'] ?? '');
            $contenido = trim($_POST['contenido'] ?? '');
            $modulo = trim($_POST['modulo'] ?? '');
            $tags = trim($_POST['tags'] ?? '');

            if ($titulo && $contenido) {
                $this->safeInsert('portal_kb', [
                    'titulo' => $titulo,
                    'contenido' => $contenido,
                    'modulo' => $modulo,
                    'tags' => $tags
                ]);
                header('Location: /soporte/kb?created=1'); exit;
            }
        }

        $pageTitle = 'Nuevo Articulo — KB';
        ob_start(); require BASE_PATH . '/templates/soporte/kb_crear.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function kbArticulo(int $id): void {
        $a = $this->safeOne("SELECT * FROM portal_kb WHERE id=?", [$id]);
        if (!$a) { die("Articulo no encontrado"); }

        $this->safeExec("UPDATE portal_kb SET vistas = vistas + 1 WHERE id=?", [$id]);

        $pageTitle = htmlspecialchars($a['titulo']) . ' — KB';
        ob_start(); require BASE_PATH . '/templates/soporte/kb_articulo.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function reporteSLA(): void {
        $mes = $_GET['mes'] ?? date('Y-m');
        $inicio = $mes . '-01';
        $fin = date('Y-m-t', strtotime($inicio));
        $eid = (int)($_COOKIE['empresa_activa'] ?? 1);
        $fechaInicio = $inicio . ' 00:00:00';
        $fechaFin = $fin . ' 23:59:59';

        $totalTickets = max((int)$this->safe("SELECT COUNT(*) FROM soporte_tickets WHERE id_empresa=? AND created_at BETWEEN ? AND ?", [$eid, $fechaInicio, $fechaFin]), 1);
        $cerrados = (int)$this->safe("SELECT COUNT(*) FROM soporte_tickets WHERE id_empresa=? AND estado IN('RESUELTO','CERRADO') AND fecha_resolucion BETWEEN ? AND ?", [$eid, $fechaInicio, $fechaFin]);
        $dentroSLA = (int)$this->safe("SELECT COUNT(*) FROM soporte_tickets WHERE id_empresa=? AND estado IN('RESUELTO','CERRADO') AND fecha_resolucion <= fecha_limite_sla AND fecha_resolucion BETWEEN ? AND ?", [$eid, $fechaInicio, $fechaFin]);
        $slaPct = round(($dentroSLA / max($cerrados, 1)) * 100, 1);
        $promRes = round((float)$this->safe("SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_resolucion)) FROM soporte_tickets WHERE id_empresa=? AND estado IN('RESUELTO','CERRADO') AND fecha_resolucion BETWEEN ? AND ?", [$eid, $fechaInicio, $fechaFin]), 1);
        $porPrioridad = $this->safeAll("SELECT prioridad, COUNT(*) as total, SUM(CASE WHEN estado IN('RESUELTO','CERRADO') AND fecha_resolucion <= fecha_limite_sla THEN 1 ELSE 0 END) as dentro_sla FROM soporte_tickets WHERE id_empresa=? AND created_at BETWEEN ? AND ? GROUP BY prioridad", [$eid, $fechaInicio, $fechaFin]);

        $pageTitle = 'Reporte SLA — Soporte';
        ob_start(); require BASE_PATH . '/templates/soporte/reporte_sla.php'; $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    private function notificarCritica(int $ticketId, string $asunto): void {
        try {
            $admins = $this->safeAll("SELECT usuario_email FROM sys_usuarios WHERE usuario_rol_id=1 AND usuario_activo=1 AND usuario_email IS NOT NULL");
            foreach ($admins as $admin) {
                if (!empty($admin['usuario_email'])) {
                    @mail($admin['usuario_email'], "CRITICA: Ticket #$ticketId - $asunto", "Se ha creado un ticket con prioridad CRITICA.\nTicket: #$ticketId\nAsunto: $asunto\nHora: " . date('Y-m-d H:i:s') . "\n\nAtienda en: " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/soporte/ver/$ticketId");
                }
            }
        } catch (\Exception $e) {}
    }
}

<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class DocumentosController {
    use \SafeQuery;
    private $core;

    public function __construct() { Auth::guard(); $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $dm = new DocManager();
        $pm = new PlanManager();

        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);
        $sectorId = $empresa['empresa_sector_id'] ?? null;

        $busqueda = trim($_GET['q'] ?? '');
        $tipo = $_GET['tipo'] ?? '';
        $estado = $_GET['estado'] ?? '';
        $normaId = $_GET['norma_id'] ?? '';
        $procesoId = (int)($_GET['proceso_id'] ?? 0);
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = min(100, max(10, (int)($_GET['por_pagina'] ?? 25)));
        $orden = $_GET['orden'] ?? 'titulo';
        $dir = $_GET['dir'] ?? 'asc';

        $where = ["d.documento_empresa_id = ?", "d.documento_activo = 1"];
        $params = [$empresaId];

        if ($busqueda) {
            $where[] = "(d.documento_titulo LIKE ? OR d.documento_codigo LIKE ? OR d.documento_contenido_html LIKE ?)";
            $params[] = "%$busqueda%"; $params[] = "%$busqueda%"; $params[] = "%$busqueda%";
        }
        if ($tipo) { $where[] = "d.documento_tipo = ?"; $params[] = $tipo; }
        if ($estado) { $where[] = "d.documento_estado = ?"; $params[] = $estado; }
        if ($normaId) { $where[] = "d.documento_norma_id = ?"; $params[] = $normaId; }
        if ($procesoId) { $where[] = "d.documento_proceso_id = ?"; $params[] = $procesoId; }

        $whereStr = implode(' AND ', $where);

        $cols = ['titulo'=>'d.documento_titulo','tipo'=>'d.documento_tipo','estado'=>'d.documento_estado','version'=>'d.documento_version','fecha'=>'d.documento_fecha_aprobacion','codigo'=>'d.documento_codigo'];
        $orderCol = $cols[$orden] ?? 'd.documento_titulo';
        $orderDir = $dir === 'desc' ? 'DESC' : 'ASC';

        $total = $this->safe("SELECT COUNT(*) FROM doc_documentos d WHERE $whereStr", $params);

        $offset = ($pagina - 1) * $porPagina;
        $documentos = $this->safeAll(
            "SELECT d.*, n.norma_codigo, p.proceso_nombre, m.macro_nombre,
                    CONCAT(el.usuario_nombre, ' ', el.usuario_apellido) as elaborado_por
             FROM doc_documentos d
             LEFT JOIN doc_normas_iso n ON d.documento_norma_id = n.norma_id
             LEFT JOIN proc_procesos p ON d.documento_proceso_id = p.proceso_id
             LEFT JOIN proc_macroprocesos m ON p.proceso_macro_id = m.macro_id
             LEFT JOIN sys_usuarios el ON d.documento_elaborado_por = el.usuario_id
             WHERE $whereStr
             ORDER BY $orderCol $orderDir
             LIMIT ? OFFSET ?",
            array_merge($params, [$porPagina, $offset])
        );

        $counts = $this->safeAll(
            "SELECT documento_tipo, COUNT(*) as cnt FROM doc_documentos WHERE documento_empresa_id = ? AND documento_activo = 1 GROUP BY documento_tipo",
            [$empresaId]
        );

        $normas = $dm->getNormas($sectorId);
        $procesos = $this->safeAll(
            'SELECT p.proceso_id, p.proceso_nombre, m.macro_nombre FROM proc_procesos p
             JOIN proc_macroprocesos m ON p.proceso_macro_id = m.macro_id
             WHERE m.macro_empresa_id = ? AND p.proceso_activo = 1 ORDER BY p.proceso_nombre',
            [$empresaId]
        );

        $pageTitle = 'Gestor Documental';
        ob_start(); require BASE_PATH . '/templates/documentos/index.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function crear(): void {
        $dm = new DocManager(); $pm = new PlanManager();
        $empresaId = (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2));
        $empresa = $pm->getEmpresa($empresaId);
        $normas = $dm->getNormas($empresa['empresa_sector_id'] ?? null);
        $procesos = $this->safeAll(
            'SELECT p.proceso_id, p.proceso_nombre, m.macro_nombre FROM proc_procesos p
             JOIN proc_macroprocesos m ON p.proceso_macro_id = m.macro_id
             WHERE m.macro_empresa_id = ? AND p.proceso_activo = 1 ORDER BY m.macro_tipo, p.proceso_nombre',
            [$empresaId]
        );
        $pageTitle = 'Nuevo Documento';
        ob_start(); require BASE_PATH . '/templates/documentos/crear.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function store(): void {
        $dm = new DocManager();
        $empresaId = (int)$_POST['empresa_id'];
        $procesoId = $_POST['proceso_id'] ?? null;

        $uploadDir = BASE_PATH . '/uploads/' . $empresaId;
        if ($procesoId) $uploadDir .= '/proceso_' . $procesoId;
        $tipo = $_POST['tipo'];
        $uploadDir .= '/' . $tipo;

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $archivoUrl = null;
        if (!empty($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
            $filename = date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $_FILES['archivo']['name']);
            $filepath = $uploadDir . '/' . $filename;
            if (move_uploaded_file($_FILES['archivo']['tmp_name'], $filepath)) {
                $archivoUrl = '/uploads/' . $empresaId . ($procesoId ? '/proceso_' . $procesoId : '') . '/' . $tipo . '/' . $filename;
            }
        }

        $id = $dm->createDocumento([
            'documento_empresa_id' => $empresaId,
            'documento_proceso_id' => $procesoId,
            'documento_norma_id' => $_POST['norma_id'] ?? null,
            'documento_codigo' => $_POST['codigo'] ?? '',
            'documento_titulo' => $_POST['titulo'],
            'documento_tipo' => $tipo,
            'documento_contenido_html' => $_POST['contenido'] ?? '',
            'documento_archivo_url' => $archivoUrl,
            'documento_elaborado_por' => Auth::userId(),
        ]);
        header('Location: /documentos?created=1'); exit;
    }

    public function proceso(int $id): void {
        $dm = new DocManager();
        $pm = new PlanManager();

        $proceso = $this->safeOne(
            "SELECT p.*, m.macro_nombre, m.macro_tipo, m.macro_empresa_id FROM proc_procesos p
             JOIN proc_macroprocesos m ON p.proceso_macro_id = m.macro_id WHERE p.proceso_id = ?",
            [$id]
        );
        if (!$proceso) { http_response_code(404); echo 'No encontrado'; return; }

        $empresa = $pm->getEmpresa($proceso['macro_empresa_id']);
        $documentos = $dm->getDocumentos($empresa['empresa_id'], $id);

        $porTipo = [];
        foreach ($documentos as $doc) {
            $porTipo[$doc['documento_tipo']][] = $doc;
        }

        $pageTitle = htmlspecialchars($proceso['proceso_nombre']);
        ob_start(); require BASE_PATH . '/templates/documentos/proceso.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function ver(int $id): void {
        $dm = new DocManager();
        $doc = $dm->getDocumento($id);
        if (!$doc) { http_response_code(404); echo 'No encontrado'; return; }
        $pageTitle = htmlspecialchars($doc['documento_titulo']);
        ob_start(); require BASE_PATH . '/templates/documentos/ver.php';
        $content = ob_get_clean(); require BASE_PATH . '/templates/layout.php';
    }

    public function aprobar(int $id): void {
        (new DocManager())->aprobarDocumento($id, Auth::userId());
        EstrateGiaCore::getInstance()->logAction(Auth::userId(), 'aprobar', 'documentacion', 'documento', $id);
        header('Location: /documentos?approved=1'); exit;
    }

    public function publicar(int $id): void {
        (new DocManager())->publicarDocumento($id);
        header('Location: /documentos?published=1'); exit;
    }

    public function nuevaVersion(int $id): void {
        (new DocManager())->crearNuevaVersion($id, $_POST['contenido'] ?? '', Auth::userId());
        header('Location: /documentos/ver/' . $id . '?version=ok'); exit;
    }
}

<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class LicenciasController {
    use \SafeQuery;
    private $core;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }

    public function index(): void {
        Auth::guard();
        if (($_SESSION['auth_user']['rol_nombre'] ?? '') !== 'SUPER_ADMIN') { http_response_code(403); die("Acceso denegado"); }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $total = (int)($this->safeOne("SELECT COUNT(*) as cnt FROM licencias")['cnt'] ?? 0);
        $pages = max(1, (int)ceil($total / $limit));

        $licencias = $this->safeAll(
            "SELECT l.*, e.empresa_razon_social FROM licencias l LEFT JOIN sys_empresas e ON l.id_empresa = e.empresa_id ORDER BY l.created_at DESC LIMIT {$limit} OFFSET {$offset}"
        ) ?: [];

        $pageTitle = 'Licencias';
        ob_start();
        ?>
        <div class="card-box">
            <div class="card-box-header">
                <span><i class="fas fa-key me-2"></i>Licencias — Gestion Comercial</span>
                <a href="/licencias/crear" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Nueva Licencia</a>
            </div>
            <div class="card-box-body" style="padding:0">
                <table class="table-box">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Empresa</th>
                            <th>Plan</th>
                            <th>Usuarios Max</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Activa</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($licencias)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No hay licencias registradas</td></tr>
                        <?php else: foreach ($licencias as $l): ?>
                        <tr>
                            <td>#<?= $l['id'] ?></td>
                            <td><?= htmlspecialchars($l['empresa_razon_social'] ?? 'Empresa #'.$l['id_empresa']) ?></td>
                            <td><span class="badge-status"><?= $l['plan'] ?></span></td>
                            <td><?= $l['usuarios_max'] ?></td>
                            <td><?= $l['fecha_inicio'] ?></td>
                            <td><?= $l['fecha_fin'] ?></td>
                            <td><span class="badge-status badge-<?= $l['activa'] ? 'ACTIVO' : 'INACTIVO' ?>"><?= $l['activa'] ? 'Si' : 'No' ?></span></td>
                            <td class="text-end">
                                <a href="/licencias/editar/<?= $l['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <?php if ($pages > 1): ?>
                <div class="p-2 d-flex justify-content-center">
                    <nav><ul class="pagination pagination-sm mb-0">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                    </ul></nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function create(): void {
        Auth::guard();
        if (($_SESSION['auth_user']['rol_nombre'] ?? '') !== 'SUPER_ADMIN') { http_response_code(403); die("Acceso denegado"); }

        $empresas = $this->safeAll("SELECT empresa_id, empresa_razon_social FROM sys_empresas WHERE empresa_estado='ACTIVO' ORDER BY empresa_razon_social") ?: [];
        $modulos = ['planeacion','workbench','indicadores','evaluacion','procesos','calidad','sst','ambiental','nc','documentos','proveedores','crm','ia','soporte','financiero','admin','config'];

        $pageTitle = 'Nueva Licencia';
        ob_start();
        ?>
        <div class="card-box" style="max-width:800px">
            <div class="card-box-header"><span><i class="fas fa-plus me-2"></i>Crear Licencia</span></div>
            <div class="card-box-body">
                <form method="POST" action="/licencias/crear">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Empresa</label>
                            <select name="id_empresa" class="form-select" required>
                                <option value="">— Seleccione —</option>
                                <?php foreach ($empresas as $e): ?>
                                <option value="<?= $e['empresa_id'] ?>"><?= htmlspecialchars($e['empresa_razon_social']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plan</label>
                            <select name="plan" class="form-select" required>
                                <option value="BASICO">BASICO</option>
                                <option value="ESTANDAR">ESTANDAR</option>
                                <option value="AVANZADO">AVANZADO</option>
                                <option value="EMPRESARIAL">EMPRESARIAL</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Usuarios Max</label>
                            <input type="number" name="usuarios_max" class="form-control" value="5" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Activa</label>
                            <select name="activa" class="form-select">
                                <option value="1">Si</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Modulos Activos</label>
                            <div class="row g-2">
                                <?php foreach ($modulos as $m): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="modulos[]" value="<?= $m ?>" checked id="mod_<?= $m ?>">
                                        <label class="form-check-label" for="mod_<?= $m ?>"><?= ucfirst($m) ?></label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Licencia</button>
                        <a href="/licencias" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function store(): void {
        Auth::guard();
        if (($_SESSION['auth_user']['rol_nombre'] ?? '') !== 'SUPER_ADMIN') { http_response_code(403); die("Acceso denegado"); }

        $idEmpresa = (int)($_POST['id_empresa'] ?? 0);
        $plan = $_POST['plan'] ?? 'BASICO';
        $usuariosMax = (int)($_POST['usuarios_max'] ?? 5);
        $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d', strtotime('+1 year'));
        $activa = (int)($_POST['activa'] ?? 1);
        $modulos = $_POST['modulos'] ?? [];
        $modulosJson = json_encode($modulos, JSON_UNESCAPED_UNICODE);
        $token = bin2hex(random_bytes(32));

        if ($idEmpresa <= 0) { $_SESSION['flash_error'] = 'Seleccione una empresa.'; header('Location: /licencias/crear'); exit; }

        try {
            $this->safeExec(
                "INSERT INTO licencias (id_empresa, app, plan, usuarios_max, modulos_activos, fecha_inicio, fecha_fin, activa, token_licencia) VALUES (?,?,?,?,?,?,?,?,?)",
                [$idEmpresa, 'EstrateGIA', $plan, $usuariosMax, $modulosJson, $fechaInicio, $fechaFin, $activa, $token]
            );
            $_SESSION['flash_success'] = 'Licencia creada exitosamente.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error al crear: ' . $e->getMessage();
        }
        header('Location: /licencias'); exit;
    }

    public function edit(int $id): void {
        Auth::guard();
        if (($_SESSION['auth_user']['rol_nombre'] ?? '') !== 'SUPER_ADMIN') { http_response_code(403); die("Acceso denegado"); }

        $lic = $this->safeOne(
            "SELECT l.*, e.empresa_razon_social FROM licencias l LEFT JOIN sys_empresas e ON l.id_empresa = e.empresa_id WHERE l.id = ?", [$id]
        );
        if (!$lic) { header('Location: /licencias'); exit; }

        $modulosActivos = json_decode($lic['modulos_activos'] ?? '[]', true) ?: [];
        $todosModulos = ['planeacion','workbench','indicadores','evaluacion','procesos','calidad','sst','ambiental','nc','documentos','proveedores','crm','ia','soporte','financiero','admin','config'];
        $empresas = $this->safeAll("SELECT empresa_id, empresa_razon_social FROM sys_empresas WHERE empresa_estado='ACTIVO' ORDER BY empresa_razon_social") ?: [];

        $pageTitle = 'Editar Licencia';
        ob_start();
        ?>
        <div class="card-box" style="max-width:800px">
            <div class="card-box-header"><span><i class="fas fa-edit me-2"></i>Editar Licencia #<?= $lic['id'] ?> — <?= htmlspecialchars($lic['empresa_razon_social'] ?? '') ?></span></div>
            <div class="card-box-body">
                <form method="POST" action="/licencias/editar/<?= $lic['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Empresa</label>
                            <select name="id_empresa" class="form-select" required>
                                <?php foreach ($empresas as $e): ?>
                                <option value="<?= $e['empresa_id'] ?>" <?= (int)$e['empresa_id'] === (int)$lic['id_empresa'] ? 'selected' : '' ?>><?= htmlspecialchars($e['empresa_razon_social']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plan</label>
                            <select name="plan" class="form-select" required>
                                <?php foreach (['BASICO','ESTANDAR','AVANZADO','EMPRESARIAL'] as $p): ?>
                                <option value="<?= $p ?>" <?= $lic['plan'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Usuarios Max</label>
                            <input type="number" name="usuarios_max" class="form-control" value="<?= $lic['usuarios_max'] ?>" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?= $lic['fecha_inicio'] ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="<?= $lic['fecha_fin'] ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Activa</label>
                            <select name="activa" class="form-select">
                                <option value="1" <?= $lic['activa'] ? 'selected' : '' ?>>Si</option>
                                <option value="0" <?= !$lic['activa'] ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Token (solo lectura)</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($lic['token_licencia']) ?>" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Modulos Activos</label>
                            <div class="row g-2">
                                <?php foreach ($todosModulos as $m): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="modulos[]" value="<?= $m ?>" <?= in_array($m, $modulosActivos) ? 'checked' : '' ?> id="mod_<?= $m ?>">
                                        <label class="form-check-label" for="mod_<?= $m ?>"><?= ucfirst($m) ?></label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Actualizar</button>
                        <a href="/licencias" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }

    public function update(int $id): void {
        Auth::guard();
        if (($_SESSION['auth_user']['rol_nombre'] ?? '') !== 'SUPER_ADMIN') { http_response_code(403); die("Acceso denegado"); }

        $idEmpresa = (int)($_POST['id_empresa'] ?? 0);
        $plan = $_POST['plan'] ?? 'BASICO';
        $usuariosMax = (int)($_POST['usuarios_max'] ?? 5);
        $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d', strtotime('+1 year'));
        $activa = (int)($_POST['activa'] ?? 1);
        $modulos = $_POST['modulos'] ?? [];
        $modulosJson = json_encode($modulos, JSON_UNESCAPED_UNICODE);

        try {
            $this->safeExec(
                "UPDATE licencias SET id_empresa=?, plan=?, usuarios_max=?, modulos_activos=?, fecha_inicio=?, fecha_fin=?, activa=? WHERE id=?",
                [$idEmpresa, $plan, $usuariosMax, $modulosJson, $fechaInicio, $fechaFin, $activa, $id]
            );
            $_SESSION['flash_success'] = 'Licencia actualizada exitosamente.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error al actualizar: ' . $e->getMessage();
        }
        header('Location: /licencias'); exit;
    }

    public static function checkLicense(int $idEmpresa): array {
        $core = EstrateGiaCore::getInstance();
        $lic = $core->fetchOne(
            "SELECT * FROM licencias WHERE id_empresa = ? AND activa = 1 ORDER BY fecha_fin DESC LIMIT 1", [$idEmpresa]
        );

        if (!$lic) {
            return ['valid' => false, 'readonly' => true, 'reason' => 'no_license'];
        }

        $hoy = date('Y-m-d');
        if ($lic['fecha_fin'] < $hoy) {
            $diasVencido = (int)((strtotime($hoy) - strtotime($lic['fecha_fin'])) / 86400);
            if ($diasVencido > 30) {
                return ['valid' => false, 'readonly' => false, 'blocked' => true, 'reason' => 'expired_over_30_days'];
            }
            if ($diasVencido > 15) {
                return ['valid' => false, 'readonly' => true, 'reason' => 'expired_readonly'];
            }
            return ['valid' => false, 'readonly' => false, 'reason' => 'expired_grace'];
        }

        $modulos = json_decode($lic['modulos_activos'] ?? '[]', true);
        return [
            'valid' => true,
            'readonly' => false,
            'plan' => $lic['plan'],
            'usuarios_max' => (int)$lic['usuarios_max'],
            'modulos' => $modulos ?: [],
            'fecha_fin' => $lic['fecha_fin'],
            'token' => $lic['token_licencia'],
        ];
    }
}

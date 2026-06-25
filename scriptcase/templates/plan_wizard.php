<?php
/**
 * EstrateGIA - Wizard de Creación de Plan Estratégico
 * Guía paso a paso para crear un plan con la metodología seleccionada.
 */

require_once __DIR__ . '/../../lib/PlanManager.php';
require_once __DIR__ . '/../../lib/DocManager.php';

$planManager = new PlanManager();
$docManager = new DocManager();

$empresas = $planManager->getEmpresas();
$metodologias = $planManager->getMetodologias();
$sectores = $docManager->getSectores();

$step = $_GET['step'] ?? 1;
$step = max(1, min(5, (int)$step));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EstrateGIA - Nuevo Plan Estratégico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .wizard-container { max-width: 900px; margin: 30px auto; }
        .wizard-steps { display: flex; margin-bottom: 30px; position: relative; }
        .wizard-step {
            flex: 1; text-align: center; position: relative; z-index: 1;
        }
        .wizard-step .step-number {
            width: 40px; height: 40px; border-radius: 50%; background: #dee2e6;
            color: #666; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 8px; font-weight: 700; font-size: 1.1rem;
        }
        .wizard-step.active .step-number { background: #1a73e8; color: #fff; }
        .wizard-step.completed .step-number { background: #28a745; color: #fff; }
        .wizard-step .step-label { font-size: 0.85rem; color: #888; font-weight: 500; }
        .wizard-step.active .step-label { color: #1a73e8; font-weight: 700; }
        .wizard-line {
            position: absolute; top: 20px; left: 0; right: 0; height: 3px;
            background: #dee2e6; z-index: 0;
        }
        .card { border-radius: 12px; border: none; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .card-header {
            background: white; border-bottom: 1px solid #eee; border-radius: 12px 12px 0 0 !important;
            padding: 20px 24px;
        }
        .card-body { padding: 24px; }
        .btn-primary { background: #1a73e8; border: none; padding: 10px 24px; border-radius: 8px; }
        .btn-success { background: #28a745; border: none; padding: 10px 24px; border-radius: 8px; }
        .method-card {
            border: 2px solid #eee; border-radius: 12px; padding: 20px; cursor: pointer;
            transition: all 0.2s; text-align: center; height: 100%;
        }
        .method-card:hover { border-color: #1a73e8; box-shadow: 0 4px 12px rgba(26,115,232,0.1); }
        .method-card.selected { border-color: #1a73e8; background: #f0f7ff; }
        .method-card .method-icon { font-size: 2.5rem; margin-bottom: 12px; color: #1a73e8; }
        .method-card .method-name { font-weight: 700; margin-bottom: 8px; }
        .method-card .method-desc { font-size: 0.85rem; color: #666; }
    </style>
</head>
<body>

<div class="wizard-container">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-bullseye me-2"></i>Crear Plan Estratégico</h4>
        </div>

        <div class="card-body">
            <!-- Wizard Steps Indicator -->
            <div class="wizard-steps" style="position: relative;">
                <div class="wizard-line"></div>
                <?php
                $steps = [
                    1 => ['icon' => 'fa-building', 'label' => 'Empresa'],
                    2 => ['icon' => 'fa-lightbulb', 'label' => 'Metodología'],
                    3 => ['icon' => 'fa-bullseye', 'label' => 'Plan'],
                    4 => ['icon' => 'fa-calendar', 'label' => 'Fechas'],
                    5 => ['icon' => 'fa-check', 'label' => 'Confirmar'],
                ];
                foreach ($steps as $num => $s):
                    $cls = $num < $step ? 'completed' : ($num === $step ? 'active' : '');
                ?>
                <div class="wizard-step <?= $cls ?>">
                    <div class="step-number">
                        <?= $num < $step ? '<i class="fas fa-check"></i>' : "<i class=\"fas {$s['icon']}\"></i>" ?>
                    </div>
                    <div class="step-label"><?= $s['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Step Content -->
            <?php if ($step === 1): ?>
                <h5 class="mb-3">Paso 1: Selecciona la Empresa</h5>
                <div class="row g-3">
                    <?php foreach ($empresas as $empresa): ?>
                    <div class="col-md-6">
                        <div class="method-card" onclick="selectCompany(<?= $empresa['empresa_id'] ?>)">
                            <div class="method-icon"><i class="fas fa-building"></i></div>
                            <div class="method-name"><?= htmlspecialchars($empresa['empresa_nombre']) ?></div>
                            <div class="method-desc">
                                <?= htmlspecialchars($empresa['empresa_razon_social'] ?? '') ?><br>
                                <small>Sector: <?= htmlspecialchars($empresa['sector_nombre'] ?? 'General') ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($step === 2): ?>
                <h5 class="mb-3">Paso 2: Elige la Metodología</h5>
                <div class="row g-3">
                    <?php foreach ($metodologias as $met): ?>
                    <div class="col-md-6">
                        <div class="method-card" onclick="selectMethod(<?= $met['metodologia_id'] ?>)">
                            <div class="method-icon"><i class="fas <?= htmlspecialchars($met['metodologia_icono'] ?? 'fa-circle') ?>"></i></div>
                            <div class="method-name"><?= htmlspecialchars($met['metodologia_nombre']) ?></div>
                            <div class="method-desc"><?= htmlspecialchars(substr($met['metodologia_descripcion'] ?? '', 0, 150)) ?>...</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($step === 3): ?>
                <h5 class="mb-3">Paso 3: Define el Plan</h5>
                <form>
                    <div class="mb-3">
                        <label class="form-label">Nombre del Plan Estratégico *</label>
                        <input type="text" class="form-control" placeholder="Ej: Plan Estratégico 2025-2027">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" rows="3" placeholder="Describe el alcance y propósito del plan..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Responsable del Plan</label>
                        <select class="form-select">
                            <option>Seleccionar responsable...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Presupuesto Total</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" placeholder="0.00">
                        </div>
                    </div>
                </form>
            <?php elseif ($step === 4): ?>
                <h5 class="mb-3">Paso 4: Define el Período</h5>
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Período</label>
                            <select class="form-select">
                                <option value="2025">2025</option>
                                <option value="2025-2027">2025 - 2027</option>
                                <option value="2025-2030">2025 - 2030</option>
                                <option value="Q1-2025">Q1 2025</option>
                                <option value="2026">2026</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" value="2025-01-01">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control" value="2025-12-31">
                        </div>
                    </div>
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong><?= $metodologias[0]['metodologia_nombre'] ?? 'BSC' ?></strong> tiene 7 fases definidas que se crearán automáticamente.
                    </div>
                </form>
            <?php elseif ($step === 5): ?>
                <h5 class="mb-3">Paso 5: Confirma la Creación</h5>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>¡Todo listo! Revisa el resumen antes de crear el plan.
                </div>
                <table class="table">
                    <tr><th width="200">Empresa</th><td>Empresa seleccionada</td></tr>
                    <tr><th>Metodología</th><td>Balance Scorecard (BSC)</td></tr>
                    <tr><th>Plan</th><td>Plan Estratégico 2025-2027</td></tr>
                    <tr><th>Período</th><td>2025 - 2027</td></tr>
                    <tr><th>Fases automáticas</th><td>7 fases con guías paso a paso</td></tr>
                </table>
                <p class="text-muted small">Al crear el plan, se generarán automáticamente las fases, se habilitará el asistente IA para guiarte y podrás comenzar a definir objetivos.</p>
            <?php endif; ?>
        </div>

        <div class="card-footer text-end bg-white">
            <?php if ($step > 1): ?>
                <a href="?step=<?= $step - 1 ?>" class="btn btn-light me-2">Anterior</a>
            <?php endif; ?>
            <?php if ($step < 5): ?>
                <a href="?step=<?= $step + 1 ?>" class="btn btn-primary">Siguiente <i class="fas fa-arrow-right ms-1"></i></a>
            <?php else: ?>
                <button class="btn btn-success btn-lg">
                    <i class="fas fa-magic me-2"></i>Crear Plan con IA
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function selectCompany(id) {
    document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
    event.target.closest('.method-card').classList.add('selected');
}
function selectMethod(id) {
    document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
    event.target.closest('.method-card').classList.add('selected');
}
</script>
</body>
</html>

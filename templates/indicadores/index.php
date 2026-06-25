<?php
$busqueda = trim($_GET['q'] ?? '');
$filtroPersp = $_GET['persp'] ?? '';
$filtroObj = (int)($_GET['obj_id'] ?? 0);
$todosInds = $indicadores;

// Filtrar
if ($busqueda) $todosInds = array_filter($todosInds, fn($i) => stripos($i['indicador_nombre'], $busqueda) !== false);

// Cargar objetivos y procesos
$objetivosPlan = (new PlanManager())->getObjetivos($planId);
$objMap = []; foreach ($objetivosPlan as $o) $objMap[$o['objetivo_id']] = $o;

$core = EstrateGiaCore::getInstance();
$procesosActivos = $core->fetchAll(
    'SELECT p.proceso_id, p.proceso_nombre, m.macro_nombre FROM proc_procesos p
     JOIN proc_macroprocesos m ON p.proceso_macro_id = m.macro_id
     WHERE m.macro_empresa_id = :eid AND p.proceso_activo = 1 ORDER BY p.proceso_nombre',
    ['eid' => (int)($_GET['empresa_id'] ?? ($_COOKIE['empresa_activa'] ?? 2))]
) ?: [];

// Filtrar por perspectiva y objetivo
$indsFiltrados = $todosInds;
if ($filtroPersp || $filtroObj) {
    $indsFiltrados = [];
    foreach ($todosInds as $ind) {
        $oid = (int)($ind['indicador_objetivo_id'] ?? 0);
        $obj = $objMap[$oid] ?? null;
        $persp = $obj['objetivo_perspectiva'] ?? '';
        if ($filtroPersp && $persp !== $filtroPersp) continue;
        if ($filtroObj && $oid !== $filtroObj) continue;
        $indsFiltrados[] = $ind;
    }
}

// Agrupar por perspectiva
$persps = ['financiera'=>'Financiera','cliente'=>'Cliente','procesos'=>'Procesos','aprendizaje'=>'Aprendizaje'];
$colors = ['financiera'=>'#28a745','cliente'=>'#007bff','procesos'=>'#ff9800','aprendizaje'=>'#6f42c1'];
$indsPorPersp = []; $indsSinObj = [];
foreach ($indsFiltrados as $ind) {
    $oid = (int)($ind['indicador_objetivo_id'] ?? 0);
    $persp = $objMap[$oid]['objetivo_perspectiva'] ?? null;
    if ($persp && isset($persps[$persp])) {
        $indsPorPersp[$persp][] = $ind;
    } else {
        $indsSinObj[] = $ind;
    }
}
$conMeta = 0; foreach ($indsFiltrados as $i) if (($i['indicador_rango_maximo']??0) > 0) $conMeta++;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="fas fa-gauge-high me-2"></i>Indicadores KPIs · <?= htmlspecialchars($plan['plan_nombre'] ?? 'Plan') ?></h5>
    <div class="d-flex gap-2">
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.href='?'">Todos</button>
            <?php foreach ($persps as $pk => $pv): ?>
            <button type="button" class="btn btn-sm <?= $filtroPersp===$pk?'btn-primary':'btn-outline-secondary' ?>" onclick="location.href='?persp=<?= $pk ?><?= $filtroObj?'&obj_id='.$filtroObj:'' ?>'" style="font-size:0.65rem;color:<?= $filtroPersp===$pk?'white':$colors[$pk] ?>">&#9679; <?= substr($pv,0,4) ?></button>
            <?php endforeach; ?>
        </div>
        <select class="form-select form-select-sm" style="width:180px;font-size:0.7rem" onchange="if(this.value)location.href='?obj_id='+this.value+'<?= $filtroPersp?'&persp='.$filtroPersp:'' ?>'">
            <option value="">Todos los objetivos</option>
            <?php foreach ($objetivosPlan as $obj): ?>
            <option value="<?= $obj['objetivo_id'] ?>" <?= $filtroObj===$obj['objetivo_id']?'selected':'' ?>><?= htmlspecialchars(substr($obj['objetivo_nombre'],0,40)) ?></option>
            <?php endforeach; ?>
        </select>
        <form method="GET" class="d-flex">
            <input type="text" name="q" class="form-control form-control-sm" style="width:160px;font-size:0.7rem" placeholder="Buscar..." value="<?= htmlspecialchars($busqueda) ?>">
            <?php if ($filtroPersp): ?><input type="hidden" name="persp" value="<?= htmlspecialchars($filtroPersp) ?>"><?php endif; ?>
            <?php if ($filtroObj): ?><input type="hidden" name="obj_id" value="<?= $filtroObj ?>"><?php endif; ?>
            <?php if (!empty($_GET['plan_id'])): ?><input type="hidden" name="plan_id" value="<?= (int)$_GET['plan_id'] ?>"><?php endif; ?>
        </form>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalIndicador"><i class="fas fa-plus me-1"></i>Nuevo</button>
        <div class="btn-group btn-group-sm ms-1 nivel-detalle">
            <button class="btn btn-sm btn-outline-secondary active" onclick="setNivel('n1')" style="font-size:0.6rem">N1</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="setNivel('n2')" style="font-size:0.6rem">N2</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="setNivel('n3')" style="font-size:0.6rem">N3</button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarTabla()" style="font-size:0.65rem">CSV</button>
        <a href="/indicadores/plantilla-mediciones?plan_id=<?= $planId ?>" class="btn btn-sm btn-outline-success" style="font-size:0.65rem" title="Descargar plantilla Excel (.xlsx)">&#128196; Excel</a>
        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalCargaExcel" style="font-size:0.65rem">&#128228; Subir XLSX</button>
    </div>
</div>

<!-- Resumen -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Total KPIs</div><div class="stat-value"><?= count($indsFiltrados) ?></div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Con Meta</div><div class="stat-value"><?= $conMeta ?></div></div></div>
    <div class="col-md-3"><div class="stat-card"><div class="stat-label">Procesos</div><div class="stat-value"><?= count($procesosActivos) ?></div></div></div>
    <?php foreach (array_slice($persps,0,1) as $pk => $pv): $cnt = count($indsPorPersp[$pk] ?? []); ?>
    <div class="col-md-3"><div class="stat-card" style="border-left:3px solid <?= $colors[$pk] ?>"><div class="stat-label" style="color:<?= $colors[$pk] ?>"><?= $pv ?></div><div class="stat-value" style="font-size:1rem"><?= $cnt ?></div></div></div>
    <?php endforeach; ?>
</div>

<!-- Tabla -->
<div class="card-box">
    <div class="card-box-header">Indicadores (<?= count($indsFiltrados) ?>)</div>
    <div class="card-box-body p-0">
        <div class="table-responsive">
            <table class="table table-sm small mb-0" id="tabla-indicadores" style="font-size:0.75rem">
                <thead class="table-light">
                    <tr><th>Indicador</th><th>Perspectiva</th><th>Objetivo</th><th class="n2-col">Proceso</th><th class="n2-col">Fórmula</th><th>Unidad</th><th>Meta</th></tr>
                </thead>
                <tbody>
                <?php foreach ($persps as $pk => $pv): $inds = $indsPorPersp[$pk] ?? []; if (empty($inds)) continue; ?>
                <tr class="table-active"><td colspan="7" style="color:<?= $colors[$pk] ?>;font-weight:600;font-size:0.7rem;padding:4px 8px"><?= $pv ?> (<?= count($inds) ?>)</td></tr>
                <?php foreach ($inds as $ind): 
                    $oid = (int)($ind['indicador_objetivo_id']??0); $obj = $objMap[$oid] ?? null;
                    $pid = (int)($ind['indicador_proceso_id']??0);
                    $procName = '';
                    foreach ($procesosActivos as $p) { if ((int)$p['proceso_id'] === $pid) { $procName = $p['proceso_nombre']; break; } }
                ?>
                <tr style="cursor:pointer" onclick="location.href='/indicadores/ver/<?= $ind['indicador_id'] ?>'" title="Ver detalle">
                    <td><strong><?= htmlspecialchars($ind['indicador_nombre']) ?></strong></td>
                    <td><span style="color:<?= $colors[$pk] ?>">&#9679;</span></td>
                    <td><?= $obj ? htmlspecialchars(substr($obj['objetivo_nombre'],0,25)) : '—' ?></td>
                    <td class="n2-col"><?= $procName ? htmlspecialchars(substr($procName,0,20)) : '<span class="text-muted">—</span>' ?></td>
                    <td class="n2-col"><code style="font-size:0.65rem"><?= htmlspecialchars($ind['indicador_formula'] ?? '') ?></code></td>
                    <td><?= htmlspecialchars($ind['indicador_unidad_medida'] ?? '—') ?></td>
                    <td><?= ($ind['indicador_rango_maximo']??0) > 0 ? '<strong>'.number_format($ind['indicador_rango_maximo'],1).'</strong>' : '<span class="text-muted">—</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
                <?php if (!empty($indsSinObj)): ?>
                <tr class="table-active"><td colspan="7" style="color:#6c757d;font-weight:600;font-size:0.7rem;padding:4px 8px">Sin clasificar (<?= count($indsSinObj) ?>)</td></tr>
                <?php foreach ($indsSinObj as $ind): ?>
                <tr style="cursor:pointer" onclick="location.href='/indicadores/ver/<?= $ind['indicador_id'] ?>'" title="Ver detalle">
                    <td><?= htmlspecialchars($ind['indicador_nombre']) ?></td>
                    <td>—</td><td>—</td><td>—</td>
                    <td><code style="font-size:0.65rem"><?= htmlspecialchars($ind['indicador_formula'] ?? '') ?></code></td>
                    <td><?= htmlspecialchars($ind['indicador_unidad_medida'] ?? '—') ?></td>
                    <td><?= ($ind['indicador_rango_maximo']??0) > 0 ? '<strong>'.number_format($ind['indicador_rango_maximo'],1).'</strong>' : '<span class="text-muted">—</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php if (empty($indsFiltrados)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">Sin indicadores para este filtro</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nuevo Indicador -->
<div class="modal fade" id="modalIndicador" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2"><h6 class="modal-title">Nuevo Indicador</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="formNuevoIndicador" onsubmit="guardarIndicador(event)">
                    <input type="hidden" name="plan_id" value="<?= $planId ?>">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-0">Nombre *</label>
                            <input name="nombre" class="form-control form-control-sm" required placeholder="Nombre del indicador">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-0">Categoría</label>
                            <select name="categoria_id" class="form-select form-select-sm">
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['categoria_id'] ?>"><?= htmlspecialchars($cat['categoria_nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-0">Frecuencia</label>
                            <select name="frecuencia" class="form-select form-select-sm">
                                <option value="mensual">Mensual</option><option value="trimestral">Trimestral</option>
                                <option value="semanal">Semanal</option><option value="anual">Anual</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-0">Objetivo</label>
                            <select name="objetivo_id" class="form-select form-select-sm">
                                <option value="">— Sin objetivo —</option>
                                <?php foreach ($objetivosPlan as $obj): ?>
                                <option value="<?= $obj['objetivo_id'] ?>"><?= htmlspecialchars(substr($obj['objetivo_nombre'],0,40)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-0">Proceso asociado</label>
                            <select name="proceso_id" class="form-select form-select-sm">
                                <option value="">— Sin proceso —</option>
                                <?php foreach ($procesosActivos as $proc): ?>
                                <option value="<?= $proc['proceso_id'] ?>"><?= htmlspecialchars($proc['proceso_nombre']) ?> (<?= htmlspecialchars($proc['macro_nombre']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-0">Fórmula</label>
                            <input name="formula" class="form-control form-control-sm" placeholder="(A - B) / C * 100">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-0">Unidad</label>
                            <input name="unidad" class="form-control form-control-sm" placeholder="%">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-0">Rango mín</label>
                            <input name="rango_min" class="form-control form-control-sm" type="number" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-0">Meta (máx)</label>
                            <input name="rango_max" class="form-control form-control-sm" type="number" step="0.01" placeholder="18">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-0">Fuente</label>
                            <input name="fuente" class="form-control form-control-sm" placeholder="ERP / CRM">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small mb-0">Descripción</label>
                            <textarea name="descripcion" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary">Crear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function exportarTabla() {
    var tbody = document.querySelector('#tabla-indicadores tbody');
    if (!tbody) return;
    var rows = tbody.querySelectorAll('tr');
    var data = [];
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('td, th');
        if (cells.length < 2) return;
        var r = [];
        cells.forEach(function(cell) { r.push('"'+cell.textContent.trim().replace(/"/g,'""')+'"'); });
        if (r.length > 0) data.push(r.join(','));
    });
    var csv = '\uFEFF' + data.join('\n');
    var blob = new Blob([csv], {type:'text/csv;charset=utf-8'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'indicadores_<?= date('Ymd') ?>.csv';
    a.click();
}

function setNivel(n) {
    document.querySelectorAll('.nivel-detalle .btn').forEach(function(b){ b.classList.remove('active'); });
    event.target.classList.add('active');
    document.getElementById('tabla-indicadores').className = 'table table-sm small mb-0 nivel-' + n;
}
</script>

<!-- Modal Carga Masiva -->
<div class="modal fade" id="modalCargaExcel" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2"><h6 class="modal-title">Carga Masiva de Mediciones</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <ol class="small mb-2">
                    <li>Descarga la <a href="/indicadores/plantilla-mediciones?plan_id=<?= $planId ?>">plantilla Excel (.xlsx)</a></li>
                    <li>Llena <strong>Mes</strong> (1-12), <strong>Año</strong> (YYYY) y <strong>Valor</strong> (numérico)</li>
                    <li>El semáforo se calcula automático: ≥ meta = verde, ≥ 70% = amarillo, resto = rojo</li>
                    <li>Súbela aquí (XLSX o CSV)</li>
                </ol>
                <form id="uploadMediciones" enctype="multipart/form-data">
                    <input type="file" name="archivo" class="form-control form-control-sm mb-2" accept=".csv,.xlsx" required>
                    <button type="submit" class="btn btn-sm btn-success w-100">Subir Excel</button>
                </form>
            </div>
        </div>
    </div>
</div>
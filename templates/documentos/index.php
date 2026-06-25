<?php
$macroColors = ['estrategico'=>'#1a73e8','misional'=>'#28a745','apoyo'=>'#ffc107','evaluacion'=>'#6f42c1'];
$macroIcons = ['estrategico'=>'crown','misional'=>'stethoscope','apoyo'=>'gear','evaluacion'=>'magnifying-glass-chart'];
$macroLabels = ['estrategico'=>'Estratégicos','misional'=>'Misionales','apoyo'=>'De Apoyo','evaluacion'=>'De Evaluación'];
$busqueda = trim($_GET['q'] ?? '');

// Cargar macroprocesos con conteos
$core = EstrateGiaCore::getInstance();
$macroprocesos = $core->fetchAll(
    "SELECT m.*, 
            (SELECT COUNT(*) FROM proc_procesos p WHERE p.proceso_macro_id=m.macro_id AND p.proceso_activo=1) as total_procesos,
            (SELECT COUNT(*) FROM doc_documentos d JOIN proc_procesos p ON d.documento_proceso_id=p.proceso_id WHERE p.proceso_macro_id=m.macro_id AND d.documento_activo=1) as total_docs
     FROM proc_macroprocesos m WHERE m.macro_empresa_id = :eid AND m.macro_activo = 1
     ORDER BY FIELD(m.macro_tipo,'estrategico','misional','apoyo','evaluacion'), m.macro_orden",
    ['eid' => $empresaId]
);

// Agrupar por tipo
$porTipo = ['estrategico'=>[],'misional'=>[],'apoyo'=>[],'evaluacion'=>[]];
foreach ($macroprocesos as $mp) {
    $porTipo[$mp['macro_tipo']][] = $mp;
}
?>

<div class="row g-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fw-bold mb-0" style="font-size:1.15rem"><i class="fas fa-sitemap me-2"></i>Gestión Documental · <?= htmlspecialchars($empresa['empresa_nombre']) ?></div>
            <div class="d-flex gap-2">
                <a href="?" class="btn btn-sm <?= ($_GET['vista']??'')==='tarjetas'?'btn-outline-secondary':'btn-primary' ?>"><i class="fas fa-table me-1"></i>Tabla</a>
                <a href="?vista=tarjetas" class="btn btn-sm <?= ($_GET['vista']??'')==='tarjetas'?'btn-primary':'btn-outline-secondary' ?>"><i class="fas fa-th-large me-1"></i>Tarjetas</a>
                <form method="GET" class="d-flex">
                    <input type="text" name="q" class="form-control form-control-sm" style="width:280px" placeholder="Buscar documento por título o código..." value="<?= htmlspecialchars($busqueda) ?>">
                    <button class="btn btn-primary btn-sm ms-1"><i class="fas fa-search"></i></button>
                </form>
                <a href="/documentos/crear?empresa_id=<?= $empresaId ?>" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Nuevo Documento</a>
                <a href="#" class="btn btn-outline-info btn-sm" onclick="document.getElementById('importCSVForm').style.display='block'; return false"><i class="fas fa-upload me-1"></i>Importar CSV</a>
            </div>
        </div>

        <?php if (empty($macroprocesos)): ?>
        <div class="card-box"><div class="card-box-body text-center py-5">
            <i class="fas fa-sitemap" style="font-size:4rem;color:#ccc;display:block;margin-bottom:16px"></i>
            <h5>Sin mapa de procesos</div>
            <p>Ve a <a href="/procesos">Procesos</a> para crear los macroprocesos y procesos de la empresa.</p>
        </div></div>
        <?php elseif ($_GET['vista']??'' === 'tarjetas'): ?>

        <!-- 4 TIPOS DE PROCESO como tarjetas principales -->
        <div class="row g-4">
            <?php foreach ($porTipo as $tipo => $macros): 
                if (empty($macros)) continue;
                $totalProcs = array_sum(array_column($macros, 'total_procesos'));
                $totalDocs = array_sum(array_column($macros, 'total_docs'));
            ?>
            <div class="col-md-6">
                <div class="card" style="border-left:5px solid <?= $macroColors[$tipo] ?>;border-radius:12px">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div>
                                <i class="fas fa-<?= $macroIcons[$tipo] ?>" style="font-size:2rem;color:<?= $macroColors[$tipo] ?>;margin-bottom:8px;display:block"></i>
                                <h4 class="mb-1">Procesos <?= $macroLabels[$tipo] ?></h4>
                                <p class="text-muted mb-0 small"><?= count($macros) ?> macroprocesos · <?= $totalProcs ?> procesos · <?= $totalDocs ?> documentos</p>
                            </div>
                            <span class="badge fs-5" style="background:<?= $macroColors[$tipo] ?>;color:#fff"><?= $totalDocs ?></span>
                        </div>

                        <div class="list-group list-group-flush">
                            <?php foreach ($macros as $mp): ?>
                            <div class="list-group-item px-0 py-2 border-0">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fas fa-folder text-muted"></i>
                                    <strong><?= htmlspecialchars($mp['macro_nombre']) ?></strong>
                                    <small class="text-muted">(<?= $mp['total_procesos'] ?> procesos · <?= $mp['total_docs'] ?> docs)</small>
                                </div>
                                <?php 
                                $procesos = $core->fetchAll(
                                    "SELECT p.*, (SELECT COUNT(*) FROM doc_documentos WHERE documento_proceso_id=p.proceso_id AND documento_activo=1) as total_docs
                                     FROM proc_procesos p WHERE p.proceso_macro_id = :mid AND p.proceso_activo = 1 ORDER BY p.proceso_nombre",
                                    ['mid' => $mp['macro_id']]
                                );
                                ?>
                                <?php foreach ($procesos as $proc): ?>
                                <a href="/documentos/proceso/<?= $proc['proceso_id'] ?>" class="d-flex align-items-center gap-2 ms-4 mb-1 p-2 rounded-2 text-decoration-none hover-bg" style="color:#333;font-size:0.9rem">
                                    <i class="fas fa-diagram-project text-muted small"></i>
                                    <span style="flex:1"><?= htmlspecialchars($proc['proceso_nombre']) ?></span>
                                    <?php if ($proc['proceso_codigo']): ?><code class="small text-muted"><?= htmlspecialchars($proc['proceso_codigo']) ?></code><?php endif; ?>
                                    <?php if ($proc['total_docs'] > 0): ?>
                                    <span class="badge bg-primary rounded-pill"><?= $proc['total_docs'] ?></span>
                                    <?php endif; ?>
                                    <i class="fas fa-chevron-right text-muted small"></i>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <?php $docs = $core->fetchAll("SELECT d.*, p.proceso_nombre, m.macro_nombre, m.macro_tipo FROM doc_documentos d JOIN proc_procesos p ON d.documento_proceso_id=p.proceso_id JOIN proc_macroprocesos m ON p.proceso_macro_id=m.macro_id WHERE m.macro_empresa_id=:eid AND d.documento_activo=1 ORDER BY d.updated_at DESC LIMIT 100", ['eid'=>$empresaId]); ?>
        <div class="card-box"><div class="card-box-body p-0"><table class="table-box small">
        <thead><tr><th>Código</th><th>Título</th><th>Versión</th><th>Estado</th><th>Proceso</th><th>Macroproceso</th><th>Actualizado</th><th></th></tr></thead>
        <tbody><?php foreach ($docs as $d): ?>
        <tr>
            <td><code><?= htmlspecialchars($d['documento_codigo']) ?></code></td>
            <td><strong><a href="/documentos/ver/<?= $d['documento_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($d['documento_titulo']) ?></a></strong></td>
            <td>v<?= $d['documento_version'] ?></td>
            <td><span class="badge bg-<?= $d['documento_estado']==='publicado'?'success':($d['documento_estado']==='borrador'?'warning':'info') ?>"><?= $d['documento_estado'] ?></span></td>
            <td><small><?= htmlspecialchars($d['proceso_nombre']) ?></small></td>
            <td><span class="badge" style="background:<?= $macroColors[$d['macro_tipo']] ?? '#999' ?>"><?= $d['macro_tipo'] ?></span></td>
            <td><small><?= date('d/m/Y', strtotime($d['updated_at'])) ?></small></td>
            <td><a href="/documentos/ver/<?= $d['documento_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
        </tr>
        <?php endforeach; ?></tbody></table></div></div>
        <?php endif; ?>
    </div>
</div>

<!-- Formulario de importación CSV (oculto por defecto) -->
<div id="importCSVForm" style="display:none" class="card-box mt-3">
    <div class="card-box-header d-flex justify-content-between align-items-center">
        <div><i class="fas fa-file-csv me-2"></i>Importar Documentos desde CSV</div>
        <button type="button" class="btn-close" onclick="document.getElementById('importCSVForm').style.display='none'"></button>
    </div>
    <div class="card-box-body">
        <p class="small text-muted mb-3">
            Archivo CSV con columnas: <code>codigo, nombre, descripcion, tipo, proceso_codigo, version, fecha_vigencia, estado, contenido</code><br>
            Si se omite el código, se genera usando la codificación configurada de la empresa.
        </p>
        <form id="csvUploadForm" enctype="multipart/form-data">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">
            <div class="input-group">
                <input type="file" name="archivo_csv" class="form-control form-control-sm" accept=".csv" required>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-upload me-1"></i>Importar</button>
            </div>
        </form>
        <div id="importResult" class="mt-3" style="display:none"></div>
    </div>
</div>

<script>
document.getElementById('csvUploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Importando...';
    var formData = new FormData(this);
    fetch('/documentos/importar-masivo', {method:'POST',body:formData})
        .then(r => r.json())
        .then(j => {
            var div = document.getElementById('importResult');
            div.style.display = 'block';
            if (j.success) {
                div.innerHTML = '<div class="alert alert-success"><strong>' + j.creados + ' documentos creados</strong>' +
                    (j.errores > 0 ? ' · ' + j.errores + ' errores' : '') +
                    (j.detalle && j.detalle.length > 0 ? '<br><small>' + j.detalle.map(function(d){return 'Fila ' + d.fila + ': ' + d.error}).join('<br>') + '</small>' : '') +
                    '</div>';
                if (j.creados > 0) setTimeout(function(){ location.reload(); }, 2000);
            } else {
                div.innerHTML = '<div class="alert alert-danger">' + (j.error || 'Error desconocido') + '</div>';
            }
        })
        .catch(function(e) {
            document.getElementById('importResult').style.display = 'block';
            document.getElementById('importResult').innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>';
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-1"></i>Importar';
        });
});
</script>

<style>
.hover-bg:hover { background: #f5f6fa; }
</style>

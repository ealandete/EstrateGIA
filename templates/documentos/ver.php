<?php
$tipoLabels = ['manual_calidad'=>'Manual de Calidad','politica'=>'Política','procedimiento'=>'Procedimiento','instructivo'=>'Instructivo','registro'=>'Registro','formato'=>'Formato','plan'=>'Plan','informe'=>'Informe'];
$controlCambios = json_decode($doc['documento_control_cambios'] ?? '[]', true) ?: [];
$estadoColor = ['borrador'=>'secondary','revision'=>'warning','aprobado'=>'success','publicado'=>'primary','obsoleto'=>'dark'];
$versionOk = $_GET['version'] ?? null;
?>

<?php if ($versionOk): ?><div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Nueva versión creada</div><?php endif; ?>

<div class="d-flex justify-content-between mb-3">
    <a href="/documentos" class="btn btn-sm btn-light"><i class="fas fa-arrow-left me-1"></i>Volver</a>
    <div class="d-flex gap-2">
        <span class="badge bg-<?= $estadoColor[$doc['documento_estado']]??'light' ?>"><?= $doc['documento_estado'] ?></span>
        <span class="badge bg-light text-dark">v<?= $doc['documento_version'] ?></span>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card-box">
            <div class="card-box-header d-flex justify-content-between">
                <div><h5 class="mb-0"><?= htmlspecialchars($doc['documento_titulo']) ?></h5>
                <small class="text-muted"><?= $tipoLabels[$doc['documento_tipo']]??$doc['documento_tipo'] ?> · <?= htmlspecialchars($doc['documento_codigo']??'Sin código') ?></small></div>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fas fa-print"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('editVersion').style.display='block'"><i class="fas fa-code-branch"></i></button>
    </div>
</div>

<script>
async function firmarDocumento(id) {
    if (!confirm('¿Confirmar firma electrónica de este documento?\n\nSe registrará su identidad, cargo, fecha y hora como firma electrónica.')) return;
    var btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Firmando...';
    try {
        var r = await fetch('/documentos/firmar/' + id, {method:'POST'});
        var j = await r.json();
        if (j.success) {
            alert('Documento firmado electrónicamente.\nFirma: ' + j.firma.substring(0, 40) + '...\nFecha: ' + j.fecha);
        } else {
            alert('Error: ' + (j.error || 'No se pudo firmar'));
        }
    } catch(e) { alert('Error: ' + e.message); }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-signature me-1"></i>Firmar';
}
</script>
            <div class="card-box-body" style="min-height:300px">
                <?php if ($doc['documento_contenido_html']): ?>
                <div class="p-3"><?= $doc['documento_contenido_html'] ?></div>
                <?php else: ?>
                <div class="text-center text-muted py-5">Sin contenido</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Nueva versión -->
        <div id="editVersion" style="display:none" class="card-box mt-3">
            <div class="card-box-header"><i class="fas fa-code-branch me-2"></i>Nueva Versión</div>
            <div class="card-box-body">
                <form method="POST" action="/documentos/nueva-version/<?= $doc['documento_id'] ?>">
                    <div class="mb-2"><label class="form-label small">Contenido actualizado</label>
                    <textarea name="contenido" class="form-control" rows="10"><?= htmlspecialchars($doc['documento_contenido_html'] ?? '') ?></textarea></div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-code-branch me-1"></i>Crear Nueva Versión (v<?= $doc['documento_version'] + 0.1 ?>)</button>
                    <button type="button" class="btn btn-light btn-sm" onclick="document.getElementById('editVersion').style.display='none'">Cancelar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card-box mb-3">
            <div class="card-box-header">Metadatos</div>
            <div class="card-box-body small">
                <div><strong>Código:</strong> <?= htmlspecialchars($doc['documento_codigo']??'-') ?></div>
                <div><strong>Versión:</strong> v<?= $doc['documento_version'] ?></div>
                <div><strong>Estado:</strong> <span class="badge bg-<?= $estadoColor[$doc['documento_estado']]??'light' ?>"><?= $doc['documento_estado'] ?></span></div>
                <div><strong>Norma:</strong> <?= htmlspecialchars($doc['norma_codigo']??'-') ?></div>
                <div><strong>Proceso:</strong> <?= htmlspecialchars($doc['proceso_nombre']??'Sin proceso') ?></div>
                <div><strong>Elaborado por:</strong> <?= htmlspecialchars($doc['elaborado_por_nombre']??'-') ?></div>
                <div><strong>Aprobado por:</strong> <?= htmlspecialchars($doc['aprobado_por_nombre']??'Pendiente') ?></div>
                <div><strong>Fecha aprobación:</strong> <?= $doc['documento_fecha_aprobacion'] ? date('d/m/Y', strtotime($doc['documento_fecha_aprobacion'])) : 'Pendiente' ?></div>
            </div>
        </div>

        <!-- Historial de versiones -->
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-clock-rotate-left me-2"></i>Historial de Versiones</div>
            <div class="card-box-body p-0">
                <?php if (empty($controlCambios)): ?>
                <div class="p-3 text-muted small text-center">Sin cambios registrados. Esta es la versión inicial.</div>
                <?php else: ?>
                <?php foreach (array_reverse($controlCambios) as $cc): ?>
                <div class="p-2 px-3 border-bottom small">
                    <div class="d-flex justify-content-between">
                        <strong>v<?= htmlspecialchars($cc['version']??'?') ?></strong>
                        <small class="text-muted"><?= htmlspecialchars($cc['fecha']??'') ?></small>
                    </div>
                    <div class="text-muted"><?= htmlspecialchars($cc['cambio']??'') ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Acciones -->
        <div class="card-box">
            <div class="card-box-header">Acciones</div>
            <div class="card-box-body d-grid gap-2">
                <?php if ($doc['documento_estado'] === 'borrador'): ?>
                <a href="/documentos/aprobar/<?= $doc['documento_id'] ?>" class="btn btn-warning btn-sm" onclick="return confirm('¿Enviar a revisión?')"><i class="fas fa-check me-1"></i>Enviar a Revisión</a>
                <?php endif; ?>
                <?php if ($doc['documento_estado'] === 'revision'): ?>
                <a href="/documentos/aprobar/<?= $doc['documento_id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('¿Aprobar?')"><i class="fas fa-check-double me-1"></i>Aprobar</a>
                <?php endif; ?>
                <?php if ($doc['documento_estado'] === 'aprobado'): ?>
                <a href="/documentos/publicar/<?= $doc['documento_id'] ?>" class="btn btn-primary btn-sm" onclick="return confirm('¿Publicar?')"><i class="fas fa-upload me-1"></i>Publicar</a>
                <?php endif; ?>
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fas fa-print me-1"></i>Imprimir</button>
                <?php if ($doc['documento_estado'] === 'aprobado'): ?>
                <button class="btn btn-warning btn-sm" onclick="firmarDocumento(<?= $doc['documento_id'] ?>)"><i class="fas fa-signature me-1"></i>Firmar</button>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div>
</div>

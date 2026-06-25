<?php $error = $_GET['error'] ?? null; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-md-8">
        <form method="POST" action="/documentos/store" enctype="multipart/form-data">
            <input type="hidden" name="empresa_id" value="<?= $empresaId ?>">

            <div class="card-box">
                <div class="card-box-header"><i class="fas fa-file-alt me-2"></i>Nuevo Documento</div>
                <div class="card-box-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Título *</label>
                            <input type="text" name="titulo" class="form-control" required placeholder="Nombre del documento">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Código</label>
                            <input type="text" name="codigo" class="form-control" placeholder="Auto-generado" id="docCodigo">
                            <small class="text-muted">Se genera automáticamente al seleccionar tipo y proceso</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo de Documento *</label>
                            <select name="tipo" class="form-select" required id="docTipo" onchange="updateCodigo()">
                                <option value="">Seleccionar...</option>
                                <option value="manual_calidad">MC - Manual de Calidad</option>
                                <option value="politica">PO - Política</option>
                                <option value="plan">PL - Plan</option>
                                <option value="procedimiento">PR - Procedimiento</option>
                                <option value="instructivo">IN - Instructivo</option>
                                <option value="formato">FO - Formato</option>
                                <option value="registro">RG - Registro</option>
                                <option value="informe">IF - Informe</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Proceso</label>
                            <select name="proceso_id" class="form-select" id="docProceso" onchange="updateCodigo()">
                                <option value="">Sin proceso</option>
                                <?php foreach ($procesos as $pr): ?>
                                <option value="<?= $pr['proceso_id'] ?>" data-codigo="<?= htmlspecialchars($pr['proceso_codigo'] ?? '') ?>">
                                    <?= htmlspecialchars($pr['macro_nombre']) ?> → <?= htmlspecialchars($pr['proceso_nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Norma ISO</label>
                            <select name="norma_id" class="form-select">
                                <option value="">Sin norma</option>
                                <?php foreach ($normas as $n): ?>
                                <option value="<?= $n['norma_id'] ?>"><?= htmlspecialchars($n['norma_codigo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Versión</label>
                            <input type="text" class="form-control" value="1.0" readonly>
                            <small class="text-muted">Inicia en 1.0. Se incrementa al crear nuevas versiones.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-box mt-3">
                <div class="card-box-header"><i class="fas fa-edit me-2"></i>Contenido</div>
                <div class="card-box-body">
                    <div class="mb-3">
                        <label class="form-label">Archivo (opcional)</label>
                        <input type="file" name="archivo" class="form-control">
                        <small class="text-muted">Formatos: PDF, DOCX, XLSX, imágenes. Máx 10MB. Se guarda en el directorio del proceso.</small>
                    </div>
                    <div class="editor-toolbar mb-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="bold" title="Negrita"><b>B</b></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="italic" title="Cursiva"><i>I</i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="underline" title="Subrayado"><u>U</u></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="insertUnorderedList" title="Lista"><i class="fas fa-list-ul"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="insertOrderedList" title="Lista numerada"><i class="fas fa-list-ol"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="formatBlock" data-val="h2" title="Título H2">H2</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="formatBlock" data-val="h3" title="Título H3">H3</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-cmd="formatBlock" data-val="p" title="Párrafo">P</button>
                    </div>
                    <div id="editorWysiwyg" contenteditable="true" class="form-control" style="min-height:300px;overflow-y:auto" placeholder="Escribe el contenido del documento. Puedes usar formato."></div>
                    <textarea name="contenido" id="editorWysiwygSource" class="d-none"></textarea>
                </div>
            </div>

            <div class="mt-3 text-end">
                <a href="/documentos" class="btn btn-light me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-1"></i>Crear Documento</button>
            </div>
        </form>
    </div>

    <div class="col-md-4">
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-sitemap me-2"></i>Codificación</div>
            <div class="card-box-body small">
                <p class="text-muted mb-2">El código sigue el árbol documental:</p>
                <code class="d-block mb-2 p-2 bg-light rounded">[TIPO]-[PROCESO]-[CONSECUTIVO]</code>
                <p class="mb-1"><strong>Tipos:</strong></p>
                <table class="small w-100 mb-2">
                    <tr><td>MC</td><td>Manual de Calidad</td></tr>
                    <tr><td>PO</td><td>Política</td></tr>
                    <tr><td>PR</td><td>Procedimiento</td></tr>
                    <tr><td>IN</td><td>Instructivo</td></tr>
                    <tr><td>FO</td><td>Formato</td></tr>
                    <tr><td>RG</td><td>Registro</td></tr>
                </table>
                <p class="mb-0 text-muted">Ej: <code>PR-GC-001</code> = Procedimiento de Gestión de Calidad #001</p>
            </div>
        </div>

        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-code-branch me-2"></i>Control de Versiones</div>
            <div class="card-box-body small">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-light text-dark">1.0</span> → Creación inicial
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-warning">1.1</span> → Cambios menores
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success">2.0</span> → Revisión mayor
                </div>
                <hr>
                <p class="mb-0 text-muted">Cada versión queda registrada en el historial de cambios del documento.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function() {
    document.getElementById('editorWysiwygSource').value = document.getElementById('editorWysiwyg').innerHTML;
});
document.querySelectorAll('.editor-toolbar button').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var cmd = this.dataset.cmd;
        var val = this.dataset.val || null;
        document.execCommand(cmd, false, val);
    });
});
function updateCodigo() {
    const tipo = document.getElementById('docTipo');
    const proceso = document.getElementById('docProceso');
    const codigo = document.getElementById('docCodigo');
    const tipoVal = tipo.value;
    const tipoCodes = {manual_calidad:'MC',politica:'PO',plan:'PL',procedimiento:'PR',instructivo:'IN',formato:'FO',registro:'RG',informe:'IF'};
    const procCode = proceso.selectedOptions[0]?.dataset?.codigo || proceso.value || 'XX';
    if (tipoVal) {
        codigo.value = (tipoCodes[tipoVal] || tipoVal.toUpperCase()) + '-' + procCode + '-001';
    }
}
</script>

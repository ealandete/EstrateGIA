<?php $error = $_GET['error'] ?? null; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><i class="fas fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="/planeacion/store" id="planForm">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card-box">
                <div class="card-box-header"><i class="fas fa-building me-2"></i>Empresa</div>
                <div class="card-box-body">
                    <select name="empresa_id" id="empresaSelect" class="form-select" required onchange="evaluarRecomendacion()">
                        <option value="">Seleccionar empresa...</option>
                        <?php foreach ($empresas as $e): ?>
                        <option value="<?= $e['empresa_id'] ?>" data-sector="<?= htmlspecialchars($e['sector_nombre']??'General') ?>"><?= htmlspecialchars($e['empresa_nombre']) ?> (<?= htmlspecialchars($e['sector_nombre']??'General') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaEmpresa">
                            <i class="fas fa-plus me-1"></i>Registrar nueva empresa
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-box">
                <div class="card-box-header"><i class="fas fa-lightbulb me-2"></i>Metodología</div>
                <div class="card-box-body">
                    <?php foreach ($metodologias as $m): ?>
                    <div class="form-check mb-3 p-3 border rounded-3 metodologia-option" style="cursor:pointer" onclick="this.querySelector('input').checked=true;seleccionarMet(<?=$m['metodologia_id']?>)">
                        <input class="form-check-input metodologia-radio" type="radio" name="metodologia_id" value="<?= $m['metodologia_id'] ?>" id="met_<?= $m['metodologia_id'] ?>" data-nombre="<?= htmlspecialchars($m['metodologia_nombre']) ?>" required onchange="evaluarRecomendacion()">
                        <label class="form-check-label w-100" for="met_<?= $m['metodologia_id'] ?>">
                            <strong><i class="fas <?= htmlspecialchars($m['metodologia_icono']??'fa-circle') ?> me-2"></i><?= htmlspecialchars($m['metodologia_nombre']) ?></strong>
                            <small class="d-block text-muted mt-1 metodologia-desc" id="desc_<?=$m['metodologia_id']?>"><?= htmlspecialchars($m['metodologia_descripcion']??'') ?></small>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div id="recomendacionSector" class="alert alert-info border-start border-4 border-info mt-3" style="display:none">
        <i class="fas fa-lightbulb me-2"></i><span id="recoTexto"></span>
    </div>

    <div class="card-box mt-4">
        <div class="card-box-header"><i class="fas fa-file-lines me-2"></i>Datos del Plan</div>
        <div class="card-box-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Nombre del Plan *</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Plan Estratégico 2025-2027" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3" placeholder="Alcance y propósito del plan estratégico..."></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Período</label>
                    <input type="text" name="periodo" class="form-control" placeholder="2025-2027">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Presupuesto Total</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="presupuesto" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 text-end">
        <a href="/planeacion" class="btn btn-light me-2">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-magic me-2"></i>Crear Plan Estratégico</button>
    </div>
</form>

<div class="alert alert-info mt-3 small">
    <i class="fas fa-info-circle me-2"></i>Al crear el plan se generarán automáticamente las fases según la metodología seleccionada y podrás usar el asistente IA para guiarte en cada paso.
</div>

<!-- MODAL: Nueva Empresa -->
<div class="modal fade" id="modalNuevaEmpresa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-building me-2"></i>Registrar Nueva Empresa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevaEmpresa" onsubmit="return guardarEmpresa(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Empresa *</label>
                        <input type="text" name="empresa_nombre" class="form-control" required placeholder="Ej: Hospital Central">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Razón Social</label>
                            <input type="text" name="empresa_razon_social" class="form-control" placeholder="Razón social">
                        </div>
                        <div class="col-6">
                            <label class="form-label">NIT</label>
                            <input type="text" name="empresa_nit" class="form-control" placeholder="NIT">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sector Económico *</label>
                        <select name="empresa_sector_id" class="form-select" required>
                            <option value="">Seleccionar sector...</option>
                            <?php foreach ($sectores as $s): ?>
                            <option value="<?= $s['sector_id'] ?>"><?= htmlspecialchars($s['sector_nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Define las normativas ISO y legales aplicables</small>
                    </div>
                    <div class="row g-2">
                        <div class="col-8">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="empresa_direccion" class="form-control" placeholder="Dirección">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="empresa_telefono" class="form-control" placeholder="Teléfono">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Empresa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function guardarEmpresa(e) {
    e.preventDefault();
    const form = document.getElementById('formNuevaEmpresa');
    const fd = new FormData(form);
    const params = new URLSearchParams(fd).toString();

    try {
        const resp = await fetch('/planeacion/crear-empresa', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        });
        if (resp.redirected) {
            const url = new URL(resp.url);
            const empresaId = url.searchParams.get('empresa_ok');
            if (empresaId) {
                const nombre = fd.get('empresa_nombre');
                const sectorId = fd.get('empresa_sector_id');
                const sectorNombres = <?= json_encode(array_column($sectores, 'sector_nombre', 'sector_id')) ?>;
                const sectorNombre = sectorNombres[sectorId] || 'General';

                const select = document.getElementById('empresaSelect');
                const opt = document.createElement('option');
                opt.value = empresaId;
                opt.textContent = nombre + ' (' + sectorNombre + ')';
                opt.selected = true;
                select.appendChild(opt);

                bootstrap.Modal.getInstance(document.getElementById('modalNuevaEmpresa')).hide();
                form.reset();
                alert('Empresa "' + nombre + '" creada y seleccionada.');
            }
        }
    } catch(err) {
        alert('Error al crear la empresa: ' + err.message);
    }
    return false;
}

function seleccionarMet(id) {}
// Sector-based recommendations
var sectorRecos = {
    'salud':           'Para <b>Salud</b> se recomienda <b>BSC (Balanced Scorecard)</b> o <b>Hoshin Kanri</b> para alinear estándares clínicos con estrategia. Asegúrate de incluir indicadores de calidad asistencial.',
    'financiero':      'Para <b>Financiero</b> se recomienda <b>BSC</b> con énfasis en la perspectiva financiera y de riesgos.',
    'educacion':       'Para <b>Educación</b> se recomienda <b>OKR</b> o <b>BSC</b> para medir resultados de aprendizaje y acreditaciones.',
    'tecnologia':      'Para <b>Tecnología</b> se recomienda <b>OKR</b> o <b>Design Thinking</b> por sus ciclos ágiles de innovación.',
    'manufactura':     'Para <b>Manufactura</b> se recomienda <b>Hoshin Kanri</b> o <b>BSC</b> para desplegar metas de calidad y productividad.',
    'gobierno':        'Para <b>Gobierno</b> se recomienda <b>BSC</b> adaptado al sector público con indicadores de transparencia.',
    'construccion':    'Para <b>Construcción</b> se recomienda <b>BSC</b> con control de costos y plazos.',
    'comercio':        'Para <b>Comercio</b> se recomienda <b>BSC</b> con perspectiva de cliente y cadena de suministro.',
    'transporte':      'Para <b>Transporte</b> se recomienda <b>BSC</b> con KPIs de eficiencia operativa y seguridad.',
    'energia':         'Para <b>Energía</b> se recomienda <b>BSC</b> con perspectiva de sostenibilidad y regulación.',
    'agropecuario':    'Para <b>Agropecuario</b> se recomienda <b>BSC</b> con indicadores de productividad y cadena de valor.',
    'turismo':         'Para <b>Turismo</b> se recomienda <b>BSC</b> u <b>OKR</b> con enfoque en satisfacción del cliente.',
    'minero':          'Para <b>Minería</b> se recomienda <b>BSC</b> con énfasis en SST, ambiental y comunidades.',
    'servicios':       'Para <b>Servicios</b> se recomienda <b>BSC</b> con perspectiva de cliente y procesos.',
    'ong':             'Para <b>ONG</b> se recomienda <b>Escenarios</b> o <b>BSC</b> adaptado a objetivos de impacto social.'
};

function evaluarRecomendacion() {
    var empresaSelect = document.getElementById('empresaSelect');
    var sector = empresaSelect.options[empresaSelect.selectedIndex].dataset.sector || '';
    var metRadio = document.querySelector('input[name="metodologia_id"]:checked');
    var metNombre = metRadio ? metRadio.dataset.nombre : '';
    var recoBox = document.getElementById('recomendacionSector');
    var recoTexto = document.getElementById('recoTexto');
    var sectorKey = sector.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
    var reco = sectorRecos[sectorKey] || null;
    if (reco) {
        recoBox.style.display = 'block';
        recoTexto.innerHTML = '<strong>Sector ' + sector + ':</strong> ' + reco;
    } else if (sector) {
        recoBox.style.display = 'block';
        recoTexto.innerHTML = '<strong>Sector ' + sector + ':</strong> Puedes usar cualquier metodología. <b>BSC</b> y <b>Hoshin Kanri</b> son las más versátiles para este sector.';
    } else {
        recoBox.style.display = 'none';
    }
}
</script>

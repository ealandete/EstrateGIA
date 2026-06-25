<?php
$perspectivas = ['financiera'=>'Financiera','cliente'=>'Cliente','procesos'=>'Procesos Internos','aprendizaje'=>'Aprendizaje y Crecimiento'];
$colores = ['financiera'=>'#28a745','cliente'=>'#007bff','procesos'=>'#ff9800','aprendizaje'=>'#6f42c1','sostenibilidad'=>'#00bcd4'];
$objetivos = $pm->getObjetivos($planId);
$perspActivas = isset($_GET['perspectivas']) ? explode(',',$_GET['perspectivas']) : array_keys($perspectivas);
?>
<div class="d-flex justify-content-between mb-2">
    <h5><i class="fas fa-project-diagram me-2"></i>Mapa Estratégico BSC</h5>
    <div class="d-flex gap-1 small">
        <?php foreach ($perspectivas as $pk => $pv): $checked = in_array($pk, $perspActivas); ?>
        <a href="?perspectivas=<?= $checked ? implode(',',array_diff($perspActivas,[$pk])) : implode(',',array_merge($perspActivas,[$pk])) ?>" class="btn btn-sm <?= $checked ? 'btn-primary' : 'btn-outline-secondary' ?>" style="font-size:0.7rem"><?= $pv ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-project-diagram me-2" style="color:#1a73e8"></i>Mapa Estratégico BSC</div>
            <div class="card-box-body">
                <p class="small text-muted">Construye relaciones causa-efecto entre objetivos de diferentes perspectivas. Arrastra las flechas para conectar.</p>
                <button type="button" class="btn btn-purple btn-sm w-100 mb-2" onclick="generarBSC()">&#129504; Sugerir objetivos por perspectiva</button>
                <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="abrirNuevoObj('cliente')">+ Nuevo objetivo</button>
                <button type="button" class="btn btn-success btn-sm w-100" onclick="guardarBSC()">&#128190; Guardar mapa</button>
                <div id="bscStatus" class="mt-2"></div>
            </div>
        </div>

        <div class="card-box mt-3">
            <div class="card-box-header">Leyenda</div>
            <div class="card-box-body small">
                <?php foreach ($perspectivas as $k => $v): ?>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div style="width:14px;height:14px;border-radius:3px;background:<?= $colores[$k] ?>"></div>
                    <?= $v ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card-box">
            <div class="card-box-header">Lienzo del Mapa Estratégico</div>
            <div class="card-box-body">
                <div id="bscCanvas" style="position:relative;min-height:500px;background:linear-gradient(180deg,#f8f9fa 0%,#e9ecef 100%);border-radius:12px;padding:24px">
                    <?php foreach ($perspectivas as $pk => $pv): if (!in_array($pk, $perspActivas)) continue; ?>
                    <div class="bsc-row mb-3" data-perspective="<?= $pk ?>" style="position:relative;min-height:80px;border-left:4px solid <?= $colores[$pk] ?>;padding:12px 16px;background:rgba(255,255,255,0.7);border-radius:0 8px 8px 0">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge" style="background:<?= $colores[$pk] ?>;color:#fff"><?= $pv ?></span>
                            <small class="text-muted"><?= $pk === 'financiera' ? 'Resultados económicos y valor para accionistas' : ($pk === 'cliente' ? 'Propuesta de valor y satisfacción del cliente' : ($pk === 'procesos' ? 'Eficiencia operativa y calidad de procesos' : ($pk === 'aprendizaje' ? 'Capital humano, tecnología y cultura' : 'Impacto ambiental y social'))) ?></small>
                            <button type="button" class="btn btn-sm btn-outline-secondary p-0 px-1" title="Añadir a <?= $pv ?>" onclick="abrirNuevoObj('<?= $pk ?>')" style="font-size:0.7rem;line-height:1.2;margin-left:auto">&plus;</button>
                        </div>
                        <div class="d-flex flex-wrap gap-2 bsc-objects" data-perspective="<?= $pk ?>">
                            <?php foreach ($objetivos as $obj): ?>
                            <?php if ($obj['objetivo_perspectiva'] === $pk): ?>
                            <div class="bsc-node" draggable="true" data-obj-id="<?= $obj['objetivo_id'] ?>" style="background:white;border:2px solid <?= $colores[$pk] ?>;border-radius:8px;padding:8px 14px;cursor:move;font-size:0.85rem;box-shadow:0 1px 3px rgba(0,0,0,0.1);position:relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <strong class="bsc-obj-name" style="flex:1"><?= htmlspecialchars(substr($obj['objetivo_nombre'], 0, 60)) ?></strong>
                                    <div class="d-flex gap-1 ms-2" style="flex-shrink:0">
                                        <button type="button" title="Editar" onclick="event.stopPropagation();var e=this.closest('.bsc-node');bscEditar(e)" style="background:#e9ecef;border:1px solid #aaa;border-radius:4px;cursor:pointer;padding:2px 7px;font-size:0.8rem;line-height:1.3" onmousedown="event.stopPropagation()">&#9998;</button>
                                        <button type="button" title="Eliminar" onclick="event.stopPropagation();bscEliminar(this.closest('.bsc-node'))" style="background:#ffdddd;color:#c00;border:1px solid #faa;border-radius:4px;cursor:pointer;padding:2px 7px;font-size:0.8rem;line-height:1.3" onmousedown="event.stopPropagation()">&#10005;</button>
                                    </div>
                                </div>
                                <div class="progress mt-1" style="height:3px"><div class="progress-bar" style="width:<?= $obj['objetivo_avance_porcentaje'] ?>%;background:<?= $colores[$pk] ?>"></div></div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <svg id="bscArrows" style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none"></svg>
                </div>
            </div>
        </div>

        <div class="card-box mt-3">
            <div class="card-box-header">Matriz de Relaciones Causa-Efecto</div>
            <div class="card-box-body table-responsive">
                <table class="table table-sm small" id="bscMatrix">
                    <thead>
                        <tr><th>Objetivo (Causa)</th><th>→</th><th>Objetivo (Efecto)</th><th>Intensidad</th><th></th></tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addRelacion()">&#10132; Añadir relación</button>
                <button type="button" class="btn btn-sm btn-purple" onclick="generarRelaciones()">&#129504; Sugerir relaciones</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo Objetivo -->
<div class="modal fade" id="modalNuevoObj" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2"><h6 class="modal-title">Nuevo objetivo</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label small">Perspectiva</label>
                <select id="nobj-persp" class="form-select form-select-sm mb-2">
                    <?php foreach ($perspectivas as $pk => $pv): if (!in_array($pk, $perspActivas)) continue; ?>
                    <option value="<?= $pk ?>"><?= $pv ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label small">Nombre del objetivo</label>
                <input id="nobj-nombre" class="form-control form-control-sm" placeholder="Ej: Aumentar margen EBITDA...">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="guardarNuevoObj()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
function guardarBSC() {
    var status = document.getElementById('bscStatus');
    status.innerHTML = '<div class="alert alert-success py-1 small">Mapa guardado</div>';
    setTimeout(function(){ status.innerHTML = ''; }, 2000);
    if (typeof updateCompleteButtons === 'function') updateCompleteButtons();
}

var bscPerspPreset = 'financiera';
function abrirNuevoObj(persp) {
    bscPerspPreset = persp;
    document.getElementById('nobj-persp').value = persp;
    document.getElementById('nobj-nombre').value = '';
    var modal = new bootstrap.Modal(document.getElementById('modalNuevoObj'));
    modal.show();
}

async function guardarNuevoObj() {
    var persp = document.getElementById('nobj-persp').value;
    var nombre = document.getElementById('nobj-nombre').value.trim();
    if (!nombre) { alert('Debe escribir un nombre'); return; }
    try {
        var modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoObj'));
        if (modal) modal.hide();
        await fetch('/tools/save-objetivo', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: 'plan_id=<?= $planId ?>&obj_nombre='+encodeURIComponent(nombre)+'&obj_perspectiva='+persp});
        setTimeout(function(){ location.reload(); }, 300);
    } catch(e) { alert('Error al crear objetivo'); }
}

async function addObjetivoBSC() { abrirNuevoObj('cliente'); }

function addRelacion() {
    var tbody = document.querySelector('#bscMatrix tbody');
    var row = document.createElement('tr');
    row.innerHTML = '<td><input class="form-control form-control-sm" placeholder="Causa..."></td><td>&rarr;</td><td><input class="form-control form-control-sm" placeholder="Efecto..."></td><td><select class="form-select form-select-sm"><option>Fuerte</option><option>Media</option><option>Débil</option></select></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove()">&times;</button></td>';
    tbody.appendChild(row);
}

async function generarRelaciones() {
    var btn = event.target;
    btn.disabled = true; btn.innerHTML = '&#9203; Sugiriendo...';
    var objNames = [];
    document.querySelectorAll('.bsc-node strong').forEach(function(el){ objNames.push(el.textContent.trim()); });
    var contexto = encodeURIComponent("Objetivos: " + objNames.join(", "));
    try {
        var r = await fetch('/generar', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'tipo=bsc-relaciones&plan_id=<?= $planId ?>&contexto='+contexto});
        var d = await r.json();
        if (d.success && d.contenido) {
            var rels = JSON.parse(d.contenido);
            var tbody = document.querySelector('#bscMatrix tbody');
            // Recolectar pares (causa+efecto) ya existentes para evitar duplicados
            var existentes = [];
            tbody.querySelectorAll('tr').forEach(function(row){
                var inputs = row.querySelectorAll('input');
                if (inputs.length >= 2 && inputs[0].value && inputs[1].value) {
                    existentes.push((inputs[0].value.trim() + '|||' + inputs[1].value.trim()).toLowerCase());
                }
            });
            var agregados = 0;
            rels.forEach(function(rel){
                var key = (rel.causa.trim() + '|||' + rel.efecto.trim()).toLowerCase();
                if (existentes.indexOf(key) === -1) {
                    var row = document.createElement('tr');
                    row.innerHTML = '<td><input class="form-control form-control-sm" value="'+rel.causa+'"></td><td>&rarr;</td><td><input class="form-control form-control-sm" value="'+rel.efecto+'"></td><td><select class="form-select form-select-sm"><option'+(rel.intensidad==='Fuerte'?' selected':'')+'>Fuerte</option><option'+(rel.intensidad==='Media'?' selected':'')+'>Media</option><option'+(rel.intensidad==='Débil'?' selected':'')+'>Débil</option></select></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'tr\').remove()">&times;</button></td>';
                    tbody.appendChild(row);
                    existentes.push(key);
                    agregados++;
                }
            });
            var status = document.getElementById('bscStatus');
            status.innerHTML = '<div class="alert alert-info py-1 small">'+agregados+' nuevas relaciones agregadas</div>';
            setTimeout(function(){ status.innerHTML = ''; }, 2000);
        }
    } catch(e) { console.error(e); }
    btn.disabled = false; btn.innerHTML = '&#129504; Sugerir relaciones';
}

async function generarBSC() {
    var btn = event.target;
    btn.disabled = true; btn.innerHTML = '&#9203; Generando...';
    try {
        var r = await fetch('/generar', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'tipo=bsc&plan_id=<?= $planId ?>&contexto=Genera objetivos estratégicos para cada perspectiva del BSC'});
        var d = await r.json();
        if (d.success) location.reload();
    } catch(e) {}
}

async function bscEditar(node) {
    var id = parseInt(node.getAttribute('data-obj-id'));
    var name = node.querySelector('.bsc-obj-name').textContent.trim();
    var nuevo = prompt('Editar nombre:', name);
    if (!nuevo || nuevo === name) return;
    try {
        await fetch('/tools/edit-objetivo', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'obj_id='+id+'&obj_nombre='+encodeURIComponent(nuevo)});
        location.reload();
    } catch(e) { alert('Error al editar'); }
}

async function bscEliminar(node) {
    if (!confirm('¿Eliminar este objetivo?')) return;
    var id = parseInt(node.getAttribute('data-obj-id'));
    try {
        await fetch('/tools/delete-objetivo', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'obj_id='+id});
        location.reload();
    } catch(e) { alert('Error al eliminar'); }
}
</script>

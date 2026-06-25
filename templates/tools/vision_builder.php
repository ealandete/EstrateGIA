<div class="row g-4">
    <div class="col-md-4">
        <?php $faseAnterior = $fases[$faseActualIdx - 1] ?? null; $tienePestel = false; $pestelData = []; ?>
        <?php if ($faseAnterior && in_array($faseAnterior['fase_estado']??'', ['completada','aprobada'])): ?>
        <div class="alert alert-success small mb-3">
            <i class="fas fa-check-circle me-1"></i> <strong><?= htmlspecialchars($faseAnterior['fase_nombre']) ?></strong> completada.
            <?php $pestelData = json_decode($faseAnterior['fase_guia_paso_a_paso']??'{}', true); $tienePestel = !empty($pestelData['politico'] ?? $pestelData['economico'] ?? []); ?>
            <?php if ($tienePestel): ?><br><small class="text-muted">El análisis PESTEL está disponible. Úsalo como referencia para definir la dirección estratégica.</small><?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-eye me-2" style="color:#e91e63"></i>Constructos Estratégicos</div>
            <div class="card-box-body">
                <p class="small text-muted">Define los pilares fundamentales de tu estrategia. La IA te ayudará a generar borradores alineados con tu sector.</p>
                <button class="btn btn-purple btn-sm w-100 mb-2" onclick="generarIA('mision')"><i class="fas fa-brain me-1"></i>Generar Misión</button>
                <button class="btn btn-purple btn-sm w-100 mb-2" onclick="generarIA('vision')"><i class="fas fa-brain me-1"></i>Generar Visión</button>
                <button class="btn btn-purple btn-sm w-100 mb-2" onclick="generarIA('valores')"><i class="fas fa-brain me-1"></i>Generar Valores</button>
                <button class="btn btn-success btn-sm w-100" onclick="guardarVision()"><i class="fas fa-save me-1"></i>Guardar</button>
                <div id="visionStatus" class="mt-2"></div>
                <?php if ($tienePestel): ?>
                <div class="mt-3 p-2 border rounded small" style="background:#f8f9fa">
                    <strong>Análisis previo (Fase 1):</strong>
                    <?php foreach (['politico'=>'Político','economico'=>'Económico','social'=>'Social','tecnologico'=>'Tecnológico'] as $k=>$v): if(!empty($pestelData[$k])): ?>
                    <div class="mt-1"><strong><?=$v?>:</strong> <?= implode(', ', array_slice($pestelData[$k],0,2)) ?></div>
                    <?php endif; endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-bullseye me-2" style="color:#e91e63"></i>Misión</div>
            <div class="card-box-body">
                <textarea id="misionText" class="form-control" rows="4" placeholder="¿Qué hace la empresa? ¿Para quién? ¿Cómo lo hace?"><?= htmlspecialchars($empresa['empresa_mision'] ?? '') ?></textarea>
                <small class="text-muted">Responde: ¿qué hacemos?, ¿para quién?, ¿cómo lo hacemos?</small>
            </div>
        </div>
        <div class="card-box mb-3">
            <div class="card-box-header"><i class="fas fa-eye me-2" style="color:#e91e63"></i>Visión</div>
            <div class="card-box-body">
                <textarea id="visionText" class="form-control" rows="4" placeholder="¿Qué queremos llegar a ser en 3-5 años?"><?= htmlspecialchars($empresa['empresa_vision'] ?? '') ?></textarea>
                <small class="text-muted">Responde: ¿qué queremos ser?, ¿para cuándo?, ¿cómo nos verán?</small>
            </div>
        </div>
        <div class="card-box">
            <div class="card-box-header"><i class="fas fa-heart me-2" style="color:#e91e63"></i>Valores Corporativos</div>
            <div class="card-box-body">
                <div id="valoresList">
                    <?php $valores = json_decode($empresa['empresa_valores'] ?? '[]', true) ?: []; ?>
                    <?php foreach ($valores as $i => $v): ?>
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text"><?= $i+1 ?></span>
                        <input type="text" class="form-control valor-item" value="<?= htmlspecialchars($v) ?>" placeholder="Valor corporativo">
                        <button class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">×</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-sm btn-outline-secondary" onclick="addValor()"><i class="fas fa-plus me-1"></i>Añadir valor</button>
            </div>
        </div>
    </div>
</div>
<script>
function addValor(){const d=document.createElement('div');d.className='input-group input-group-sm mb-2';const n=document.querySelectorAll('.valor-item').length+1;d.innerHTML=`<span class="input-group-text">${n}</span><input type="text" class="form-control valor-item" placeholder="Valor corporativo"><button class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">&times;</button>`;document.getElementById('valoresList').appendChild(d)}
async function generarIA(t){const b=event.target;b.disabled=true;b.innerHTML='<i class="fas fa-spinner fa-spin"></i>';try{const r=await fetch('/generar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`tipo=${t}&plan_id=<?=$planId?>`});const d=await r.json();if(d.success){if(t==='mision')document.getElementById('misionText').value=d.contenido;if(t==='vision')document.getElementById('visionText').value=d.contenido;if(t==='valores'&&d.contenido){const vals=d.contenido.split('\n').filter(l=>l.match(/^\d+\./)).map(l=>l.replace(/^\d+\.\s*/,''));document.getElementById('valoresList').innerHTML='';vals.forEach((v,i)=>{const div=document.createElement('div');div.className='input-group input-group-sm mb-2';div.innerHTML=`<span class="input-group-text">${i+1}</span><input type="text" class="form-control valor-item" value="${v.replace(/"/g,'&quot;')}"><button class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()">&times;</button>`;document.getElementById('valoresList').appendChild(div)})}document.getElementById('visionStatus').innerHTML='<div class="alert alert-success py-1 px-2 small mt-1"><i class="fas fa-check-circle"></i> Generado con IA</div>'}}catch(e){}b.disabled=false;b.innerHTML='<i class="fas fa-brain me-1"></i>Generar '+t}
async function guardarVision(){const d={mision:document.getElementById('misionText').value,vision:document.getElementById('visionText').value,valores:Array.from(document.querySelectorAll('.valor-item')).map(e=>e.value).filter(v=>v)};document.getElementById('visionStatus').innerHTML='<div class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Guardando...</div>';try{const r=await fetch('/tools/save-foda',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'plan_id=<?=$planId?>&foda_data='+encodeURIComponent(JSON.stringify(d))});if(r.ok){document.getElementById('visionStatus').innerHTML='<div class="alert alert-success py-1 px-2 small mt-1"><i class="fas fa-check-circle"></i> Guardado</div>';var btn=document.getElementById('btnCompletar');var btnB=document.getElementById('btnCompletarBottom');[btn,btnB].forEach(function(b){if(b){b.disabled=false;b.className=b.id==='btnCompletarBottom'?'btn btn-success btn-lg':'btn btn-success';}})}else{document.getElementById('visionStatus').innerHTML='<div class="alert alert-danger py-1 px-2 small mt-1">Error al guardar</div>'}}catch(e){document.getElementById('visionStatus').innerHTML='<div class="alert alert-danger py-1 px-2 small mt-1">Error</div>'}}
</script>

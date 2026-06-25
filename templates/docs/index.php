<style>
.doc-hero {
    background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
    color: white;
    padding: 40px;
    border-radius: 16px;
    margin-bottom: 30px;
    text-align: center;
}
.doc-hero h2 { font-size: 2rem; margin-bottom: 10px; }
.doc-hero p { opacity: 0.9; font-size: 1.1rem; }
.doc-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-top: 20px;
}
.doc-stat {
    text-align: center;
}
.doc-stat-value {
    font-size: 2rem;
    font-weight: 700;
}
.doc-stat-label {
    font-size: 0.85rem;
    opacity: 0.8;
}
.doc-category {
    margin-bottom: 40px;
}
.doc-category-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e0e0e0;
}
.doc-category-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}
.doc-category-title {
    font-size: 1.3rem;
    font-weight: 600;
    margin: 0;
}
.doc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}
.doc-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    overflow: hidden;
    transition: all 0.2s;
}
.doc-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.doc-card-header {
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
}
.doc-card-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #333;
}
.doc-card-desc {
    font-size: 0.9rem;
    color: #666;
    margin: 0;
    line-height: 1.5;
}
.doc-card-body {
    padding: 15px 20px;
    background: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.doc-card-meta {
    display: flex;
    gap: 15px;
    font-size: 0.8rem;
    color: #888;
}
.doc-card-actions {
    display: flex;
    gap: 8px;
}
.doc-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}
.doc-btn-pdf {
    background: #dc3545;
    color: white;
}
.doc-btn-pdf:hover {
    background: #c82333;
    color: white;
}
.doc-btn-html {
    background: #1a73e8;
    color: white;
}
.doc-btn-html:hover {
    background: #0d47a1;
    color: white;
}
.doc-search {
    max-width: 500px;
    margin: 0 auto 30px;
}
.doc-search input {
    width: 100%;
    padding: 12px 20px 12px 45px;
    border: 2px solid #e0e0e0;
    border-radius: 25px;
    font-size: 1rem;
    outline: none;
    transition: border-color 0.2s;
}
.doc-search input:focus {
    border-color: #1a73e8;
}
.doc-search-wrap {
    position: relative;
}
.doc-search-icon {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: #888;
}
</style>

<div class="doc-hero">
    <h2><i class="fas fa-book-open me-2"></i>Centro de Documentación</h2>
    <p>EstrateGIA v2.1 — Todos los manuales y documentación técnica</p>
    <div class="doc-stats">
        <div class="doc-stat">
            <div class="doc-stat-value">9</div>
            <div class="doc-stat-label">Documentos</div>
        </div>
        <div class="doc-stat">
            <div class="doc-stat-value">343</div>
            <div class="doc-stat-label">Páginas</div>
        </div>
        <div class="doc-stat">
            <div class="doc-stat-value">18.4</div>
            <div class="doc-stat-label">MB Total</div>
        </div>
    </div>
</div>

<div class="doc-search">
    <div class="doc-search-wrap">
        <i class="fas fa-search doc-search-icon"></i>
        <input type="text" id="docSearch" placeholder="Buscar documentos..." onkeyup="filterDocs()">
    </div>
</div>

<?php foreach ($docs as $catKey => $cat): ?>
<div class="doc-category" data-category="<?= $catKey ?>">
    <div class="doc-category-header">
        <div class="doc-category-icon" style="background:<?= $cat['color'] ?>">
            <i class="fas fa-<?= $cat['icon'] ?>"></i>
        </div>
        <h3 class="doc-category-title"><?= $cat['titulo'] ?></h3>
    </div>
    <div class="doc-grid">
        <?php foreach ($cat['documentos'] as $doc): ?>
        <div class="doc-card" data-name="<?= strtolower($doc['nombre']) ?>">
            <div class="doc-card-header">
                <h4 class="doc-card-title"><?= $doc['nombre'] ?></h4>
                <p class="doc-card-desc"><?= $doc['descripcion'] ?></p>
            </div>
            <div class="doc-card-body">
                <div class="doc-card-meta">
                    <span><i class="fas fa-file-lines me-1"></i><?= $doc['paginas'] ?> págs</span>
                    <span><i class="fas fa-weight-hanging me-1"></i><?= $doc['tamano'] ?></span>
                </div>
                <div class="doc-card-actions">
                    <a href="<?= $doc['pdf'] ?>" class="doc-btn doc-btn-pdf" target="_blank">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="<?= $doc['html'] ?>" class="doc-btn doc-btn-html" target="_blank">
                        <i class="fas fa-globe"></i> HTML
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<script>
function filterDocs() {
    const query = document.getElementById('docSearch').value.toLowerCase();
    document.querySelectorAll('.doc-card').forEach(card => {
        const name = card.dataset.name;
        card.style.display = name.includes(query) ? '' : 'none';
    });
}
</script>

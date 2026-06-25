<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(($titulo ?? 'Reporte HSE') . ' - ' . ($empresa_nombre ?? 'EstrateGIA')) ?></title>
<style>
  :root {
    --brand: #1a1e34;
    --text: #333;
    --text-muted: #666;
    --border: #d0d4da;
    --border-light: #e8eaee;
    --bg-header: #f4f5f7;
    --bg-alt: #fafbfc;
    --accent: #2563eb;
    --accent-light: #eff6ff;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    color: var(--text);
    font-size: 12pt;
    line-height: 1.6;
    background: #fff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .container {
    max-width: 190mm;
    margin: 0 auto;
    padding: 15mm 20mm;
  }

  /* ── Encabezado ── */
  .header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 3px solid var(--brand);
    padding-bottom: 14px;
    margin-bottom: 24px;
  }

  .header-left .logo {
    font-size: 22pt;
    font-weight: 700;
    color: var(--brand);
    letter-spacing: -0.5px;
    line-height: 1.2;
  }
  .header-left .logo span { color: var(--accent); }

  .header-right {
    text-align: right;
    font-size: 9pt;
    color: var(--text-muted);
    line-height: 1.7;
  }
  .header-right strong {
    color: var(--text);
    font-size: 10pt;
  }

  .report-title-block {
    margin-bottom: 22px;
  }
  .report-title {
    font-size: 16pt;
    font-weight: 700;
    color: var(--brand);
    margin-bottom: 4px;
  }
  .report-subtitle {
    font-size: 9pt;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1.5px;
  }

  /* ── Norma badge ── */
  .norma-badge {
    display: inline-block;
    background: var(--accent-light);
    color: var(--accent);
    border: 1px solid #bfdbfe;
    border-radius: 4px;
    padding: 3px 10px;
    font-size: 9pt;
    font-weight: 600;
    margin-top: 8px;
  }

  /* ── KPIs resumen ── */
  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 22px;
  }
  .kpi-card {
    background: var(--bg-header);
    border: 1px solid var(--border-light);
    border-radius: 6px;
    padding: 12px 14px;
    text-align: center;
  }
  .kpi-card .kpi-value {
    font-size: 20pt;
    font-weight: 700;
    color: var(--brand);
    line-height: 1.2;
  }
  .kpi-card .kpi-label {
    font-size: 8pt;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-top: 2px;
  }

  /* ── Tabla ── */
  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9.5pt;
    margin-bottom: 22px;
    page-break-inside: auto;
  }
  thead { display: table-header-group; }
  tr { page-break-inside: avoid; }

  th {
    background: var(--brand);
    color: #fff;
    font-weight: 600;
    text-align: left;
    padding: 9px 10px;
    font-size: 8.5pt;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid #151a2c;
  }

  td {
    padding: 7px 10px;
    border: 1px solid var(--border);
    vertical-align: top;
  }
  tbody tr:nth-child(even) td { background: var(--bg-alt); }

  .table-caption {
    font-size: 9pt;
    font-weight: 600;
    color: var(--brand);
    margin-bottom: 8px;
  }

  .table-info {
    display: flex;
    justify-content: space-between;
    font-size: 8.5pt;
    color: var(--text-muted);
    margin-bottom: 6px;
  }

  /* ── Pie de página ── */
  .footer {
    position: fixed;
    bottom: 0;
    left: 20mm;
    right: 20mm;
    border-top: 1px solid var(--border);
    padding-top: 8px;
    font-size: 8pt;
    color: var(--text-muted);
    display: flex;
    justify-content: space-between;
  }
  .footer .page-number::after {
    content: counter(page);
  }

  /* ── Botones (ocultos al imprimir) ── */
  .no-print { margin-bottom: 16px; }
  .no-print .btn-print {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid var(--border);
    background: var(--bg-header);
    color: var(--text);
    border-radius: 6px;
    padding: 8px 18px;
    font-size: 10pt;
    cursor: pointer;
    transition: background 0.15s;
  }
  .no-print .btn-print:hover { background: var(--border-light); }

  /* ── Vacío ── */
  .empty-state {
    text-align: center;
    padding: 48px 0;
    color: var(--text-muted);
  }
  .empty-state .empty-icon { font-size: 36pt; margin-bottom: 10px; opacity: 0.4; }

  /* ── Notas ── */
  .notes-block {
    margin-top: 22px;
    padding: 14px 16px;
    background: var(--bg-header);
    border: 1px solid var(--border-light);
    border-radius: 6px;
    font-size: 9pt;
    color: var(--text-muted);
    line-height: 1.7;
  }
  .notes-block strong { color: var(--text); }

  /* ── PRINT ── */
  @page {
    size: A4;
    margin: 0;
  }

  @media print {
    .no-print { display: none !important; }
    body {
      font-size: 11pt;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .container {
      max-width: 100%;
      padding: 12mm 16mm;
    }
    th {
      background: var(--brand) !important;
      color: #fff !important;
      -webkit-print-color-adjust: exact;
    }
    .footer {
      position: fixed;
      bottom: 0;
    }
    .kpi-card {
      background: var(--bg-header) !important;
      -webkit-print-color-adjust: exact;
    }
  }
</style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Imprimir / Guardar PDF
  </button>
</div>

<div class="container">

  <!-- ── ENCABEZADO ── -->
  <div class="header">
    <div class="header-left">
      <div class="logo">Estrate<span>GIA</span></div>
    </div>
    <div class="header-right">
      <?php if (!empty($empresa_nombre)): ?>
        <strong><?= htmlspecialchars($empresa_nombre) ?></strong><br>
      <?php endif; ?>
      <?php if (!empty($fecha_generacion)): ?>
        Fecha: <?= htmlspecialchars($fecha_generacion) ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── TÍTULO ── -->
  <div class="report-title-block">
    <div class="report-subtitle">SST &amp; Ambiental</div>
    <div class="report-title"><?= htmlspecialchars($titulo ?? 'Reporte HSE') ?></div>
    <?php if (!empty($norma)): ?>
      <div class="norma-badge"><?= htmlspecialchars($norma) ?></div>
    <?php endif; ?>
  </div>

  <!-- ── RESUMEN KPIs ── -->
  <?php if (!empty($resumen) && is_array($resumen)): ?>
  <div class="kpi-grid">
    <?php foreach ($resumen as $k => $v): ?>
      <div class="kpi-card">
        <div class="kpi-value"><?= htmlspecialchars((string)$v) ?></div>
        <div class="kpi-label"><?= htmlspecialchars($k) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── TABLA DE DATOS ── -->
  <?php if (!empty($datos) && is_array($datos) && !empty($columnas) && is_array($columnas)): ?>
    <div class="table-info">
      <span>Total registros: <strong><?= count($datos) ?></strong></span>
    </div>
    <table>
      <thead>
        <tr>
          <?php foreach ($columnas as $col): ?>
            <th><?= htmlspecialchars($col) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($datos as $fila): ?>
          <tr>
            <?php foreach ($columnas as $idx => $col): ?>
              <td><?= htmlspecialchars($fila[$idx] ?? $fila[$col] ?? '') ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php elseif (!empty($datos) && is_array($datos)): ?>
    <?php
      // Autodetectar columnas desde los datos
      $autoColumnas = [];
      foreach ($datos as $fila) {
          if (is_array($fila)) {
              $autoColumnas = array_unique(array_merge($autoColumnas, array_keys($fila)));
          }
      }
    ?>
    <?php if (!empty($autoColumnas)): ?>
      <div class="table-info">
        <span>Total registros: <strong><?= count($datos) ?></strong></span>
      </div>
      <table>
        <thead>
          <tr>
            <?php foreach ($autoColumnas as $col): ?>
              <th><?= htmlspecialchars($col) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($datos as $fila): ?>
            <?php if (is_array($fila)): ?>
            <tr>
              <?php foreach ($autoColumnas as $col): ?>
                <td><?= htmlspecialchars($fila[$col] ?? '') ?></td>
              <?php endforeach; ?>
            </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  <?php elseif (empty($datos)): ?>
    <div class="empty-state">
      <div class="empty-icon">&#128203;</div>
      <p>No hay datos disponibles para este reporte.</p>
    </div>
  <?php endif; ?>

  <!-- ── NOTAS ── -->
  <div class="notes-block">
    <strong>Nota:</strong>
    Este reporte fue generado autom&aacute;ticamente por EstrateGIA&reg;.
    <?php if (!empty($norma)): ?>
      La informaci&oacute;n presentada se rige bajo los lineamientos de <strong><?= htmlspecialchars($norma) ?></strong>.
    <?php endif; ?>
    Para cualquier aclaraci&oacute;n, contacte al &aacute;rea de SST y Gesti&oacute;n Ambiental.
  </div>

</div>

<!-- ── PIE DE PÁGINA ── -->
<div class="footer">
  <span><?= htmlspecialchars($fecha_generacion ?? date('Y-m-d')) ?></span>
  <span class="page-number">P&aacute;gina </span>
</div>

</body>
</html>

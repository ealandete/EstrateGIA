<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 500 (DEBUG) - EstrateGIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8f9fa; }
        .debug-card { max-width: 800px; width: 100%; padding: 32px; }
        .exception { margin-top: 16px; }
        pre.trace { background: #212529; color: #f8f9fa; padding: 12px; border-radius: 8px; font-size: 0.8rem; max-height: 300px; overflow-y: auto; }
        .countdown { font-weight: 700; color: #0d6efd; }
    </style>
</head>
<body>
    <div class="debug-card">
        <div class="card shadow">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-bug me-2"></i>Error 500 — Modo DEBUG</h5>
            </div>
            <div class="card-body">
                <div class="exception">
                    <h6 class="text-danger"><?= htmlspecialchars(get_class($exception) ?? 'Exception') ?></h6>
                    <p><strong>Mensaje:</strong> <?= htmlspecialchars($exception->getMessage()) ?></p>
                    <p><strong>Archivo:</strong> <?= htmlspecialchars($exception->getFile()) ?>:<?= $exception->getLine() ?></p>
                    <?php if (!empty($diagnosis)): ?>
                    <div class="alert alert-info mt-3">
                        <strong>Diagn&oacute;stico N1:</strong><br>
                        Tipo: <?= htmlspecialchars($diagnosis['type'] ?? 'N/A') ?><br>
                        Severidad: <?= htmlspecialchars($diagnosis['severity'] ?? 'N/A') ?><br>
                        <?= htmlspecialchars($diagnosis['diagnosis'] ?? '') ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($ticketId)): ?>
                    <div class="alert alert-secondary">Ticket de soporte #<?= (int)$ticketId ?></div>
                    <?php endif; ?>
                    <pre class="trace"><?= htmlspecialchars($exception->getTraceAsString()) ?></pre>
                    <?php if (!empty($requestUri)): ?>
                    <p class="mt-2"><strong>URL:</strong> <?= htmlspecialchars($requestUri) ?></p>
                    <?php endif; ?>
                </div>
                <div class="mt-3">
                    <span class="countdown" id="countdown">5</span> segundos para reintento...
                    <a href="/" class="btn btn-sm btn-outline-secondary ms-2">Ir al inicio</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        var sec = 5;
        var el = document.getElementById('countdown');
        var timer = setInterval(function() {
            sec--;
            if (sec <= 0) { clearInterval(timer); location.reload(); }
            else { el.textContent = sec; }
        }, 1000);
    </script>
</body>
</html>

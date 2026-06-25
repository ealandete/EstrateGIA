<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - EstrateGIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8f9fa; }
        .error-card { text-align: center; max-width: 500px; padding: 48px; }
        .error-code { font-size: 5rem; font-weight: 800; color: #dc3545; margin-bottom: 0; line-height: 1; }
        .ticket-id { font-size: 0.85rem; color: #6c757d; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">500</div>
        <h2 class="mt-3 mb-3">Algo sali&oacute; mal</h2>
        <p class="text-muted">Estamos trabajando para resolverlo. Por favor intenta de nuevo en unos momentos.</p>
        <?php if (!empty($ticketId)): ?>
        <div class="ticket-id">Ticket de soporte #<?= (int)$ticketId ?></div>
        <?php endif; ?>
        <a href="/" class="btn btn-primary mt-4">Volver al inicio</a>
    </div>
</body>
</html>

<?php

class ExportManager {

    /**
     * Exporta un array de datos a formato CSV y fuerza la descarga
     */
    public static function toCSV(array $data, string $filename = 'export'): void {
        if (empty($data)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Sin datos para exportar']);
            exit;
        }

        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $output = fopen('php://output', 'w');
        // BOM para UTF-8 en Excel
        fwrite($output, "\xEF\xBB\xBF");

        // Cabeceras
        $headers = array_keys(reset($data));
        fputcsv($output, $headers);

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Exporta un array de datos a JSON
     */
    public static function toJSON(array $data, string $filename = 'export'): void {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '_' . date('Ymd_His') . '.json';
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Agrega botones de exportación a una tabla HTML
     */
    public static function renderExportButtons(string $tableId, string $filename = 'export'): string {
        return '<div class="d-flex gap-1 mb-1 no-print">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarTabla(\''.$tableId.'\',\'csv\',\''.$filename.'\')" title="Exportar CSV" style="font-size:0.65rem">CSV</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarTabla(\''.$tableId.'\',\'json\',\''.$filename.'\')" title="Exportar JSON" style="font-size:0.65rem">JSON</button>
        </div>';
    }

    /**
     * Genera el JS necesario para la exportación (incluir una vez por página)
     */
    public static function renderExportJS(): string {
        return '<script>
        function exportarTabla(tableId, format, filename) {
            var table = document.getElementById(tableId);
            if (!table) { alert("Tabla no encontrada"); return; }
            var rows = table.querySelectorAll("tr");
            var data = [];
            rows.forEach(function(row) {
                var r = [];
                row.querySelectorAll("th, td").forEach(function(cell) {
                    r.push(cell.textContent.trim());
                });
                data.push(r.join(","));
            });
            if (format === "csv") {
                var bom = "\uFEFF";
                var csv = bom + data.join("\n");
                var blob = new Blob([csv], {type: "text/csv;charset=utf-8"});
                var url = URL.createObjectURL(blob);
                var a = document.createElement("a");
                a.href = url;
                a.download = filename + "_" + new Date().toISOString().slice(0,10) + ".csv";
                a.click();
            } else if (format === "json") {
                var json = [];
                var headers = [];
                rows[0].querySelectorAll("th, td").forEach(function(cell) { headers.push(cell.textContent.trim()); });
                for (var i = 1; i < rows.length; i++) {
                    var obj = {};
                    rows[i].querySelectorAll("td").forEach(function(cell, j) {
                        obj[headers[j] || "col_" + j] = cell.textContent.trim();
                    });
                    json.push(obj);
                }
                var blob = new Blob([JSON.stringify(json, null, 2)], {type: "application/json"});
                var url = URL.createObjectURL(blob);
                var a = document.createElement("a");
                a.href = url;
                a.download = filename + "_" + new Date().toISOString().slice(0,10) + ".json";
                a.click();
            }
        }
        </script>';
    }

}

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
     * Exporta un array de datos a Excel (HTML table for .xls compatibility)
     */
    public static function toExcel(array $data, string $filename = 'export'): void {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename) . '_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<table border="1">';
        if (!empty($data)) {
            echo '<tr>';
            foreach (array_keys(reset($data)) as $h) {
                echo '<th>' . htmlspecialchars($h) . '</th>';
            }
            echo '</tr>';
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($row as $v) {
                    echo '<td>' . htmlspecialchars((string)$v) . '</td>';
                }
                echo '</tr>';
            }
        }
        echo '</table></body></html>';
        exit;
    }

    /**
     * Agrega botones de exportación a una tabla HTML
     */
    public static function renderExportButtons(string $tableId, string $filename = 'export'): string {
        return '<div class="d-flex gap-1 mb-1 no-print">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarTabla(\''.$tableId.'\',\'csv\',\''.$filename.'\')" title="Exportar CSV" style="font-size:0.65rem">CSV</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarTabla(\''.$tableId.'\',\'xls\',\''.$filename.'\')" title="Exportar Excel" style="font-size:0.65rem">XLS</button>
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
            var headers = [];
            rows[0].querySelectorAll("th, td").forEach(function(cell) { headers.push(cell.textContent.trim()); });
            for (var i = 1; i < rows.length; i++) {
                var obj = {};
                rows[i].querySelectorAll("td").forEach(function(cell, j) {
                    obj[headers[j] || "col_" + j] = cell.textContent.trim();
                });
                data.push(obj);
            }
            if (format === "csv") {
                var bom = "\uFEFF";
                var csv = bom + headers.map(function(h){return h.replace(/,/g,";")}).join(",") + "\n";
                data.forEach(function(row) {
                    var vals = headers.map(function(h) {
                        var v = String(row[h] || "");
                        if (v.indexOf(",") >= 0 || v.indexOf(\'"\') >= 0) v = \'"\' + v.replace(/"/g,\'""\') + \'"\';
                        return v;
                    });
                    csv += vals.join(",") + "\n";
                });
                var blob = new Blob([csv], {type: "text/csv;charset=utf-8"});
                var url = URL.createObjectURL(blob);
                var a = document.createElement("a");
                a.href = url;
                a.download = filename + "_" + new Date().toISOString().slice(0,10) + ".csv";
                a.click();
            } else if (format === "xls") {
                var html = "<html><head><meta charset=UTF-8></head><body><table border=1>";
                html += "<tr>" + headers.map(function(h){return "<th>"+h+"</th>";}).join("") + "</tr>";
                data.forEach(function(row){
                    html += "<tr>";
                    headers.forEach(function(h){ html += "<td>" + (row[h]||"") + "</td>"; });
                    html += "</tr>";
                });
                html += "</table></body></html>";
                var blob = new Blob([html], {type: "application/vnd.ms-excel;charset=utf-8"});
                var url = URL.createObjectURL(blob);
                var a = document.createElement("a");
                a.href = url;
                a.download = filename + "_" + new Date().toISOString().slice(0,10) + ".xls";
                a.click();
            } else if (format === "json") {
                var blob = new Blob([JSON.stringify(data, null, 2)], {type: "application/json"});
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

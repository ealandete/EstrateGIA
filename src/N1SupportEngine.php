<?php
declare(strict_types=1);

/**
 * N1SupportEngine — Motor Experto de Soporte N1
 * Diagnostica, categoriza y resuelve errores automáticamente
 * Política: Corregir > Documentar > Escalar
 * @since 2026-06-09
 */
class N1SupportEngine {
    private $db;
    private string $errorMsg;
    private string $errorFile;
    private int $errorLine;
    private string $requestUri;
    private string $errorTrace = '';
    
    public function __construct(string $msg, string $file, int $line, string $uri, string $trace = '') {
        $this->errorMsg = $msg;
        $this->errorFile = $file;
        $this->errorLine = $line;
        $this->requestUri = $uri;
        $this->errorTrace = $trace;
        $core = EstrateGiaCore::getInstance();
        $this->db = $core->getPDO();
    }

    /**
     * Categoriza el error y determina la acción
     * @return array {type, severity, diagnosis, fix_applied, auto_fixable, escalated}
     */
    public function analyze(): array {
        // Patrón 1: Columna desconocida en SQL
        if (preg_match("/Unknown column '([^']+)' in '([^']+)'/", $this->errorMsg, $m)) {
            return $this->fixMissingColumn($m[1], $m[2]);
        }
        
        // Patrón 2: Tabla no existe
        if (preg_match("/Table '.*?\.(.*?)' doesn't exist/", $this->errorMsg, $m)) {
            return $this->diagnoseMissingTable($m[1]);
        }
        
        // Patrón 3: Clase no encontrada (autoloader)
        if (preg_match("/Class \"(\w+)\" not found/", $this->errorMsg, $m)) {
            return $this->diagnoseMissingClass($m[1]);
        }
        
        // Patrón 4: Undefined array key
        if (preg_match("/Undefined array key \"(\w+)\"/", $this->errorMsg, $m)) {
            return $this->fixMissingArrayKey($m[1]);
        }
        
        // Patrón 5: Call to member function on null
        if (preg_match("/Call to a member function (\w+)\(\) on null/", $this->errorMsg, $m)) {
            return $this->diagnoseNullCall($m[1]);
        }
        
        // Patrón 6: Ruta no encontrada (404)
        if (str_contains($this->errorMsg, '404') || str_contains($this->errorMsg, 'no encontrada')) {
            return $this->diagnoseRoute404();
        }
        
        // Patrón 7: Error de tipo (return type mismatch)
        if (preg_match("/must be of type (\w+), (\w+) returned/", $this->errorMsg, $m)) {
            return $this->fixTypeMismatch($m[1], $m[2]);
        }
        
        // Patrón 8: Permiso denegado
        if (str_contains($this->errorMsg, 'permisos') || str_contains($this->errorMsg, 'canAccess')) {
            return $this->diagnosePermission();
        }
        
        // Desconocido — escalar a N2
        return [
            'type' => 'DESCONOCIDO',
            'severity' => 'ALTA',
            'diagnosis' => 'Error no categorizado por el motor N1',
            'fix_applied' => false,
            'auto_fixable' => false,
            'escalated' => true,
            'recommendation' => 'Revisar manualmente el archivo ' . $this->errorFile . ' línea ' . $this->errorLine
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // DIAGNÓSTICOS Y CORRECCIONES AUTOMÁTICAS
    // ═══════════════════════════════════════════════════════════

    private function fixMissingColumn(string $col, string $context): array {
        // Intentar encontrar la tabla real (resolver alias)
        $tableGuess = $this->guessTableFromContext($context);
        $suggestion = $this->findSimilarColumn($tableGuess, $col);
        
        if ($suggestion) {
            $this->applyColumnFix($this->errorFile, $col, $suggestion);
            return [
                'type' => 'COLUMNA_INEXISTENTE',
                'severity' => 'MEDIA',
                'diagnosis' => "Columna '$col' no existe en '$tableGuess'. Reemplazada por '$suggestion'.",
                'fix_applied' => true,
                'auto_fixable' => true,
                'escalated' => false,
                'detail' => "Archivo: {$this->errorFile}:{$this->errorLine}"
            ];
        }
        
        // Si no se encuentra reemplazo, sugerir usar la primera columna disponible
        $cols = $this->getTableColumns($tableGuess);
        $alternatives = array_slice($cols, 0, 3);
        return [
            'type' => 'COLUMNA_INEXISTENTE',
            'severity' => 'ALTA',
            'diagnosis' => "Columna '$col' no existe en '$tableGuess'. Columnas disponibles: " . implode(', ', $alternatives) . "...",
            'fix_applied' => false,
            'auto_fixable' => false,
            'escalated' => true,
            'detail' => "Reemplace manualmente '$col' por una de las columnas existentes o elimínela del SELECT."
        ];
    }

    private function guessTableFromContext(string $context): string {
        // Resolver alias: "FROM ref_contrareferencias cr" → ref_contrareferencias
        if (preg_match('/FROM\s+(\w+)\s+(\w+)/i', $context, $m)) return $m[1];
        if (preg_match('/JOIN\s+(\w+)\s+(\w+)/i', $context, $m)) return $m[1];
        if (preg_match('/FROM\s+(\w+)/i', $context, $m)) return $m[1];
        return 'desconocida';
    }

    private function getTableColumns(string $table): array {
        try {
            return $this->db->query("SHOW COLUMNS FROM `$table`")->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) { return []; }
    }

    private function guessTable(string $context): string {
        return $this->guessTableFromContext($context);
    }

    private function findSimilarColumn(?string $table, string $col): ?string {
        if (!$table) return null;
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM `$table`")->fetchAll(\PDO::FETCH_COLUMN);
            // Búsqueda por similitud
            foreach ($cols as $c) {
                if (str_contains($c, $col) || str_contains($col, $c)) return $c;
            }
            // El singular/plural más común en español
            if (str_ends_with($col, 's')) {
                $singular = rtrim($col, 's');
                if (in_array($singular, $cols)) return $singular;
                if (in_array(rtrim($singular, 'e') . 'o', $cols)) return rtrim($singular, 'e') . 'o';
            }
            return null;
        } catch (\Exception $e) { return null; }
    }

    private function applyColumnFix(string $file, string $old, string $new): void {
        $content = file_get_contents($file);
        $content = str_replace(".$old", ".$new", $content);
        $content = str_replace("['$old']", "['$new']", $content);
        file_put_contents($file, $content);
    }

    /**
     * Crea un ticket de soporte con el diagnóstico
     */
    public function createTicket(array $diagnosis): int {
        $modulo = 'sistema';
        if (preg_match('#/modules/([^/]+)/#', $this->errorFile, $m)) $modulo = $m[1];
        if (preg_match('#/src/Controllers/([^/]+)#', $this->errorFile, $m)) $modulo = strtolower(str_replace('Controller','',$m[1]));

        $asunto = ($diagnosis['fix_applied'] ? '[AUTO-FIX] ' : '') .
                  $diagnosis['type'] . ': ' . substr($this->errorMsg, 0, 80);

        $descripcion = "Diagnostico N1:\n" .
            "Tipo: {$diagnosis['type']}\n" .
            "Severidad: {$diagnosis['severity']}\n" .
            "Archivo: {$this->errorFile}:{$this->errorLine}\n" .
            "URL: {$this->requestUri}\n" .
            "Diagnostico: {$diagnosis['diagnosis']}\n" .
            "Auto-corregido: " . ($diagnosis['fix_applied'] ? 'SI' : 'NO') . "\n" .
            "Detalle: " . ($diagnosis['detail'] ?? 'N/A');

        $prioridad = $diagnosis['escalated'] ? 'ALTA' : 'MEDIA';
        $estado = $diagnosis['fix_applied'] ? 'RESUELTO' : 'ABIERTO';
        $nivelActual = $diagnosis['escalated'] ? 'N2' : 'N1';
        $slaHoras = ['CRITICA' => 1, 'ALTA' => 4, 'MEDIA' => 8, 'BAJA' => 24][$prioridad] ?? 8;
        $slaVencimiento = date('Y-m-d H:i:s', time() + ($slaHoras * 3600));

        $this->db->prepare("INSERT INTO soporte_tickets (id_empresa, modulo_afectado, asunto, descripcion, prioridad, estado, nivel_actual, origen, creado_por, fecha_limite_sla) VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([1, $modulo, $asunto, $descripcion, $prioridad, $estado, $nivelActual, 'IA_DIAGNOSTICO', 'N1SupportEngine', $slaVencimiento]);

        // Registrar diagnostico como respuesta
        $ticketId = (int)$this->db->lastInsertId();
        $this->db->prepare("INSERT INTO soporte_respuestas (ticket_id, id_ticket, tipo, contenido, autor) VALUES (?,?,?,?,?)")
            ->execute([$ticketId, $ticketId, 'DIAGNOSTICO_IA', $diagnosis['diagnosis'], 'N1SupportEngine']);

        return $ticketId;
    }
}

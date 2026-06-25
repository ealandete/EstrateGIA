<?php
declare(strict_types=1);

/**
 * SafeQuery Trait para EstrateGIA
 * Protección SQL con prepared statements obligatorios
 * 
 * Uso:
 *   class MiController {
 *       use \SafeQuery;
 *       private $core;
 *       public function __construct() { $this->core = EstrateGiaCore::getInstance(); }
 *       
 *       public function index(): void {
 *           $total = (int)$this->safe("SELECT COUNT(*) FROM tabla WHERE id=?", [$id]);
 *           $rows = $this->safeAll("SELECT * FROM tabla WHERE id=? ORDER BY id DESC", [$id]);
 *       }
 *   }
 */

trait SafeQuery
{
    /**
     * Ejecuta una consulta SELECT y retorna un único valor escalar
     * 
     * @param string $sql Consulta SQL con placeholders (?)
     * @param array $params Parámetros para los placeholders
     * @return mixed Valor escalar (string, int, float, null)
     */
    protected function safe(string $sql, array $params = []): mixed
    {
        try {
            $stmt = $this->core->getPDO()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("SafeQuery Error: " . $e->getMessage() . " | SQL: " . $sql);
            return null;
        }
    }

    /**
     * Ejecuta una consulta SELECT y retorna todas las filas
     * 
     * @param string $sql Consulta SQL con placeholders (?)
     * @param array $params Parámetros para los placeholders
     * @return array Array de filas asociativas
     */
    protected function safeAll(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->core->getPDO()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("SafeQuery Error: " . $e->getMessage() . " | SQL: " . $sql);
            return [];
        }
    }

    /**
     * Ejecuta una consulta SELECT y retorna una única fila
     * 
     * @param string $sql Consulta SQL con placeholders (?)
     * @param array $params Parámetros para los placeholders
     * @return array|null Fila asociativa o null si no hay resultados
     */
    protected function safeOne(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->core->getPDO()->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("SafeQuery Error: " . $e->getMessage() . " | SQL: " . $sql);
            return null;
        }
    }

    /**
     * Ejecuta una consulta INSERT, UPDATE o DELETE
     * 
     * @param string $sql Consulta SQL con placeholders (?)
     * @param array $params Parámetros para los placeholders
     * @return int Número de filas afectadas
     */
    protected function safeExec(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->core->getPDO()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log("SafeQuery Error: " . $e->getMessage() . " | SQL: " . $sql);
            return 0;
        }
    }

    /**
     * Inserta un registro y retorna el ID generado
     * 
     * @param string $table Nombre de la tabla
     * @param array $data Array asociativo [columna => valor]
     * @return int|null ID del último insert o null si falla
     */
    protected function safeInsert(string $table, array $data): ?int
    {
        try {
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');
            
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $table,
                implode(', ', $columns),
                implode(', ', $placeholders)
            );
            
            $stmt = $this->core->getPDO()->prepare($sql);
            $stmt->execute(array_values($data));
            
            return (int)$this->core->getPDO()->lastInsertId();
        } catch (\PDOException $e) {
            error_log("SafeQuery Insert Error: " . $e->getMessage() . " | Table: " . $table);
            return null;
        }
    }

    /**
     * Actualiza registros en una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param array $data Array asociativo [columna => valor] para actualizar
     * @param string $where Condición WHERE (sin la palabra WHERE)
     * @param array $whereParams Parámetros para el WHERE
     * @return int Número de filas afectadas
     */
    protected function safeUpdate(string $table, array $data, string $where, array $whereParams = []): int
    {
        try {
            $set = implode(', ', array_map(fn($col) => "$col = ?", array_keys($data)));
            $sql = "UPDATE $table SET $set WHERE $where";
            
            $params = array_merge(array_values($data), $whereParams);
            $stmt = $this->core->getPDO()->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log("SafeQuery Update Error: " . $e->getMessage() . " | Table: " . $table);
            return 0;
        }
    }

    /**
     * Elimina registros de una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param string $where Condición WHERE (sin la palabra WHERE)
     * @param array $whereParams Parámetros para el WHERE
     * @return int Número de filas afectadas
     */
    protected function safeDelete(string $table, string $where, array $whereParams = []): int
    {
        try {
            $sql = "DELETE FROM $table WHERE $where";
            $stmt = $this->core->getPDO()->prepare($sql);
            $stmt->execute($whereParams);
            
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log("SafeQuery Delete Error: " . $e->getMessage() . " | Table: " . $table);
            return 0;
        }
    }

    /**
     * Cuenta registros en una tabla
     * 
     * @param string $table Nombre de la tabla
     * @param string $where Condición WHERE opcional (sin la palabra WHERE)
     * @param array $whereParams Parámetros para el WHERE
     * @return int Número de registros
     */
    protected function safeCount(string $table, string $where = '1', array $whereParams = []): int
    {
        $sql = "SELECT COUNT(*) FROM $table WHERE $where";
        return (int)$this->safe($sql, $whereParams);
    }

    /**
     * Verifica si existe al menos un registro
     * 
     * @param string $table Nombre de la tabla
     * @param string $where Condición WHERE (sin la palabra WHERE)
     * @param array $whereParams Parámetros para el WHERE
     * @return bool True si existe al menos un registro
     */
    protected function safeExists(string $table, string $where, array $whereParams = []): bool
    {
        return $this->safeCount($table, $where, $whereParams) > 0;
    }
}

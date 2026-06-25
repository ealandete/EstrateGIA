<?php

class FinancialManager {

    private EstrateGiaCore $core;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }

    public function getPresupuesto(int $planId, ?string $periodo = null): array {
        $sql = 'SELECT f.*, o.objetivo_nombre, e.estrategia_nombre, o.objetivo_perspectiva
                FROM fin_presupuesto f
                LEFT JOIN plan_objetivos o ON f.fin_objetivo_id = o.objetivo_id
                LEFT JOIN plan_estrategias e ON f.fin_estrategia_id = e.estrategia_id
                WHERE f.fin_plan_id = :pid';
        $params = ['pid' => $planId];
        if ($periodo) {
            $sql .= ' AND f.fin_periodo = :per';
            $params['per'] = $periodo;
        }
        $sql .= ' ORDER BY f.fin_periodo DESC, f.fin_presupuestado DESC';
        return $this->core->fetchAll($sql, $params);
    }

    public function getResumen(int $planId): array {
        return $this->core->fetchOne(
            'SELECT COALESCE(SUM(fin_presupuestado),0) as total_presupuestado,
                    COALESCE(SUM(fin_ejecutado),0) as total_ejecutado,
                    COUNT(*) as total_registros,
                    COUNT(DISTINCT fin_periodo) as periodos
             FROM fin_presupuesto WHERE fin_plan_id = :pid',
            ['pid' => $planId]
        ) ?: ['total_presupuestado'=>0,'total_ejecutado'=>0,'total_registros'=>0,'periodos'=>0];
    }

    public function getPresupuestoByPerspectiva(int $planId): array {
        return $this->core->fetchAll(
            'SELECT o.objetivo_perspectiva,
                    COALESCE(SUM(f.fin_presupuestado),0) as presupuestado,
                    COALESCE(SUM(f.fin_ejecutado),0) as ejecutado
             FROM fin_presupuesto f
             LEFT JOIN plan_objetivos o ON f.fin_objetivo_id = o.objetivo_id
             WHERE f.fin_plan_id = :pid
             GROUP BY o.objetivo_perspectiva',
            ['pid' => $planId]
        );
    }

    public function savePresupuesto(array $data): int {
        return $this->core->insert('fin_presupuesto', [
            'fin_plan_id' => $data['plan_id'],
            'fin_objetivo_id' => $data['objetivo_id'] ?? null,
            'fin_estrategia_id' => $data['estrategia_id'] ?? null,
            'fin_periodo' => $data['periodo'] ?? date('Y-m'),
            'fin_presupuestado' => (float)($data['presupuestado'] ?? 0),
            'fin_ejecutado' => (float)($data['ejecutado'] ?? 0),
            'fin_notas' => $data['notas'] ?? null,
        ]);
    }

    public function deletePresupuesto(int $id): bool {
        return $this->core->delete('fin_presupuesto', 'fin_id = :id', ['id' => $id]) > 0;
    }
}

<?php
/**
 * EstrateGIA - DocManager
 * Gestión Documental basada en normas ISO.
 * Soporte sectorial: Salud, Inmobiliario, Logística Farmacéutica.
 * Plantillas, documentos, auditorías, control de versiones.
 */

require_once __DIR__ . '/EstrateGiaCore.php';

class DocManager {

    private $core;

    public function __construct() {
        $this->core = EstrateGiaCore::getInstance();
    }

    // ========================================================================
    // SECTORES
    // ========================================================================

    public function getSectores(bool $activos = true): array {
        $sql = 'SELECT * FROM doc_sectores';
        if ($activos) $sql .= ' WHERE sector_activo = 1';
        $sql .= ' ORDER BY sector_nombre';
        return $this->core->fetchAll($sql);
    }

    public function getSector(int $id): ?array {
        return $this->core->fetchOne('SELECT * FROM doc_sectores WHERE sector_id = :id', ['id' => $id]);
    }

    /**
     * Obtiene información específica del sector para una empresa
     */
    public function getSectorInfo(int $empresaId): ?array {
        $empresa = $this->core->fetchOne(
            'SELECT empresa_sector_id FROM plan_empresas WHERE empresa_id = :id', ['id' => $empresaId]
        );
        if (!$empresa || !$empresa['empresa_sector_id']) return null;

        $sector = $this->getSector($empresa['empresa_sector_id']);
        if (!$sector) return null;

        $info = ['sector' => $sector];

        switch ($sector['sector_nombre']) {
            case 'Salud':
                $info['especifico'] = $this->core->fetchOne(
                    'SELECT * FROM sector_salud WHERE salud_empresa_id = :eid', ['eid' => $empresaId]
                );
                break;
            case 'Inmobiliario':
                $info['especifico'] = $this->core->fetchOne(
                    'SELECT * FROM sector_inmobiliario WHERE inmob_empresa_id = :eid', ['eid' => $empresaId]
                );
                break;
            case 'Logística Farmacéutica':
                $info['especifico'] = $this->core->fetchOne(
                    'SELECT * FROM sector_logistica_farma WHERE logifarma_empresa_id = :eid', ['eid' => $empresaId]
                );
                break;
        }

        return $info;
    }

    // ========================================================================
    // NORMAS ISO
    // ========================================================================

    public function getNormas(?int $sectorId = null, bool $activas = true): array {
        $sql = 'SELECT n.*, s.sector_nombre
                FROM doc_normas_iso n
                LEFT JOIN doc_sectores s ON n.norma_sector_id = s.sector_id';
        $where = [];
        $params = [];

        if ($activas) $where[] = 'n.norma_activo = 1';
        if ($sectorId) { $where[] = '(n.norma_sector_id = :sid OR n.norma_sector_id = (SELECT sector_id FROM doc_sectores WHERE sector_nombre = \'General\'))'; $params['sid'] = $sectorId; }

        if (!empty($where)) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY n.norma_codigo';

        return $this->core->fetchAll($sql, $params);
    }

    public function getNorma(int $id): ?array {
        return $this->core->fetchOne(
            'SELECT n.*, s.sector_nombre FROM doc_normas_iso n
             LEFT JOIN doc_sectores s ON n.norma_sector_id = s.sector_id
             WHERE n.norma_id = :id', ['id' => $id]
        );
    }

    public function getNormaRequisitos(int $normaId): array {
        $norma = $this->getNorma($normaId);
        if ($norma && $norma['norma_requisitos_json']) {
            return json_decode($norma['norma_requisitos_json'], true) ?? [];
        }
        return [];
    }

    // ========================================================================
    // PLANTILLAS DE DOCUMENTOS
    // ========================================================================

    public function getPlantillas(?int $normaId = null, ?int $sectorId = null, ?string $tipoDocumento = null): array {
        $sql = 'SELECT p.*, n.norma_codigo, n.norma_nombre, s.sector_nombre
                FROM doc_plantillas p
                LEFT JOIN doc_normas_iso n ON p.plantilla_norma_id = n.norma_id
                LEFT JOIN doc_sectores s ON p.plantilla_sector_id = s.sector_id
                WHERE p.plantilla_activo = 1';
        $params = [];

        if ($normaId)      { $sql .= ' AND p.plantilla_norma_id = :nid'; $params['nid'] = $normaId; }
        if ($sectorId)     { $sql .= ' AND (p.plantilla_sector_id = :sid OR p.plantilla_sector_id IS NULL)'; $params['sid'] = $sectorId; }
        if ($tipoDocumento){ $sql .= ' AND p.plantilla_tipo_documento = :tipo'; $params['tipo'] = $tipoDocumento; }

        $sql .= ' ORDER BY p.plantilla_tipo_documento, p.plantilla_nombre';
        return $this->core->fetchAll($sql, $params);
    }

    public function getPlantilla(int $id): ?array {
        return $this->core->fetchOne(
            'SELECT p.*, n.norma_codigo, n.norma_nombre FROM doc_plantillas p
             LEFT JOIN doc_normas_iso n ON p.plantilla_norma_id = n.norma_id
             WHERE p.plantilla_id = :id', ['id' => $id]
        );
    }

    public function createPlantilla(array $data): int {
        return $this->core->insert('doc_plantillas', [
            'plantilla_norma_id'        => $data['plantilla_norma_id'] ?? null,
            'plantilla_sector_id'       => $data['plantilla_sector_id'] ?? null,
            'plantilla_tipo_documento'  => $data['plantilla_tipo_documento'],
            'plantilla_nombre'          => $data['plantilla_nombre'],
            'plantilla_descripcion'     => $data['plantilla_descripcion'] ?? null,
            'plantilla_estructura_json' => isset($data['plantilla_estructura_json']) ? json_encode($data['plantilla_estructura_json']) : null,
            'plantilla_contenido_html'  => $data['plantilla_contenido_html'] ?? null
        ]);
    }

    // ========================================================================
    // DOCUMENTOS
    // ========================================================================

    public function createDocumento(array $data): int {
        $required = ['documento_empresa_id', 'documento_titulo', 'documento_tipo'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        $docData = [
            'documento_empresa_id'          => $data['documento_empresa_id'],
            'documento_proceso_id'          => $data['documento_proceso_id'] ?? null,
            'documento_procedimiento_id'    => $data['documento_procedimiento_id'] ?? null,
            'documento_plantilla_id'        => $data['documento_plantilla_id'] ?? null,
            'documento_norma_id'            => $data['documento_norma_id'] ?? null,
            'documento_titulo'              => $data['documento_titulo'],
            'documento_tipo'                => $data['documento_tipo'],
            'documento_version'             => $data['documento_version'] ?? '1.0',
            'documento_estado'              => $data['documento_estado'] ?? 'borrador',
            'documento_codigo'              => $data['documento_codigo'] ?? null,
            'documento_contenido_html'      => $data['documento_contenido_html'] ?? null,
            'documento_archivo_url'         => $data['documento_archivo_url'] ?? null,
            'documento_elaborado_por'       => $data['documento_elaborado_por'] ?? null,
            'documento_revisado_por'        => $data['documento_revisado_por'] ?? null,
            'documento_fecha_vigencia'      => $data['documento_fecha_vigencia'] ?? null,
            'documento_fecha_proxima_revision' => $data['documento_fecha_proxima_revision'] ?? null
        ];

        return $this->core->insert('doc_documentos', $docData);
    }

    public function getDocumentos(?int $empresaId = null, ?int $procesoId = null,
                                   ?string $tipo = null, ?string $estado = null): array {
        $sql = 'SELECT d.*, e.empresa_nombre, p.proceso_nombre, n.norma_codigo,
                       CONCAT(el.usuario_nombre, \' \', el.usuario_apellido) as elaborado_por_nombre,
                       CONCAT(ap.usuario_nombre, \' \', ap.usuario_apellido) as aprobado_por_nombre
                FROM doc_documentos d
                LEFT JOIN plan_empresas e ON d.documento_empresa_id = e.empresa_id
                LEFT JOIN proc_procesos p ON d.documento_proceso_id = p.proceso_id
                LEFT JOIN doc_normas_iso n ON d.documento_norma_id = n.norma_id
                LEFT JOIN sys_usuarios el ON d.documento_elaborado_por = el.usuario_id
                LEFT JOIN sys_usuarios ap ON d.documento_aprobado_por = ap.usuario_id
                WHERE d.documento_activo = 1';
        $params = [];

        if ($empresaId) { $sql .= ' AND d.documento_empresa_id = :eid'; $params['eid'] = $empresaId; }
        if ($procesoId) { $sql .= ' AND d.documento_proceso_id = :pid'; $params['pid'] = $procesoId; }
        if ($tipo)      { $sql .= ' AND d.documento_tipo = :tipo'; $params['tipo'] = $tipo; }
        if ($estado)    { $sql .= ' AND d.documento_estado = :est'; $params['est'] = $estado; }

        $sql .= ' ORDER BY d.documento_tipo, d.documento_titulo';
        return $this->core->fetchAll($sql, $params);
    }

    public function getDocumento(int $id): ?array {
        return $this->core->fetchOne(
            'SELECT d.*, e.empresa_nombre, p.proceso_nombre, n.norma_codigo, n.norma_nombre,
                    pt.plantilla_nombre
             FROM doc_documentos d
             LEFT JOIN plan_empresas e ON d.documento_empresa_id = e.empresa_id
             LEFT JOIN proc_procesos p ON d.documento_proceso_id = p.proceso_id
             LEFT JOIN doc_normas_iso n ON d.documento_norma_id = n.norma_id
             LEFT JOIN doc_plantillas pt ON d.documento_plantilla_id = pt.plantilla_id
             WHERE d.documento_id = :id', ['id' => $id]
        );
    }

    public function updateDocumento(int $id, array $data): bool {
        if (isset($data['documento_control_cambios']) && is_array($data['documento_control_cambios'])) {
            $data['documento_control_cambios'] = json_encode($data['documento_control_cambios']);
        }
        return $this->core->update('doc_documentos', $data, 'documento_id = :id', ['id' => $id]) > 0;
    }

    public function aprobarDocumento(int $id, int $aprobadorId): bool {
        return $this->core->update('doc_documentos', [
            'documento_estado'          => 'aprobado',
            'documento_aprobado_por'    => $aprobadorId,
            'documento_fecha_aprobacion'=> date('Y-m-d')
        ], 'documento_id = :id', ['id' => $id]) > 0;
    }

    public function publicarDocumento(int $id): bool {
        return $this->core->update('doc_documentos', [
            'documento_estado' => 'publicado'
        ], 'documento_id = :id', ['id' => $id]) > 0;
    }

    public function obsoletarDocumento(int $id): bool {
        return $this->core->update('doc_documentos', [
            'documento_estado' => 'obsoleto'
        ], 'documento_id = :id', ['id' => $id]) > 0;
    }

    /**
     * Crea una nueva versión de un documento
     */
    public function crearNuevaVersion(int $documentoId, string $contenido, int $elaboradoPor): int {
        $doc = $this->getDocumento($documentoId);
        if (!$doc) throw new InvalidArgumentException('Documento no encontrado');

        $versionAnterior = $doc['documento_version'];
        $contenidoAnterior = $doc['documento_contenido_html'] ?? '';
        $partes = explode('.', $versionAnterior);
        $nuevaVersion = $partes[0] . '.' . ((int)($partes[1] ?? 0) + 1);

        $controlCambios = json_decode($doc['documento_control_cambios'] ?? '[]', true) ?: [];
        $controlCambios[] = [
            'version' => $versionAnterior,
            'fecha'   => date('Y-m-d'),
            'cambio'  => 'Nueva versión creada: ' . $nuevaVersion,
            'usuario' => $elaboradoPor
        ];

        $this->updateDocumento($documentoId, [
            'documento_version'         => $nuevaVersion,
            'documento_contenido_html'  => $contenido,
            'documento_estado'          => 'borrador',
            'documento_elaborado_por'   => $elaboradoPor,
            'documento_control_cambios' => $controlCambios
        ]);

        $this->core->insert('doc_historial', [
            'historial_documento_id'        => $documentoId,
            'historial_version_anterior'    => $versionAnterior,
            'historial_version_nueva'       => $nuevaVersion,
            'historial_contenido_anterior'  => $contenidoAnterior,
            'historial_contenido_nuevo'     => $contenido,
            'historial_fecha_cambio'        => date('Y-m-d H:i:s'),
            'historial_usuario_id'          => $elaboradoPor,
            'historial_accion'              => 'actualizacion',
            'historial_comentario'          => 'Versión actualizada de ' . $versionAnterior . ' a ' . $nuevaVersion,
        ]);

        return $documentoId;
    }

    public function getHistorialVersiones(int $documentoId): array {
        return $this->core->fetchAll(
            'SELECT h.*, CONCAT(u.usuario_nombre, \' \', u.usuario_apellido) as usuario_nombre
             FROM doc_historial h
             LEFT JOIN sys_usuarios u ON h.historial_usuario_id = u.usuario_id
             WHERE h.historial_documento_id = :did
             ORDER BY h.historial_fecha_cambio DESC',
            ['did' => $documentoId]
        );
    }

    public function getCodificacion(int $empresaId, string $modulo = 'documentos'): ?array {
        return $this->core->fetchOne(
            'SELECT * FROM conf_codificacion WHERE codif_empresa_id = :eid AND codif_modulo = :mod AND codif_activo = 1',
            ['eid' => $empresaId, 'mod' => $modulo]
        );
    }

    public function generarCodigoDocumento(int $empresaId, string $tipo): string {
        $cfg = $this->getCodificacion($empresaId, 'documentos');

        // Si no hay codificación activa, usar configuración de empresa como fallback
        $formatoDefault = $this->core->getEmpresaConfigValue($empresaId, 'empresa_documento_codigo_formato', '{PREFIJO}-{TIPO}-{CONSECUTIVO}');
        $prefijoDefault = $this->core->getEmpresaConfigValue($empresaId, 'empresa_documento_codigo_prefijo', 'DOC');

        if (!$cfg || empty($cfg['codif_formato'])) {
            $prefijo = $cfg['codif_prefijo'] ?? $prefijoDefault;
            $consecutivo = (int)($cfg['codif_consecutivo_actual'] ?? 0) + 1;
            if ($cfg && !empty($cfg['codif_id'])) {
                $this->core->update('conf_codificacion', ['codif_consecutivo_actual' => $consecutivo], 'codif_id = :id', ['id' => $cfg['codif_id']]);
            }
            $codigo = $formatoDefault;
            $codigo = str_replace('{PREFIJO}', $prefijo, $codigo);
            $codigo = str_replace('{TIPO}', strtoupper(substr($tipo, 0, 3)), $codigo);
            $codigo = str_replace('{CONSECUTIVO}', str_pad((string)$consecutivo, 4, '0', STR_PAD_LEFT), $codigo);
            $codigo = str_replace('{SEPARADOR}', '-', $codigo);
            return $codigo;
        }

        $prefijo = $cfg['codif_prefijo'] ?? $prefijoDefault;
        $consecutivo = (int)($cfg['codif_consecutivo_actual'] ?? 0) + 1;
        $this->core->update('conf_codificacion', ['codif_consecutivo_actual' => $consecutivo], 'codif_id = :id', ['id' => $cfg['codif_id']]);

        $codigo = $cfg['codif_formato'];
        $sep = $cfg['codif_separador'] ?? '-';
        $codigo = str_replace('{prefijo}', $prefijo, $codigo);
        $codigo = str_replace('{PREFIJO}', $prefijo, $codigo);
        $codigo = str_replace('{tipo}', strtoupper(substr($tipo, 0, 3)), $codigo);
        $codigo = str_replace('{TIPO}', strtoupper(substr($tipo, 0, 3)), $codigo);
        $codigo = str_replace('{consecutivo}', str_pad((string)$consecutivo, 4, '0', STR_PAD_LEFT), $codigo);
        $codigo = str_replace('{CONSECUTIVO}', str_pad((string)$consecutivo, 4, '0', STR_PAD_LEFT), $codigo);
        $codigo = str_replace('{separador}', $sep, $codigo);
        $codigo = str_replace('{SEPARADOR}', $sep, $codigo);

        return $codigo;
    }

    public function guardarConfiguracionCodificacion(int $empresaId, array $data): bool {
        $existing = $this->core->fetchOne(
            'SELECT codif_id FROM conf_codificacion WHERE codif_empresa_id = :eid AND codif_modulo = :mod',
            ['eid' => $empresaId, 'mod' => $data['codif_modulo'] ?? 'documentos']
        );

        $codifData = [
            'codif_prefijo'            => $data['codif_prefijo'] ?? '',
            'codif_formato'            => $data['codif_formato'] ?? '{prefijo}-{tipo}-{consecutivo}',
            'codif_separador'          => $data['codif_separador'] ?? '-',
            'codif_consecutivo_actual' => (int)($data['codif_consecutivo_actual'] ?? 0),
            'codif_activo'             => 1,
        ];

        if ($existing) {
            return $this->core->update('conf_codificacion', $codifData, 'codif_empresa_id = :eid AND codif_modulo = :mod', [
                'eid' => $empresaId, 'mod' => $data['codif_modulo'] ?? 'documentos'
            ]) > 0;
        }

        $codifData['codif_empresa_id'] = $empresaId;
        $codifData['codif_modulo'] = $data['codif_modulo'] ?? 'documentos';
        return $this->core->insert('conf_codificacion', $codifData) > 0;
    }

    // ========================================================================
    // AUDITORÍAS
    // ========================================================================

    public function createAuditoria(array $data): int {
        $required = ['auditoria_empresa_id', 'auditoria_tipo', 'auditoria_fecha_inicio'];
        $errors = $this->core->validateRequired($data, $required);
        if (!empty($errors)) throw new InvalidArgumentException(json_encode($errors));

        return $this->core->insert('doc_auditorias', [
            'auditoria_empresa_id'  => $data['auditoria_empresa_id'],
            'auditoria_norma_id'    => $data['auditoria_norma_id'] ?? null,
            'auditoria_tipo'        => $data['auditoria_tipo'],
            'auditoria_fecha_inicio'=> $data['auditoria_fecha_inicio'],
            'auditoria_fecha_fin'   => $data['auditoria_fecha_fin'] ?? null,
            'auditoria_alcance'     => $data['auditoria_alcance'] ?? null,
            'auditoria_equipo_ids'  => isset($data['auditoria_equipo_ids']) ? json_encode($data['auditoria_equipo_ids']) : null
        ]);
    }

    public function getAuditorias(int $empresaId, ?string $estado = null): array {
        $sql = 'SELECT a.*, n.norma_codigo, n.norma_nombre
                FROM doc_auditorias a
                LEFT JOIN doc_normas_iso n ON a.auditoria_norma_id = n.norma_id
                WHERE a.auditoria_empresa_id = :eid';
        $params = ['eid' => $empresaId];
        if ($estado) { $sql .= ' AND a.auditoria_estado = :est'; $params['est'] = $estado; }
        $sql .= ' ORDER BY a.auditoria_fecha_inicio DESC';
        return $this->core->fetchAll($sql, $params);
    }

    public function registrarHallazgo(int $auditoriaId, array $hallazgo): bool {
        $auditoria = $this->core->fetchOne(
            'SELECT auditoria_hallazgos_json FROM doc_auditorias WHERE auditoria_id = :id', ['id' => $auditoriaId]
        );
        $hallazgos = json_decode($auditoria['auditoria_hallazgos_json'] ?? '[]', true) ?: [];
        $hallazgo['fecha'] = date('Y-m-d');
        $hallazgo['id'] = count($hallazgos) + 1;
        $hallazgos[] = $hallazgo;

        return $this->core->update('doc_auditorias', [
            'auditoria_hallazgos_json' => json_encode($hallazgos)
        ], 'auditoria_id = :id', ['id' => $auditoriaId]) > 0;
    }

    // ========================================================================
    // DASHBOARD DOCUMENTAL
    // ========================================================================

    /**
     * Resumen del estado documental de la empresa
     */
    public function getDashboardDocumental(int $empresaId): array {
        return [
            'total_documentos' => $this->core->fetchColumn(
                'SELECT COUNT(*) FROM doc_documentos WHERE documento_empresa_id = :eid AND documento_activo = 1',
                ['eid' => $empresaId]
            ),
            'por_estado' => $this->core->fetchAll(
                'SELECT documento_estado, COUNT(*) as total
                 FROM doc_documentos WHERE documento_empresa_id = :eid AND documento_activo = 1
                 GROUP BY documento_estado',
                ['eid' => $empresaId]
            ),
            'por_tipo' => $this->core->fetchAll(
                'SELECT documento_tipo, COUNT(*) as total
                 FROM doc_documentos WHERE documento_empresa_id = :eid AND documento_activo = 1
                 GROUP BY documento_tipo',
                ['eid' => $empresaId]
            ),
            'documentos_por_revisar' => $this->core->fetchAll(
                'SELECT documento_titulo, documento_fecha_proxima_revision
                 FROM doc_documentos WHERE documento_empresa_id = :eid
                 AND documento_activo = 1 AND documento_fecha_proxima_revision <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                 ORDER BY documento_fecha_proxima_revision ASC',
                ['eid' => $empresaId]
            ),
            'normas_aplicables' => $this->core->fetchAll(
                'SELECT DISTINCT n.norma_codigo, n.norma_nombre, COUNT(d.documento_id) as total_documentos
                 FROM doc_normas_iso n
                 LEFT JOIN doc_documentos d ON n.norma_id = d.documento_norma_id AND d.documento_empresa_id = :eid
                 WHERE n.norma_sector_id IN (SELECT empresa_sector_id FROM plan_empresas WHERE empresa_id = :eid2)
                    OR n.norma_sector_id = (SELECT sector_id FROM doc_sectores WHERE sector_nombre = \'General\')
                 GROUP BY n.norma_id',
                ['eid' => $empresaId, 'eid2' => $empresaId]
            ),
            'ultima_auditoria' => $this->core->fetchOne(
                'SELECT * FROM doc_auditorias WHERE auditoria_empresa_id = :eid
                 ORDER BY auditoria_fecha_inicio DESC LIMIT 1',
                ['eid' => $empresaId]
            )
        ];
    }

    // ========================================================================
    // SECTOR ESPECÍFICO: SALUD
    // ========================================================================

    public function saveSectorSalud(int $empresaId, array $data): int {
        $existing = $this->core->fetchOne(
            'SELECT salud_id FROM sector_salud WHERE salud_empresa_id = :eid', ['eid' => $empresaId]
        );

        $saludData = [
            'salud_nivel_atencion'      => $data['salud_nivel_atencion'] ?? 'primario',
            'salud_habilitacion_no'     => $data['salud_habilitacion_no'] ?? null,
            'salud_tipo_institucion'    => $data['salud_tipo_institucion'] ?? 'ips',
            'salud_camas'               => $data['salud_camas'] ?? 0,
            'salud_servicios_habilitados' => isset($data['salud_servicios_habilitados']) ? json_encode($data['salud_servicios_habilitados']) : null,
            'salud_normas_aplicables'   => isset($data['salud_normas_aplicables']) ? json_encode($data['salud_normas_aplicables']) : null
        ];

        if ($existing) {
            $this->core->update('sector_salud', $saludData, 'salud_empresa_id = :eid', ['eid' => $empresaId]);
            return $existing['salud_id'];
        } else {
            $saludData['salud_empresa_id'] = $empresaId;
            return $this->core->insert('sector_salud', $saludData);
        }
    }

    public function getSectorSalud(int $empresaId): ?array {
        return $this->core->fetchOne(
            'SELECT * FROM sector_salud WHERE salud_empresa_id = :eid', ['eid' => $empresaId]
        );
    }

    // ========================================================================
    // SECTOR ESPECÍFICO: INMOBILIARIO
    // ========================================================================

    public function saveSectorInmobiliario(int $empresaId, array $data): bool {
        $existing = $this->core->fetchOne(
            'SELECT inmob_empresa_id FROM sector_inmobiliario WHERE inmob_empresa_id = :eid', ['eid' => $empresaId]
        );

        $inmobData = [
            'inmob_tipo_operacion'      => $data['inmob_tipo_operacion'] ?? 'mixto',
            'inmob_numero_propiedades'  => $data['inmob_numero_propiedades'] ?? 0,
            'inmob_numero_proyectos'    => $data['inmob_numero_proyectos'] ?? 0,
            'inmob_zonas_operacion'     => isset($data['inmob_zonas_operacion']) ? json_encode($data['inmob_zonas_operacion']) : null,
            'inmob_camara_comercio'     => $data['inmob_camara_comercio'] ?? null,
            'inmob_lonja_afiliacion'    => $data['inmob_lonja_afiliacion'] ?? null,
            'inmob_normas_aplicables'   => isset($data['inmob_normas_aplicables']) ? json_encode($data['inmob_normas_aplicables']) : null
        ];

        if ($existing) {
            return $this->core->update('sector_inmobiliario', $inmobData,
                'inmob_empresa_id = :eid', ['eid' => $empresaId]) > 0;
        } else {
            $inmobData['inmob_empresa_id'] = $empresaId;
            return $this->core->insert('sector_inmobiliario', $inmobData) > 0;
        }
    }

    public function getSectorInmobiliario(int $empresaId): ?array {
        return $this->core->fetchOne(
            'SELECT * FROM sector_inmobiliario WHERE inmob_empresa_id = :eid', ['eid' => $empresaId]
        );
    }

    // ========================================================================
    // SECTOR ESPECÍFICO: LOGÍSTICA FARMACÉUTICA
    // ========================================================================

    public function saveSectorLogisticaFarma(int $empresaId, array $data): bool {
        $existing = $this->core->fetchOne(
            'SELECT logifarma_empresa_id FROM sector_logistica_farma WHERE logifarma_empresa_id = :eid', ['eid' => $empresaId]
        );

        $logiData = [
            'logifarma_tipo_operacion'      => $data['logifarma_tipo_operacion'] ?? 'integral',
            'logifarma_certificacion_bpa'   => $data['logifarma_certificacion_bpa'] ?? null,
            'logifarma_certificacion_bpt'   => $data['logifarma_certificacion_bpt'] ?? null,
            'logifarma_areas_almacenamiento'=> isset($data['logifarma_areas_almacenamiento']) ? json_encode($data['logifarma_areas_almacenamiento']) : null,
            'logifarma_capacidad_m3'        => $data['logifarma_capacidad_m3'] ?? null,
            'logifarma_flota_vehiculos'     => $data['logifarma_flota_vehiculos'] ?? 0,
            'logifarma_vehiculos_refrigerados' => $data['logifarma_vehiculos_refrigerados'] ?? 0,
            'logifarma_monitoreo_temperatura'  => $data['logifarma_monitoreo_temperatura'] ?? 0,
            'logifarma_invima_registro'     => $data['logifarma_invima_registro'] ?? null,
            'logifarma_normas_aplicables'   => isset($data['logifarma_normas_aplicables']) ? json_encode($data['logifarma_normas_aplicables']) : null
        ];

        if ($existing) {
            return $this->core->update('sector_logistica_farma', $logiData,
                'logifarma_empresa_id = :eid', ['eid' => $empresaId]) > 0;
        } else {
            $logiData['logifarma_empresa_id'] = $empresaId;
            return $this->core->insert('sector_logistica_farma', $logiData) > 0;
        }
    }

    public function getSectorLogisticaFarma(int $empresaId): ?array {
        return $this->core->fetchOne(
            'SELECT * FROM sector_logistica_farma WHERE logifarma_empresa_id = :eid', ['eid' => $empresaId]
        );
    }
}

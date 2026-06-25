<?php
declare(strict_types=1);
require_once BASE_PATH . '/lib/SafeQuery.php';

class DocsController {
    use \SafeQuery;
    private $core;

    public function __construct() { $this->core = EstrateGiaCore::getInstance(); }

    public function index(): void {
        $docs = [
            'manuales' => [
                'titulo' => 'Manuales de Usuario',
                'icon' => 'book',
                'color' => '#1a73e8',
                'documentos' => [
                    [
                        'nombre' => 'Manual de Usuario v2.1',
                        'descripcion' => 'Guía completa organizada por perfil: Director, Gerente, Coordinador, Analista, Colaborador, Auditor, Administrador',
                        'pdf' => '/descargar-doc/manual_usuario/Manual_Usuario_EstrateGIA_v2.1.pdf',
                        'html' => '/docs/html/manual_usuario.html',
                        'paginas' => 45,
                        'tamano' => '2.3 MB'
                    ],
                    [
                        'nombre' => 'Manual de Administrador v2.1',
                        'descripcion' => 'Gestión de usuarios, roles, permisos, configuración del sistema y soporte técnico',
                        'pdf' => '/descargar-doc/manual_usuario/admin/Manual_Admin_EstrateGIA_v2.1.pdf',
                        'html' => '/docs/html/manual_admin.html',
                        'paginas' => 28,
                        'tamano' => '1.5 MB'
                    ],
                    [
                        'nombre' => 'Manual de Operador v2.1',
                        'descripcion' => 'Operaciones diarias, registro de mediciones, gestión de indicadores y reportes',
                        'pdf' => '/descargar-doc/manual_usuario/operador/Manual_Operador_EstrateGIA_v2.1.pdf',
                        'html' => '/docs/html/manual_operador.html',
                        'paginas' => 32,
                        'tamano' => '1.8 MB'
                    ],
                ]
            ],
            'tecnico' => [
                'titulo' => 'Documentación Técnica',
                'icon' => 'code',
                'color' => '#28a745',
                'documentos' => [
                    [
                        'nombre' => 'Manual de Programador v3.0',
                        'descripcion' => 'Arquitectura del sistema, estándares de código, API REST, integración con IA, despliegue',
                        'pdf' => '/descargar-doc/03_Manual_Programador/Manual_Programador_v3.0.pdf',
                        'html' => '/docs/html/manual_programador.html',
                        'paginas' => 68,
                        'tamano' => '3.2 MB'
                    ],
                    [
                        'nombre' => 'Manual de Base de Datos v3.0',
                        'descripcion' => 'Modelo ER, 94 tablas, 124 foreign keys, índices, triggers, procedimientos almacenados',
                        'pdf' => '/descargar-doc/04_Manual_BD/Manual_BD_v3.0.pdf',
                        'html' => '/docs/html/manual_bd.html',
                        'paginas' => 52,
                        'tamano' => '2.8 MB'
                    ],
                    [
                        'nombre' => 'Casos de Uso v3.0',
                        'descripcion' => '25 casos de uso con diagramas UML, flujos de trabajo, actores y escenarios',
                        'pdf' => '/descargar-doc/05_Casos_Uso/Casos_Uso_v3.0.pdf',
                        'html' => '/docs/html/casos_uso.html',
                        'paginas' => 41,
                        'tamano' => '2.1 MB'
                    ],
                ]
            ],
            'metodologia' => [
                'titulo' => 'Metodología y Políticas',
                'icon' => 'shield-halved',
                'color' => '#6f42c1',
                'documentos' => [
                    [
                        'nombre' => 'Políticas de Programación v3.0',
                        'descripcion' => 'Estándares de desarrollo, convenciones de código, seguridad, testing, despliegue',
                        'pdf' => '/descargar-doc/01_Politicas_Programacion/Politicas_Programacion_v3.0.pdf',
                        'html' => '/docs/html/politicas.html',
                        'paginas' => 38,
                        'tamano' => '1.9 MB'
                    ],
                    [
                        'nombre' => 'Metodología Unificada EstrateGIA',
                        'descripcion' => 'SafeQuery, PHVA, checklist de 13 pasos, testing E2E, reglas de oro',
                        'pdf' => '/descargar-doc/99_METODOLOGIA_ESTRATEGIA.pdf',
                        'html' => '/docs/html/metodologia.html',
                        'paginas' => 24,
                        'tamano' => '1.2 MB'
                    ],
                ]
            ],
            'auditoria' => [
                'titulo' => 'Auditoría y Calidad',
                'icon' => 'clipboard-check',
                'color' => '#dc3545',
                'documentos' => [
                    [
                        'nombre' => 'Auditoría Integral v3.0',
                        'descripcion' => 'Informe completo de auditoría: 37 checks, seguridad, rendimiento, funcionalidad',
                        'pdf' => '/descargar-doc/07_Auditorias/Auditoria_Integral_v3.0.pdf',
                        'html' => '/docs/html/auditoria.html',
                        'paginas' => 35,
                        'tamano' => '1.7 MB'
                    ],
                    [
                        'nombre' => 'Pendientes de Desarrollo v3.0',
                        'descripcion' => 'Roadmap, tareas pendientes, mejoras planificadas, cronograma',
                        'pdf' => '/descargar-doc/06_Pendientes_Desarrollo/Pendientes_Desarrollo_v3.0.pdf',
                        'html' => '/docs/html/pendientes.html',
                        'paginas' => 18,
                        'tamano' => '0.9 MB'
                    ],
                ]
            ],
        ];

        $pageTitle = 'Documentación — EstrateGIA v2.1';
        ob_start();
        require BASE_PATH . '/templates/docs/index.php';
        $content = ob_get_clean();
        require BASE_PATH . '/templates/layout.php';
    }
}

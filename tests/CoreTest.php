<?php
/**
 * EstrateGIA - Tests Automatizados (PHPUnit)
 * Ejecutar: vendor/bin/phpunit tests/
 */

use PHPUnit\Framework\TestCase;

class EstrateGiaCoreTest extends TestCase {
    private $core;

    protected function setUp(): void {
        $this->core = EstrateGiaCore::getInstance();
    }

    public function testSingletonInstance(): void {
        $instance1 = EstrateGiaCore::getInstance();
        $instance2 = EstrateGiaCore::getInstance();
        $this->assertSame($instance1, $instance2, 'Debe retornar la misma instancia');
    }

    public function testConfigValues(): void {
        $config = $this->core->getConfig();
        $this->assertArrayHasKey('db_host', $config);
        $this->assertArrayHasKey('db_name', $config);
        $this->assertEquals('estrategia_v1', $config['db_name']);
        $this->assertEquals('America/Bogota', $config['timezone']);
    }

    public function testJWTGeneration(): void {
        $user = ['usuario_id' => 1, 'usuario_nombre' => 'Test', 'usuario_apellido' => 'User', 'usuario_email' => 'test@test.com', 'usuario_rol_id' => 1];
        
        $ref = new ReflectionMethod($this->core, 'generateJWT');
        $ref->setAccessible(true);
        $token = $ref->invoke($this->core, $user);
        
        $this->assertIsString($token);
        $this->assertGreaterThan(10, strlen($token));
        
        $parts = explode('.', $token);
        $this->assertCount(3, $parts, 'JWT debe tener 3 partes');
    }

    public function testJWTValidation(): void {
        $user = ['usuario_id' => 1, 'usuario_nombre' => 'Test', 'usuario_email' => 'test@test.com', 'usuario_rol_id' => 1];
        $ref = new ReflectionMethod($this->core, 'generateJWT');
        $ref->setAccessible(true);
        $token = $ref->invoke($this->core, $user);
        
        $payload = $this->core->validateJWT($token);
        $this->assertIsArray($payload);
        $this->assertEquals(1, $payload['sub']);
    }

    public function testInvalidJWT(): void {
        $this->assertNull($this->core->validateJWT('invalid.token.here'));
        $this->assertNull($this->core->validateJWT(''));
    }

    public function testSanitizeInput(): void {
        $this->assertEquals('test', $this->core->sanitizeInput('test'));
        $this->assertEquals('&lt;script&gt;', $this->core->sanitizeInput('<script>'));
        $this->assertEquals(['clean', 'also_clean'], $this->core->sanitizeInput(['clean', 'also_clean']));
    }

    public function testValidateRequired(): void {
        $data = ['name' => 'Test', 'email' => ''];
        $errors = $this->core->validateRequired($data, ['name', 'email', 'phone']);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('phone', $errors);
        $this->assertArrayNotHasKey('name', $errors);
    }

    public function testEncryptDecrypt(): void {
        $original = 'secret_data_123';
        $encrypted = $this->core->encryptData($original);
        $this->assertNotEquals($original, $encrypted);
        $this->assertEquals($original, $this->core->decryptData($encrypted));
    }

    public function testPaginationCalculation(): void {
        $result = $this->core->paginate('SELECT * FROM plan_empresas', [], 1, 3);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('page', $result['pagination']);
        $this->assertArrayHasKey('total', $result['pagination']);
    }
}

class PlanManagerTest extends TestCase {
    private $pm;

    protected function setUp(): void {
        $this->pm = new PlanManager();
    }

    public function testGetEmpresas(): void {
        $empresas = $this->pm->getEmpresas();
        $this->assertIsArray($empresas);
        if (!empty($empresas)) {
            $this->assertArrayHasKey('empresa_id', $empresas[0]);
            $this->assertArrayHasKey('empresa_nombre', $empresas[0]);
        }
    }

    public function testGetMetodologias(): void {
        $metodologias = $this->pm->getMetodologias();
        $this->assertIsArray($metodologias);
        $this->assertGreaterThanOrEqual(5, count($metodologias));
        foreach ($metodologias as $m) {
            $this->assertArrayHasKey('metodologia_nombre', $m);
        }
    }

    public function testGetPlanes(): void {
        $planes = $this->pm->getPlanes();
        $this->assertIsArray($planes);
        if (!empty($planes)) {
            $this->assertArrayHasKey('plan_nombre', $planes[0]);
            $this->assertArrayHasKey('metodologia_nombre', $planes[0]);
        }
    }

    public function testGetFases(): void {
        $fases = $this->pm->getFases(2);
        $this->assertIsArray($fases);
        $this->assertCount(5, $fases);
        $this->assertEquals('Análisis del Entorno', $fases[0]['fase_nombre']);
    }

    public function testGetPlanTree(): void {
        $arbol = $this->pm->getPlanTree(2);
        $this->assertIsArray($arbol);
        $this->assertCount(5, $arbol);
        foreach ($arbol as $fase) {
            $this->assertArrayHasKey('objetivos', $fase);
        }
    }
}

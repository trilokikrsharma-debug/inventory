<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/DemoLoginService.php';

if (!class_exists('DemoLoginFakeStatement')) {
    class DemoLoginFakeStatement {
        private $value;
        public function __construct($value) { $this->value = $value; }
        public function fetch() { return $this->value; }
        public function fetchAll() { return $this->value; }
        public function fetchColumn() { return $this->value; }
    }
}

if (!class_exists('DemoLoginFakeDb')) {
    class DemoLoginFakeDb {
        public array $queue = [];
        public array $lastInsertIds = [];
        public array $queries = [];
        public function query($sql, $params = []) {
            $this->queries[] = ['sql' => $sql, 'params' => $params];
            $value = array_shift($this->queue);
            if ($value instanceof Throwable) {
                throw $value;
            }
            return new DemoLoginFakeStatement($value);
        }
        public function lastInsertId() {
            return array_shift($this->lastInsertIds) ?? 0;
        }
    }
}

class DemoLoginServiceTest extends BaseTestCase {
    public function testResolveDemoSessionReturnsExistingUserWithoutSensitiveFields(): void {
        $db = new DemoLoginFakeDb();
        $db->queue = [
            ['id' => 9, 'name' => 'Demo Co'],
            ['id' => 41, 'company_id' => 9, 'username' => 'demo', 'password' => 'secret', 'twofa_secret' => 'x', 'is_super_admin' => 1],
        ];
        $service = new DemoLoginService($db);

        $result = $service->resolveDemoSession();

        $this->assertSame(9, $result['company']['id']);
        $this->assertSame(41, $result['user']['id']);
        $this->assertArrayNotHasKey('password', $result['user']);
        $this->assertFalse($result['user']['is_super_admin']);
    }

    public function testResolveDemoSessionCreatesUserWhenMissing(): void {
        $db = new DemoLoginFakeDb();
        $db->queue = [
            ['id' => 9, 'name' => 'Demo Co'],
            null,
            ['id' => 7],
            0,
            null,
            ['id' => 88, 'company_id' => 9, 'username' => 'demo', 'password' => 'secret'],
        ];
        $db->lastInsertIds = [88];
        $service = new DemoLoginService($db);

        $result = $service->resolveDemoSession();

        $this->assertSame(88, $result['user']['id']);
        $this->assertSame('demo', $result['user']['username']);
    }

    public function testResolveDemoSessionRejectsWhenNoDemoCompanyExists(): void {
        $db = new DemoLoginFakeDb();
        $db->queue = [null];
        $service = new DemoLoginService($db);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Demo mode is not available at the moment.');

        $service->resolveDemoSession();
    }
}

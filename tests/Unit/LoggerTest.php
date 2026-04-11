<?php
/**
 * Unit Tests - Logger request correlation
 */

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Logger.php';

class LoggerTest extends BaseTestCase {
    protected function tearDown(): void {
        $requestIdProperty = new ReflectionProperty(Logger::class, 'requestId');
        $requestIdProperty->setAccessible(true);
        $requestIdProperty->setValue(null, null);
        parent::tearDown();
    }

    public function testGetRequestIdUsesDefinedRequestIdConstant(): void {
        if (!defined('REQUEST_ID')) {
            define('REQUEST_ID', 'req-fixed-test-id');
        }

        $requestIdProperty = new ReflectionProperty(Logger::class, 'requestId');
        $requestIdProperty->setAccessible(true);
        $requestIdProperty->setValue(null, null);

        $this->assertSame('req-fixed-test-id', Logger::getRequestId());
    }

    public function testSetRequestIdOverridesLazyResolution(): void {
        Logger::setRequestId('req-manual-override');
        $this->assertSame('req-manual-override', Logger::getRequestId());
    }
}

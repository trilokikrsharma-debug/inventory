<?php
/**
 * Unit Tests - Private upload storage helper behavior
 */

require_once __DIR__ . '/../BaseTestCase.php';

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', '/tmp/tsalegacy-test-uploads');
}
if (!defined('LEGACY_UPLOAD_PATH')) {
    define('LEGACY_UPLOAD_PATH', '/tmp/tsalegacy-test-legacy-uploads');
}
if (!defined('ALLOWED_IMAGE_TYPES')) {
    define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
}

require_once dirname(__DIR__, 2) . '/core/Helper.php';

class UploadStorageHelperTest extends BaseTestCase {
    private string $privateRoot;
    private string $legacyRoot;

    protected function setUp(): void {
        parent::setUp();

        $this->privateRoot = UPLOAD_PATH;
        $this->legacyRoot = LEGACY_UPLOAD_PATH;
        @mkdir($this->privateRoot . '/tenant_44/logo', 0775, true);
        @mkdir($this->legacyRoot . '/tenant_44/logo', 0775, true);
    }

    protected function tearDown(): void {
        $this->removeTree($this->privateRoot);
        $this->removeTree($this->legacyRoot);
        parent::tearDown();
    }

    public function testResolveUploadedPathPrefersPrivateRoot(): void {
        $stored = 'uploads/tenant_44/logo/logo.png';
        $privateFile = $this->privateRoot . '/tenant_44/logo/logo.png';
        $legacyFile = $this->legacyRoot . '/tenant_44/logo/logo.png';

        file_put_contents($privateFile, 'private');
        file_put_contents($legacyFile, 'legacy');

        $resolved = Helper::resolveUploadedPath($stored);

        $this->assertSame($privateFile, $resolved);
    }

    public function testResolveUploadedPathFallsBackToLegacyRoot(): void {
        $stored = 'uploads/tenant_44/logo/fallback.png';
        $legacyFile = $this->legacyRoot . '/tenant_44/logo/fallback.png';
        file_put_contents($legacyFile, 'legacy');

        $resolved = Helper::resolveUploadedPath($stored);

        $this->assertSame($legacyFile, $resolved);
    }

    public function testUploadedImageSrcReturnsDataUriForStoredPng(): void {
        $stored = 'uploads/tenant_44/logo/sample.png';
        $file = $this->privateRoot . '/tenant_44/logo/sample.png';
        file_put_contents($file, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9p8N7VUAAAAASUVORK5CYII='));

        $src = Helper::uploadedImageSrc($stored);

        $this->assertStringStartsWith('data:image/png;base64,', $src);
        $this->assertNotFalse(base64_decode(substr($src, strlen('data:image/png;base64,')), true));
    }

    public function testUploadedImageSrcRejectsUnknownPaths(): void {
        $this->assertSame('', Helper::uploadedImageSrc('../etc/passwd'));
        $this->assertSame('', Helper::uploadedImageSrc('uploads/tenant_44/logo/missing.png'));
    }

    private function removeTree(string $path): void {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . '/' . $item;
            if (is_dir($fullPath)) {
                $this->removeTree($fullPath);
            } else {
                @unlink($fullPath);
            }
        }

        @rmdir($path);
    }
}

<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/TwoFactorService.php';

class TwoFactorServiceTest extends BaseTestCase {
    public function testGetOtpAuthUrlBuildsExpectedProvisioningUri(): void {
        $uri = TwoFactorService::getOtpAuthUrl('JBSWY3DPEHPK3PXP', 'owner@example.com');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        $this->assertStringContainsString('issuer=InvenBill%20Pro', $uri);
        $this->assertStringContainsString('owner%40example.com', $uri);
    }

    public function testOtpProvisioningUriStaysLocalToAuthenticatorFlow(): void {
        $uri = TwoFactorService::getOtpAuthUrl('JBSWY3DPEHPK3PXP', 'owner@example.com');

        $this->assertStringNotContainsString('chart.googleapis.com', $uri);
        $this->assertStringNotContainsString('https://', $uri);
        $this->assertStringNotContainsString('http://', $uri);
    }
}

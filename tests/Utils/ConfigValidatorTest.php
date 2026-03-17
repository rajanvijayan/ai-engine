<?php

declare(strict_types=1);

namespace AIEngine\Tests\Utils;

use PHPUnit\Framework\TestCase;
use AIEngine\Utils\ConfigValidator;

class ConfigValidatorTest extends TestCase
{
    public function testIsValidApiKeyWithValidKey(): void
    {
        $this->assertTrue(ConfigValidator::isValidApiKey('test-api-key-1234567890'));
        $this->assertTrue(ConfigValidator::isValidApiKey('gsk_KiNxOVHqAdWA287U'));
    }

    public function testIsValidApiKeyWithInvalidKey(): void
    {
        $this->assertFalse(ConfigValidator::isValidApiKey(''));
        $this->assertFalse(ConfigValidator::isValidApiKey('short'));
        $this->assertFalse(ConfigValidator::isValidApiKey('   '));
    }

    public function testIsValidTimeoutWithValidValues(): void
    {
        $this->assertTrue(ConfigValidator::isValidTimeout(1));
        $this->assertTrue(ConfigValidator::isValidTimeout(60));
        $this->assertTrue(ConfigValidator::isValidTimeout(300));
    }

    public function testIsValidTimeoutWithInvalidValues(): void
    {
        $this->assertFalse(ConfigValidator::isValidTimeout(0));
        $this->assertFalse(ConfigValidator::isValidTimeout(-1));
        $this->assertFalse(ConfigValidator::isValidTimeout(301));
    }

    public function testIsValidModelWithValidNames(): void
    {
        $this->assertTrue(ConfigValidator::isValidModel('gemini-2.0-flash'));
        $this->assertTrue(ConfigValidator::isValidModel('llama-3.3-70b-versatile'));
        $this->assertTrue(ConfigValidator::isValidModel('Llama-4-Maverick-17B-128E-Instruct-FP8'));
    }

    public function testIsValidModelWithInvalidNames(): void
    {
        $this->assertFalse(ConfigValidator::isValidModel(''));
        $this->assertFalse(ConfigValidator::isValidModel('model with spaces'));
        $this->assertFalse(ConfigValidator::isValidModel('model@special'));
    }

    public function testSanitizeConfig(): void
    {
        $config = [
            'api_key' => '  test-key  ',
            'timeout' => 60,
            'enable_logging' => true,
            'nested' => [
                'value' => '  nested  '
            ]
        ];

        $sanitized = ConfigValidator::sanitizeConfig($config);

        $this->assertEquals('test-key', $sanitized['api_key']);
        $this->assertEquals(60, $sanitized['timeout']);
        $this->assertTrue($sanitized['enable_logging']);
        $this->assertEquals('nested', $sanitized['nested']['value']);
    }

    public function testValidateProviderConfigWithValidConfig(): void
    {
        $config = [
            'api_key' => 'valid-api-key-1234567890',
            'timeout' => 60,
            'model' => 'gemini-2.0-flash'
        ];

        $errors = ConfigValidator::validateProviderConfig($config);

        $this->assertEmpty($errors);
    }

    public function testValidateProviderConfigWithMissingApiKey(): void
    {
        $errors = ConfigValidator::validateProviderConfig([]);

        $this->assertNotEmpty($errors);
        $this->assertContains('Invalid or missing API key', $errors);
    }

    public function testValidateProviderConfigWithInvalidTimeout(): void
    {
        $config = [
            'api_key' => 'valid-api-key-1234567890',
            'timeout' => 999
        ];

        $errors = ConfigValidator::validateProviderConfig($config);

        $this->assertContains('Invalid timeout value (must be between 1 and 300 seconds)', $errors);
    }
}

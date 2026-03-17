<?php

declare(strict_types=1);

namespace AIEngine\Tests;

use PHPUnit\Framework\TestCase;
use AIEngine\AIEngine;
use AIEngine\Response;
use AIEngine\Providers\Gemini;
use AIEngine\Providers\MetaLlama;
use AIEngine\Providers\Groq;
use AIEngine\Exceptions\ConfigurationException;
use AIEngine\Exceptions\ApiException;
use AIEngine\Exceptions\AIEngineException;

class AIEngineTest extends TestCase
{
    private string $dummyApiKey = 'test-api-key-12345678901234567890';

    public function testDefaultProviderIsGemini(): void
    {
        $engine = new AIEngine($this->dummyApiKey);

        $this->assertInstanceOf(AIEngine::class, $engine);
        $this->assertEquals('Gemini', $engine->getProviderName());
    }

    public function testCreateWithGroqProvider(): void
    {
        $engine = new AIEngine($this->dummyApiKey, ['provider' => 'groq']);

        $this->assertEquals('Groq', $engine->getProviderName());
    }

    public function testCreateWithMetaProvider(): void
    {
        $engine = new AIEngine($this->dummyApiKey, ['provider' => 'meta']);

        $this->assertEquals('MetaLlama', $engine->getProviderName());
    }

    public function testStaticFactoryMethod(): void
    {
        $engine = AIEngine::create('groq', $this->dummyApiKey);

        $this->assertInstanceOf(AIEngine::class, $engine);
        $this->assertEquals('Groq', $engine->getProviderName());
    }

    public function testSwitchProvider(): void
    {
        $engine = new AIEngine($this->dummyApiKey, ['provider' => 'gemini']);
        $this->assertEquals('Gemini', $engine->getProviderName());

        $engine->switchProvider('groq', $this->dummyApiKey);
        $this->assertEquals('Groq', $engine->getProviderName());
    }

    public function testIsConfiguredWithValidKey(): void
    {
        $engine = new AIEngine($this->dummyApiKey);

        $this->assertTrue($engine->isConfigured());
    }

    public function testIsConfiguredWithEmptyKey(): void
    {
        $engine = new AIEngine('');

        $this->assertFalse($engine->isConfigured());
    }

    public function testGetAvailableProviders(): void
    {
        $engine = new AIEngine($this->dummyApiKey);
        $providers = $engine->getAvailableProviders();

        $this->assertContains('gemini', $providers);
        $this->assertContains('meta', $providers);
        $this->assertContains('groq', $providers);
    }

    public function testGetDefaultModels(): void
    {
        $models = AIEngine::getDefaultModels();

        $this->assertArrayHasKey('gemini', $models);
        $this->assertArrayHasKey('meta', $models);
        $this->assertArrayHasKey('groq', $models);
    }

    public function testGetModelsForProvider(): void
    {
        $geminiModels = AIEngine::getModelsForProvider('gemini');
        $groqModels = AIEngine::getModelsForProvider('groq');
        $metaModels = AIEngine::getModelsForProvider('meta');

        $this->assertIsArray($geminiModels);
        $this->assertIsArray($groqModels);
        $this->assertIsArray($metaModels);
        $this->assertNotEmpty($geminiModels);
        $this->assertNotEmpty($groqModels);
        $this->assertNotEmpty($metaModels);
    }

    public function testConfigurationOptions(): void
    {
        $config = [
            'provider' => 'gemini',
            'model' => 'gemini-2.5-pro',
            'timeout' => 120,
            'enable_logging' => true
        ];

        $engine = new AIEngine($this->dummyApiKey, $config);

        $this->assertEquals(120, $engine->getConfig('timeout'));
        $this->assertTrue($engine->getConfig('enable_logging'));
    }

    public function testSetAndGetConfig(): void
    {
        $engine = new AIEngine($this->dummyApiKey);

        $engine->setConfig('custom_option', 'custom_value');

        $this->assertEquals('custom_value', $engine->getConfig('custom_option'));
        $this->assertNull($engine->getConfig('nonexistent'));
        $this->assertEquals('default', $engine->getConfig('nonexistent', 'default'));
    }

    public function testValidatePromptWithValidInput(): void
    {
        $engine = new AIEngine($this->dummyApiKey);

        $this->assertTrue($engine->validatePrompt('Hello, AI!'));
        $this->assertTrue($engine->validatePrompt('What is 2+2?'));
    }

    public function testValidatePromptWithInvalidInput(): void
    {
        $engine = new AIEngine($this->dummyApiKey);

        $this->assertFalse($engine->validatePrompt(''));
        $this->assertFalse($engine->validatePrompt('   '));
    }

    public function testGetProviderReturnsCorrectInstance(): void
    {
        $geminiEngine = new AIEngine($this->dummyApiKey, ['provider' => 'gemini']);
        $groqEngine = AIEngine::create('groq', $this->dummyApiKey);
        $metaEngine = AIEngine::create('meta', $this->dummyApiKey);

        $this->assertInstanceOf(Gemini::class, $geminiEngine->getProvider());
        $this->assertInstanceOf(Groq::class, $groqEngine->getProvider());
        $this->assertInstanceOf(MetaLlama::class, $metaEngine->getProvider());
    }

    public function testConversationMethods(): void
    {
        $engine = new AIEngine($this->dummyApiKey);

        $engine->newConversation();

        $history = $engine->getHistory();
        $this->assertIsArray($history);
        $this->assertEmpty($history);

        $engine->setSystemInstruction('You are a helpful assistant.');
    }

    public function testLoggingConfiguration(): void
    {
        $engine = new AIEngine($this->dummyApiKey);

        $engine->enableLogging(true);
        $this->assertTrue($engine->getConfig('enable_logging'));

        $engine->enableLogging(false);
        $this->assertFalse($engine->getConfig('enable_logging'));
    }

    public function testCustomLogger(): void
    {
        $engine = new AIEngine($this->dummyApiKey);
        $logMessages = [];

        $engine->setLogger(function (string $message, string $level) use (&$logMessages): void {
            $logMessages[] = ['message' => $message, 'level' => $level];
        });

        $engine->enableLogging(true);

        // Trigger a log by starting new conversation
        $engine->newConversation();

        $this->assertNotEmpty($logMessages);
        $this->assertEquals('info', $logMessages[0]['level']);
    }

    public function testGenerateContentThrowsConfigurationExceptionWhenNotConfigured(): void
    {
        $engine = new AIEngine('');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Provider not properly configured');

        $engine->generateContent('Hello');
    }

    public function testChatThrowsConfigurationExceptionWhenNotConfigured(): void
    {
        $engine = new AIEngine('');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Provider not properly configured');

        $engine->chat('Hello');
    }

    public function testGenerateContentThrowsOnInvalidPrompt(): void
    {
        $engine = new AIEngine($this->dummyApiKey);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid prompt');

        $engine->generateContent('');
    }

    public function testKnowledgeBaseIntegration(): void
    {
        $engine = new AIEngine($this->dummyApiKey);

        $this->assertFalse($engine->hasKnowledge());

        $engine->addKnowledgeText('PHP is a programming language', 'test', 'PHP Info');

        $this->assertTrue($engine->hasKnowledge());

        $summary = $engine->getKnowledgeSummary();
        $this->assertEquals(1, $summary['count']);
    }

    public function testKnowledgeBaseSyncedToAllProviders(): void
    {
        // Test that knowledge base is synced to Gemini provider (not just Groq)
        $engine = new AIEngine($this->dummyApiKey, ['provider' => 'gemini']);
        $engine->addKnowledgeText('Test knowledge content', 'test-source');

        $provider = $engine->getProvider();
        $this->assertInstanceOf(Gemini::class, $provider);
        $this->assertTrue($provider->hasKnowledgeBase());
    }

    public function testKnowledgeBaseSyncedOnProviderSwitch(): void
    {
        $engine = new AIEngine($this->dummyApiKey, ['provider' => 'gemini']);
        $engine->addKnowledgeText('Test content', 'test-source');

        $engine->switchProvider('groq', $this->dummyApiKey);

        $provider = $engine->getProvider();
        $this->assertInstanceOf(Groq::class, $provider);
        $this->assertTrue($provider->hasKnowledgeBase());
    }

    public function testClearKnowledge(): void
    {
        $engine = new AIEngine($this->dummyApiKey);
        $engine->addKnowledgeText('Test content', 'test-source');
        $this->assertTrue($engine->hasKnowledge());

        $engine->clearKnowledge();
        $this->assertFalse($engine->hasKnowledge());
    }

    public function testSaveAndLoadKnowledge(): void
    {
        $tempFile = sys_get_temp_dir() . '/ai_engine_test_' . uniqid() . '.json';

        try {
            $engine = new AIEngine($this->dummyApiKey);
            $engine->addKnowledgeText('Persistent knowledge', 'test-source', 'Test Title');

            $this->assertTrue($engine->saveKnowledge($tempFile));

            $engine2 = new AIEngine($this->dummyApiKey);
            $this->assertTrue($engine2->loadKnowledge($tempFile));
            $this->assertTrue($engine2->hasKnowledge());

            $summary = $engine2->getKnowledgeSummary();
            $this->assertEquals(1, $summary['count']);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testGetKnowledgeSummaryWhenEmpty(): void
    {
        $engine = new AIEngine($this->dummyApiKey);
        $summary = $engine->getKnowledgeSummary();

        $this->assertEquals(0, $summary['count']);
        $this->assertEmpty($summary['sources']);
        $this->assertEquals(0, $summary['totalChars']);
    }
}

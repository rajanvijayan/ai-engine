<?php

declare(strict_types=1);

namespace AIEngine\Tests\Providers;

use AIEngine\Exceptions\ConfigurationException;
use AIEngine\Knowledge\KnowledgeBase;
use AIEngine\Providers\DeepSeek;
use AIEngine\Providers\Mistral;
use AIEngine\Providers\OpenAI;
use AIEngine\Providers\OpenAICompatibleProvider;
use AIEngine\Providers\ProviderInterface;
use AIEngine\Providers\XAI;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OpenAICompatibleProvidersTest extends TestCase
{
    private string $dummyApiKey = 'test-api-key-1234567890';

    /**
     * @return array<string, array{class-string<OpenAICompatibleProvider>, string, string}>
     */
    public static function providerClasses(): array
    {
        return [
            'OpenAI' => [OpenAI::class, 'OpenAI', 'gpt-6.1-sol'],
            'DeepSeek' => [DeepSeek::class, 'DeepSeek', 'deepseek-flash'],
            'Mistral' => [Mistral::class, 'Mistral', 'mistral-large-latest'],
            'xAI' => [XAI::class, 'xAI', 'grok-4.7'],
        ];
    }

    /**
     * @dataProvider providerClasses
     */
    #[DataProvider('providerClasses')]
    public function testImplementsProviderInterface(string $class): void
    {
        $provider = new $class($this->dummyApiKey);

        $this->assertInstanceOf(ProviderInterface::class, $provider);
        $this->assertInstanceOf(OpenAICompatibleProvider::class, $provider);
    }

    /**
     * @dataProvider providerClasses
     */
    #[DataProvider('providerClasses')]
    public function testGetName(string $class, string $name): void
    {
        $provider = new $class($this->dummyApiKey);

        $this->assertEquals($name, $provider->getName());
    }

    /**
     * @dataProvider providerClasses
     */
    #[DataProvider('providerClasses')]
    public function testIsConfigured(string $class): void
    {
        $this->assertTrue((new $class($this->dummyApiKey))->isConfigured());
        $this->assertFalse((new $class(''))->isConfigured());
    }

    /**
     * @dataProvider providerClasses
     */
    #[DataProvider('providerClasses')]
    public function testDefaultModel(string $class, string $name, string $defaultModel): void
    {
        $provider = new $class($this->dummyApiKey);

        $this->assertEquals($defaultModel, $provider->getModel());
        $this->assertEquals($defaultModel, $class::DEFAULT_MODEL);
    }

    /**
     * @dataProvider providerClasses
     */
    #[DataProvider('providerClasses')]
    public function testCustomModel(string $class): void
    {
        $provider = new $class($this->dummyApiKey, 'custom-model');

        $this->assertEquals('custom-model', $provider->getModel());

        $provider->setModel('other-model');

        $this->assertEquals('other-model', $provider->getModel());
    }

    /**
     * @dataProvider providerClasses
     */
    #[DataProvider('providerClasses')]
    public function testAvailableModelsIncludeDefault(string $class, string $name, string $defaultModel): void
    {
        $models = $class::getAvailableModels();

        $this->assertNotEmpty($models);
        $this->assertContains($defaultModel, $models);
    }

    /**
     * @dataProvider providerClasses
     */
    #[DataProvider('providerClasses')]
    public function testConversationHistory(string $class): void
    {
        $provider = new $class($this->dummyApiKey);

        $this->assertEmpty($provider->getConversationHistory());

        $provider->addToHistory('user', 'Hello');
        $provider->addToHistory('assistant', 'Hi there!');

        $history = $provider->getConversationHistory();
        $this->assertCount(2, $history);
        $this->assertEquals('assistant', $history[1]['role']);
        $this->assertEquals('Hi there!', $history[1]['content']);

        $provider->startNewConversation();

        $this->assertEmpty($provider->getConversationHistory());
    }

    /**
     * @dataProvider providerClasses
     */
    #[DataProvider('providerClasses')]
    public function testSetSystemInstruction(string $class): void
    {
        $provider = new $class($this->dummyApiKey);

        $provider->setSystemInstruction('Be concise');

        $this->assertEquals('Be concise', $provider->getSystemInstruction());
    }

    /**
     * @dataProvider providerClasses
     */
    #[DataProvider('providerClasses')]
    public function testKnowledgeBaseSupport(string $class): void
    {
        $provider = new $class($this->dummyApiKey);

        $this->assertFalse($provider->hasKnowledgeBase());

        $kb = new KnowledgeBase();
        $kb->addText('Test knowledge', 'test');
        $provider->setKnowledgeBase($kb);

        $this->assertTrue($provider->hasKnowledgeBase());
        $this->assertSame($kb, $provider->getKnowledgeBase());
    }

    /**
     * @dataProvider providerClasses
     */
    #[DataProvider('providerClasses')]
    public function testGenerateContentThrowsWhenNotConfigured(string $class): void
    {
        $provider = new $class('');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Provider not properly configured');

        $provider->generateContent('Hello');
    }

    /**
     * @dataProvider providerClasses
     */
    #[DataProvider('providerClasses')]
    public function testGenerateContentThrowsOnEmptyPrompt(string $class): void
    {
        $provider = new $class($this->dummyApiKey);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid prompt');

        $provider->generateContent('');
    }

    /**
     * @dataProvider providerClasses
     */
    #[DataProvider('providerClasses')]
    public function testSendMessageThrowsOnEmptyMessage(string $class): void
    {
        $provider = new $class($this->dummyApiKey);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid message');

        $provider->sendMessage('');
    }
}

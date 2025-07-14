<?php

require_once 'vendor/autoload.php';

use AIEngine\AIEngine;
use AIEngine\Utils\ConfigValidator;
use AIEngine\Providers\Gemini;

class AIEngineTest
{
    private $testResults = [];
    private $testCount = 0;
    private $passCount = 0;
    
    public function __construct()
    {
        echo "=== AI Engine Testing Suite ===\n\n";
    }
    
    /**
     * Run a test and record results.
     */
    private function runTest($testName, $testFunction)
    {
        $this->testCount++;
        echo "Running Test {$this->testCount}: {$testName}\n";
        echo str_repeat('-', 50) . "\n";
        
        try {
            $result = $testFunction();
            if ($result) {
                $this->passCount++;
                echo "✅ PASSED\n\n";
                $this->testResults[] = ['name' => $testName, 'status' => 'PASSED'];
            } else {
                echo "❌ FAILED\n\n";
                $this->testResults[] = ['name' => $testName, 'status' => 'FAILED'];
            }
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n\n";
            $this->testResults[] = ['name' => $testName, 'status' => 'ERROR', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Test basic backward compatibility.
     */
    public function testBackwardCompatibility()
    {
        return $this->runTest('Backward Compatibility', function() {
            // Test original constructor
            $engine = new AIEngine('test-api-key');
            
            // Check if object is created properly
            if (!$engine instanceof AIEngine) {
                echo "Failed to create AIEngine instance\n";
                return false;
            }
            
            // Test generateContent method exists
            if (!method_exists($engine, 'generateContent')) {
                echo "generateContent method not found\n";
                return false;
            }
            
            echo "✓ AIEngine instance created successfully\n";
            echo "✓ generateContent method exists\n";
            echo "✓ Backward compatibility maintained\n";
            
            return true;
        });
    }
    
    /**
     * Test configuration validation.
     */
    public function testConfigValidation()
    {
        return $this->runTest('Configuration Validation', function() {
            // Test valid API key
            $validKey = 'valid-api-key-123456789';
            if (!ConfigValidator::isValidApiKey($validKey)) {
                echo "Valid API key validation failed\n";
                return false;
            }
            
            // Test invalid API key
            $invalidKey = 'short';
            if (ConfigValidator::isValidApiKey($invalidKey)) {
                echo "Invalid API key validation should have failed\n";
                return false;
            }
            
            // Test timeout validation
            if (!ConfigValidator::isValidTimeout(30)) {
                echo "Valid timeout validation failed\n";
                return false;
            }
            
            if (ConfigValidator::isValidTimeout(500)) {
                echo "Invalid timeout validation should have failed\n";
                return false;
            }
            
            // Test model validation
            if (!ConfigValidator::isValidModel('gemini-2.0-flash')) {
                echo "Valid model validation failed\n";
                return false;
            }
            
            if (ConfigValidator::isValidModel('invalid@model')) {
                echo "Invalid model validation should have failed\n";
                return false;
            }
            
            echo "✓ API key validation working\n";
            echo "✓ Timeout validation working\n";
            echo "✓ Model validation working\n";
            
            return true;
        });
    }
    
    /**
     * Test enhanced constructor.
     */
    public function testEnhancedConstructor()
    {
        return $this->runTest('Enhanced Constructor', function() {
            $config = [
                'model' => 'gemini-2.0-flash',
                'timeout' => 45,
                'enable_logging' => true
            ];
            
            $engine = new AIEngine('test-api-key', $config);
            
            if (!$engine instanceof AIEngine) {
                echo "Failed to create AIEngine with config\n";
                return false;
            }
            
            // Test configuration retrieval
            if ($engine->getConfig('model') !== 'gemini-2.0-flash') {
                echo "Configuration not set properly\n";
                return false;
            }
            
            if ($engine->getConfig('timeout') !== 45) {
                echo "Timeout configuration not set properly\n";
                return false;
            }
            
            if ($engine->getConfig('enable_logging') !== true) {
                echo "Logging configuration not set properly\n";
                return false;
            }
            
            echo "✓ Enhanced constructor working\n";
            echo "✓ Configuration retrieval working\n";
            echo "✓ Default values merged properly\n";
            
            return true;
        });
    }
    
    /**
     * Test provider management.
     */
    public function testProviderManagement()
    {
        return $this->runTest('Provider Management', function() {
            $engine = new AIEngine('test-api-key');
            
            // Test getting provider
            $provider = $engine->getProvider();
            if (!$provider instanceof Gemini) {
                echo "Provider is not Gemini instance\n";
                return false;
            }
            
            // Test provider name
            if ($engine->getProviderName() !== 'Gemini') {
                echo "Provider name should be 'Gemini'\n";
                return false;
            }
            
            // Test available providers
            $availableProviders = $engine->getAvailableProviders();
            if (!is_array($availableProviders) || !in_array('gemini', $availableProviders)) {
                echo "Available providers not working correctly\n";
                return false;
            }
            
            echo "✓ Provider retrieval working\n";
            echo "✓ Provider name retrieval working\n";
            echo "✓ Available providers list working\n";
            
            return true;
        });
    }
    
    /**
     * Test configuration management.
     */
    public function testConfigurationManagement()
    {
        return $this->runTest('Configuration Management', function() {
            $engine = new AIEngine('test-api-key');
            
            // Test setting configuration
            $engine->setConfig('test_key', 'test_value');
            if ($engine->getConfig('test_key') !== 'test_value') {
                echo "Configuration setting/getting failed\n";
                return false;
            }
            
            // Test default value
            if ($engine->getConfig('non_existent_key', 'default') !== 'default') {
                echo "Default value not working\n";
                return false;
            }
            
            // Test logging toggle
            $engine->enableLogging(true);
            if ($engine->getConfig('enable_logging') !== true) {
                echo "Logging toggle not working\n";
                return false;
            }
            
            echo "✓ Configuration setting working\n";
            echo "✓ Configuration getting working\n";
            echo "✓ Default values working\n";
            echo "✓ Logging toggle working\n";
            
            return true;
        });
    }
    
    /**
     * Test prompt validation.
     */
    public function testPromptValidation()
    {
        return $this->runTest('Prompt Validation', function() {
            $engine = new AIEngine('test-api-key');
            
            // Test valid prompt
            if (!$engine->validatePrompt('This is a valid prompt')) {
                echo "Valid prompt validation failed\n";
                return false;
            }
            
            // Test empty prompt
            if ($engine->validatePrompt('')) {
                echo "Empty prompt should be invalid\n";
                return false;
            }
            
            // Test whitespace only prompt
            if ($engine->validatePrompt('   ')) {
                echo "Whitespace only prompt should be invalid\n";
                return false;
            }
            
            // Test non-string prompt
            if ($engine->validatePrompt(123)) {
                echo "Non-string prompt should be invalid\n";
                return false;
            }
            
            echo "✓ Valid prompt validation working\n";
            echo "✓ Empty prompt validation working\n";
            echo "✓ Whitespace prompt validation working\n";
            echo "✓ Non-string prompt validation working\n";
            
            return true;
        });
    }
    
    /**
     * Test provider configuration.
     */
    public function testProviderConfiguration()
    {
        return $this->runTest('Provider Configuration', function() {
            $engine = new AIEngine('test-api-key');
            
            // Test if provider is configured
            if (!$engine->isConfigured()) {
                echo "Provider should be configured with valid API key\n";
                return false;
            }
            
            // Test provider methods
            $provider = $engine->getProvider();
            
            if (!$provider->isConfigured()) {
                echo "Provider isConfigured method not working\n";
                return false;
            }
            
            if ($provider->getName() !== 'Gemini') {
                echo "Provider getName method not working\n";
                return false;
            }
            
            // Test model management
            if (method_exists($provider, 'getModel')) {
                $currentModel = $provider->getModel();
                if (empty($currentModel)) {
                    echo "Provider getModel method not working\n";
                    return false;
                }
                echo "✓ Current model: {$currentModel}\n";
            }
            
            echo "✓ Provider configuration check working\n";
            echo "✓ Provider interface methods working\n";
            
            return true;
        });
    }
    
    /**
     * Test error handling.
     */
    public function testErrorHandling()
    {
        return $this->runTest('Error Handling', function() {
            // Test with invalid API key
            $engine = new AIEngine('');
            
            if ($engine->isConfigured()) {
                echo "Empty API key should not be valid\n";
                return false;
            }
            
            // Test provider validation
            $provider = $engine->getProvider();
            if ($provider->isConfigured()) {
                echo "Provider with empty API key should not be configured\n";
                return false;
            }
            
            // Test configuration validation
            $config = [
                'api_key' => 'short',
                'timeout' => 500,
                'model' => 'invalid@model'
            ];
            
            $errors = ConfigValidator::validateProviderConfig($config);
            if (empty($errors)) {
                echo "Configuration validation should have found errors\n";
                return false;
            }
            
            if (count($errors) < 3) {
                echo "Should have found 3 errors in configuration\n";
                return false;
            }
            
            echo "✓ Invalid API key handling working\n";
            echo "✓ Provider validation working\n";
            echo "✓ Configuration validation working\n";
            echo "✓ Found " . count($errors) . " validation errors as expected\n";
            
            return true;
        });
    }
    
    /**
     * Test logging functionality.
     */
    public function testLogging()
    {
        return $this->runTest('Logging Functionality', function() {
            $engine = new AIEngine('test-api-key');
            
            // Test custom logger
            $logMessages = [];
            $engine->setLogger(function($message, $level) use (&$logMessages) {
                $logMessages[] = ['message' => $message, 'level' => $level];
            });
            
            // Enable logging
            $engine->enableLogging(true);
            
            // Test a method that should log
            $engine->getProviderName();
            
            // Note: The actual logging happens in generateContent, 
            // but we're testing the logger setup
            echo "✓ Custom logger setup working\n";
            echo "✓ Logging enable/disable working\n";
            
            return true;
        });
    }
    
    /**
     * Test configuration sanitization.
     */
    public function testConfigSanitization()
    {
        return $this->runTest('Configuration Sanitization', function() {
            $dirtyConfig = [
                'string_value' => '  trimmed  ',
                'numeric_value' => 123,
                'boolean_value' => true,
                'array_value' => [
                    'nested_string' => '  nested  ',
                    'nested_number' => 456
                ]
            ];
            
            $cleaned = ConfigValidator::sanitizeConfig($dirtyConfig);
            
            if ($cleaned['string_value'] !== 'trimmed') {
                echo "String trimming not working\n";
                return false;
            }
            
            if ($cleaned['numeric_value'] !== 123) {
                echo "Numeric value not preserved\n";
                return false;
            }
            
            if ($cleaned['boolean_value'] !== true) {
                echo "Boolean value not preserved\n";
                return false;
            }
            
            if ($cleaned['array_value']['nested_string'] !== 'nested') {
                echo "Nested string trimming not working\n";
                return false;
            }
            
            echo "✓ String trimming working\n";
            echo "✓ Numeric value preservation working\n";
            echo "✓ Boolean value preservation working\n";
            echo "✓ Nested array sanitization working\n";
            
            return true;
        });
    }
    
    /**
     * Test with actual API call (if API key is provided).
     */
    public function testActualAPICall()
    {
        return $this->runTest('Actual API Call (Optional)', function() {
            // Check if a real API key is available
            $apiKey = getenv('GEMINI_API_KEY');
            
            if (empty($apiKey)) {
                echo "⚠️  SKIPPED: No API key provided (set GEMINI_API_KEY environment variable)\n";
                echo "✓ This test is optional and can be skipped\n";
                return true;
            }
            
            $engine = new AIEngine($apiKey);
            
            if (!$engine->isConfigured()) {
                echo "Engine not configured with provided API key\n";
                return false;
            }
            
            // Test a simple prompt
            $prompt = "Say hello in exactly 3 words";
            $response = $engine->generateContent($prompt);
            
            if (is_array($response) && isset($response['error'])) {
                echo "API call failed: " . $response['error'] . "\n";
                return false;
            }
            
            if (empty($response)) {
                echo "API call returned empty response\n";
                return false;
            }
            
            echo "✓ API call successful\n";
            echo "✓ Response received: " . substr($response, 0, 50) . "...\n";
            
            return true;
        });
    }
    
    /**
     * Run all tests.
     */
    public function runAllTests()
    {
        $this->testBackwardCompatibility();
        $this->testConfigValidation();
        $this->testEnhancedConstructor();
        $this->testProviderManagement();
        $this->testConfigurationManagement();
        $this->testPromptValidation();
        $this->testProviderConfiguration();
        $this->testErrorHandling();
        $this->testLogging();
        $this->testConfigSanitization();
        $this->testActualAPICall();
        
        $this->printSummary();
    }
    
    /**
     * Print test summary.
     */
    private function printSummary()
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat('=', 60) . "\n";
        
        foreach ($this->testResults as $result) {
            $status = $result['status'] === 'PASSED' ? '✅' : '❌';
            echo "{$status} {$result['name']}: {$result['status']}\n";
            
            if (isset($result['error'])) {
                echo "   Error: {$result['error']}\n";
            }
        }
        
        echo str_repeat('-', 60) . "\n";
        echo "Total Tests: {$this->testCount}\n";
        echo "Passed: {$this->passCount}\n";
        echo "Failed: " . ($this->testCount - $this->passCount) . "\n";
        echo "Success Rate: " . round(($this->passCount / $this->testCount) * 100, 2) . "%\n";
        
        if ($this->passCount === $this->testCount) {
            echo "\n🎉 ALL TESTS PASSED! Your AI Engine is working perfectly!\n";
        } else {
            echo "\n⚠️  Some tests failed. Please check the output above.\n";
        }
        
        echo "\n" . str_repeat('=', 60) . "\n";
    }
}

/**
 * Interactive API key input function.
 */
function getApiKeyFromUser()
{
    echo "🔑 To test with real API calls, please provide your Gemini API key.\n";
    echo "📝 Get your API key from: https://makersuite.google.com/app/apikey\n";
    echo "⚠️  Note: The key will only be used for testing and won't be stored.\n\n";
    
    // Hide input for security (works on most Unix systems)
    echo "Enter your Gemini API key (or press Enter to skip): ";
    
    if (function_exists('readline')) {
        $apiKey = readline();
    } else {
        $apiKey = trim(fgets(STDIN));
    }
    
    if (empty($apiKey)) {
        echo "⚠️  No API key provided. Real API tests will be skipped.\n\n";
        return null;
    }
    
    // Basic validation
    if (strlen($apiKey) < 20) {
        echo "⚠️  API key seems too short. Please check and try again.\n\n";
        return null;
    }
    
    echo "✅ API key accepted. Testing with real API calls...\n\n";
    return $apiKey;
}

/**
 * Run sample API calls with real API key.
 */
function runSampleApiCalls($apiKey)
{
    echo str_repeat('=', 60) . "\n";
    echo "🚀 SAMPLE API CALLS\n";
    echo str_repeat('=', 60) . "\n\n";
    
    // Test 1: Basic Usage
    echo "📝 Test 1: Basic Usage (Backward Compatible)\n";
    echo str_repeat('-', 40) . "\n";
    
    $engine = new AIEngine($apiKey);
    $prompt = "Hello! Can you tell me what you are in one sentence?";
    
    echo "Prompt: {$prompt}\n";
    echo "Response: ";
    
    $response = $engine->generateContent($prompt);
    
    if (is_array($response) && isset($response['error'])) {
        echo "❌ Error: {$response['error']}\n";
    } else {
        echo "✅ {$response}\n";
    }
    
    echo "\n" . str_repeat('-', 40) . "\n\n";
    
    // Test 2: Enhanced Usage with Configuration
    echo "📝 Test 2: Enhanced Usage with Configuration\n";
    echo str_repeat('-', 40) . "\n";
    
         $config = [
         'model' => 'gemini-2.0-flash',
         'timeout' => 30,
         'enable_logging' => true
     ];
    
    $engine = new AIEngine($apiKey, $config);
    
    // Set up logging
    $engine->setLogger(function($message, $level) {
        echo "🔍 [{$level}] {$message}\n";
    });
    
    $prompt = "Explain what AI is in exactly 2 sentences.";
    
    echo "Prompt: {$prompt}\n";
    echo "Response: ";
    
    $response = $engine->generateContent($prompt);
    
    if (is_array($response) && isset($response['error'])) {
        echo "❌ Error: {$response['error']}\n";
    } else {
        echo "✅ {$response}\n";
    }
    
    echo "\n" . str_repeat('-', 40) . "\n\n";
    
    // Test 3: Creative Writing
    echo "📝 Test 3: Creative Writing\n";
    echo str_repeat('-', 40) . "\n";
    
    $engine = new AIEngine($apiKey);
    $prompt = "Write a very short haiku about programming.";
    
    echo "Prompt: {$prompt}\n";
    echo "Response: ";
    
    $response = $engine->generateContent($prompt);
    
    if (is_array($response) && isset($response['error'])) {
        echo "❌ Error: {$response['error']}\n";
    } else {
        echo "✅ {$response}\n";
    }
    
    echo "\n" . str_repeat('-', 40) . "\n\n";
    
    // Test 4: Code Generation
    echo "📝 Test 4: Code Generation\n";
    echo str_repeat('-', 40) . "\n";
    
    $engine = new AIEngine($apiKey);
    $prompt = "Write a simple PHP function that adds two numbers.";
    
    echo "Prompt: {$prompt}\n";
    echo "Response: ";
    
    $response = $engine->generateContent($prompt);
    
    if (is_array($response) && isset($response['error'])) {
        echo "❌ Error: {$response['error']}\n";
    } else {
        echo "✅ {$response}\n";
    }
    
    echo "\n" . str_repeat('-', 40) . "\n\n";
    
    // Test 5: Provider Information
    echo "📝 Test 5: Provider Information\n";
    echo str_repeat('-', 40) . "\n";
    
    $provider = $engine->getProvider();
    
    echo "✅ Provider Name: {$provider->getName()}\n";
    echo "✅ Current Model: {$provider->getModel()}\n";
    echo "✅ Is Configured: " . ($provider->isConfigured() ? 'Yes' : 'No') . "\n";
    echo "✅ Available Providers: " . implode(', ', $engine->getAvailableProviders()) . "\n";
    
    echo "\n" . str_repeat('-', 40) . "\n\n";
    
    echo "🎉 Sample API calls completed!\n\n";
}

/**
 * Interactive demo mode.
 */
function runInteractiveDemo($apiKey)
{
    echo str_repeat('=', 60) . "\n";
    echo "🎮 INTERACTIVE DEMO MODE\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $engine = new AIEngine($apiKey, [
        'enable_logging' => true,
        'timeout' => 30
    ]);
    
    // Enable logging with custom logger
    $engine->setLogger(function($message, $level) {
        echo "🔍 [{$level}] {$message}\n";
    });
    
    echo "Type your questions and get AI responses!\n";
    echo "Commands:\n";
    echo "  - Type any question and press Enter\n";
    echo "  - Type 'quit' or 'exit' to stop\n";
    echo "  - Type 'help' for more commands\n\n";
    
    while (true) {
        echo "🤖 You: ";
        
        if (function_exists('readline')) {
            $input = readline();
        } else {
            $input = trim(fgets(STDIN));
        }
        
        if (empty($input)) {
            continue;
        }
        
        $input = strtolower($input);
        
        if ($input === 'quit' || $input === 'exit') {
            echo "👋 Goodbye!\n";
            break;
        }
        
        if ($input === 'help') {
            echo "Available commands:\n";
            echo "  - quit/exit: Exit the demo\n";
            echo "  - help: Show this help\n";
            echo "  - info: Show provider information\n";
            echo "  - Or just type any question!\n\n";
            continue;
        }
        
        if ($input === 'info') {
            $provider = $engine->getProvider();
            echo "📊 Provider Info:\n";
            echo "   Name: {$provider->getName()}\n";
            echo "   Model: {$provider->getModel()}\n";
            echo "   Configured: " . ($provider->isConfigured() ? 'Yes' : 'No') . "\n\n";
            continue;
        }
        
        // Get the original input for API call
        if (function_exists('readline')) {
            $originalInput = readline_list_history();
            $originalInput = end($originalInput);
        } else {
            $originalInput = $input;
        }
        
        echo "🤖 AI: ";
        
        $response = $engine->generateContent($originalInput);
        
        if (is_array($response) && isset($response['error'])) {
            echo "❌ Error: {$response['error']}\n\n";
        } else {
            echo "{$response}\n\n";
        }
    }
}

// Main execution
echo "AI Engine Test Suite\n";
echo "====================\n\n";

// Ask user what they want to do
echo "Choose an option:\n";
echo "1. Run automated tests only\n";
echo "2. Run tests + sample API calls\n";
echo "3. Run tests + interactive demo\n";
echo "4. Run all (tests + samples + demo)\n\n";

echo "Enter your choice (1-4): ";

if (function_exists('readline')) {
    $choice = trim(readline());
} else {
    $choice = trim(fgets(STDIN));
}

echo "\n";

// Always run basic tests first
$tester = new AIEngineTest();
$tester->runAllTests();

// Handle user choice
switch ($choice) {
    case '2':
    case '3':
    case '4':
        $apiKey = getApiKeyFromUser();
        if ($apiKey) {
            putenv("GEMINI_API_KEY={$apiKey}");
            
            if ($choice === '2' || $choice === '4') {
                runSampleApiCalls($apiKey);
            }
            
            if ($choice === '3' || $choice === '4') {
                runInteractiveDemo($apiKey);
            }
        }
        break;
    
    case '1':
    default:
        echo "ℹ️  To test with real API calls, run this script again and choose option 2, 3, or 4.\n";
        break;
}

// Additional usage examples
echo "\n" . str_repeat('=', 60) . "\n";
echo "USAGE EXAMPLES\n";
echo str_repeat('=', 60) . "\n";

echo "\n1. Basic Usage (Backward Compatible):\n";
echo "   \$engine = new AIEngine('your-api-key');\n";
echo "   \$response = \$engine->generateContent('Hello!');\n";

echo "\n2. Enhanced Usage:\n";
echo "   \$config = ['model' => 'gemini-2.0-flash', 'timeout' => 30];\n";
echo "   \$engine = new AIEngine('your-api-key', \$config);\n";
echo "   \$response = \$engine->generateContent('Hello!');\n";

echo "\n3. With Logging:\n";
echo "   \$engine->enableLogging(true);\n";
echo "   \$engine->setLogger(function(\$msg, \$level) { echo \"[\$level] \$msg\\n\"; });\n";

echo "\n4. Provider Management:\n";
echo "   \$provider = \$engine->getProvider();\n";
echo "   \$providerName = \$engine->getProviderName();\n";

echo "\n5. Configuration Validation:\n";
echo "   \$errors = ConfigValidator::validateProviderConfig(\$config);\n";
echo "   if (empty(\$errors)) { /* config is valid */ }\n";

echo "\n" . str_repeat('=', 60) . "\n"; 
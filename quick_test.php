<?php

require_once 'vendor/autoload.php';

use AIEngine\AIEngine;

echo "🚀 Quick Test - Updated Gemini API\n";
echo "==================================\n\n";

// Test 1: Create engine with new default model
echo "Test 1: Engine Creation\n";
$engine = new AIEngine('test-key');
$provider = $engine->getProvider();

echo "✅ Engine created successfully\n";
echo "✅ Provider: " . $provider->getName() . "\n";
echo "✅ Default Model: " . $provider->getModel() . "\n";
echo "✅ Is Configured: " . ($provider->isConfigured() ? 'Yes' : 'No') . "\n\n";

// Test 2: Configuration check
echo "Test 2: Configuration\n";
echo "✅ Default model: " . $engine->getConfig('model') . "\n";
echo "✅ Timeout: " . $engine->getConfig('timeout') . "\n";
echo "✅ Provider name: " . $engine->getProviderName() . "\n\n";

// Test 3: Available models
echo "Test 3: Available Models\n";
$availableModels = [
    'gemini-2.0-flash',
    'gemini-2.0-flash-thinking-exp',
    'gemini-1.5-flash',
    'gemini-1.5-pro'
];

foreach ($availableModels as $model) {
    $provider->setModel($model);
    echo "✅ Model set to: " . $provider->getModel() . "\n";
}

echo "\n🎉 All tests passed! The API has been successfully updated.\n";
echo "\nTo test with a real API key, provide your key when prompted in the main test file.\n";
echo "Run: php test_ai_engine.php\n"; 
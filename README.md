# AI Engine - PHP Library for Google Gemini API

A powerful, flexible PHP library for integrating Google's Gemini AI API into your applications. Built with modern PHP practices, comprehensive error handling, and extensive configuration options.

## 🚀 Features

- **Easy Integration**: Simple, intuitive API that works out of the box
- **Backward Compatible**: Existing code continues to work unchanged
- **Multiple Model Support**: Gemini 2.0 Flash, Gemini 1.5 Pro, and more
- **Advanced Configuration**: Timeouts, logging, custom models
- **Input Validation**: Comprehensive prompt and configuration validation
- **Error Handling**: Detailed error messages and graceful failure handling
- **Logging System**: Built-in logging with custom logger support
- **Provider Architecture**: Extensible design for future AI service integration
- **Comprehensive Testing**: Full test suite with interactive demos

## 📋 Requirements

- PHP 7.4 or higher
- `json` extension
- `curl` extension (for HTTP requests)
- Valid Google Gemini API key

## 🛠️ Installation

### Via Composer (Recommended)
```bash
composer require rajanvijayan/ai-engine
```

### Manual Installation
1. Download the source code
2. Include the autoloader in your project
3. Make sure all dependencies are available

## 🔑 Getting Your API Key

1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Sign in with your Google account
3. Click "Create API key"
4. Copy the generated key

## 🚀 Quick Start

### Basic Usage (Backward Compatible)
```php
<?php
require_once 'vendor/autoload.php';

use AIEngine\AIEngine;

// Simple initialization
$engine = new AIEngine('your-api-key-here');

// Generate content
$response = $engine->generateContent('Hello! How are you?');
echo $response;
```

### Enhanced Usage with Configuration
```php
<?php
use AIEngine\AIEngine;

// Advanced configuration
$config = [
    'model' => 'gemini-2.0-flash',
    'timeout' => 30,
    'enable_logging' => true
];

$engine = new AIEngine('your-api-key-here', $config);

// Enable custom logging
$engine->setLogger(function($message, $level) {
    echo "[{$level}] {$message}\n";
});

// Generate content with logging
$response = $engine->generateContent('Explain quantum computing in simple terms');
echo $response;
```

## 📚 API Documentation

### AIEngine Class

#### Constructor
```php
public function __construct($apiKey, $config = [])
```

**Parameters:**
- `$apiKey` (string): Your Google Gemini API key
- `$config` (array): Optional configuration array

**Default Configuration:**
```php
[
    'default_provider' => 'gemini',
    'model' => 'gemini-2.0-flash',
    'timeout' => 60,
    'enable_logging' => false
]
```

#### Methods

##### `generateContent($prompt)`
Generate AI content from a text prompt.

**Parameters:**
- `$prompt` (string): The text prompt to send to the AI

**Returns:**
- `string`: Generated content on success
- `array`: Error information on failure

**Example:**
```php
$response = $engine->generateContent('Write a haiku about PHP');
if (is_array($response) && isset($response['error'])) {
    echo "Error: " . $response['error'];
} else {
    echo $response;
}
```

##### `setProvider(ProviderInterface $provider)`
Set a custom AI provider.

##### `getProvider()`
Get the current AI provider instance.

##### `getProviderName()`
Get the name of the current provider.

##### `isConfigured()`
Check if the engine is properly configured.

##### `getConfig($key, $default = null)`
Get a configuration value.

##### `setConfig($key, $value)`
Set a configuration value.

##### `enableLogging($enable = true)`
Enable or disable logging.

##### `setLogger($logger)`
Set a custom logger function.

##### `validatePrompt($prompt)`
Validate a prompt before processing.

##### `getAvailableProviders()`
Get list of available AI providers.

## 🔧 Configuration Options

### Available Models
- `gemini-2.0-flash` (default) - Latest, fastest model
- `gemini-2.0-flash-thinking-exp` - Experimental thinking model
- `gemini-1.5-flash` - Fast, efficient model
- `gemini-1.5-pro` - Most capable model

### Configuration Parameters
```php
$config = [
    'model' => 'gemini-2.0-flash',        // AI model to use
    'timeout' => 60,                      // Request timeout in seconds
    'enable_logging' => false,            // Enable/disable logging
    'default_provider' => 'gemini'        // Default AI provider
];
```

## 🎯 Usage Examples

### 1. Creative Writing
```php
$engine = new AIEngine('your-api-key');
$story = $engine->generateContent('Write a short story about a robot learning to paint');
echo $story;
```

### 2. Code Generation
```php
$engine = new AIEngine('your-api-key');
$code = $engine->generateContent('Create a PHP function to calculate factorial');
echo $code;
```

### 3. Question Answering
```php
$engine = new AIEngine('your-api-key');
$answer = $engine->generateContent('What is the capital of France?');
echo $answer;
```

### 4. With Custom Model
```php
$config = ['model' => 'gemini-1.5-pro'];
$engine = new AIEngine('your-api-key', $config);
$response = $engine->generateContent('Explain machine learning concepts');
echo $response;
```

### 5. With Logging
```php
$engine = new AIEngine('your-api-key');
$engine->enableLogging(true);
$engine->setLogger(function($message, $level) {
    file_put_contents('ai-engine.log', "[{$level}] {$message}\n", FILE_APPEND);
});

$response = $engine->generateContent('Hello AI!');
```

## 🛡️ Error Handling

The library provides comprehensive error handling:

```php
$response = $engine->generateContent('Your prompt here');

if (is_array($response) && isset($response['error'])) {
    switch ($response['error']) {
        case 'Provider not properly configured':
            echo "Please check your API key";
            break;
        case 'Invalid prompt: must be a non-empty string under 30000 characters':
            echo "Please provide a valid prompt";
            break;
        case 'Error contacting API':
            echo "Network or API error occurred";
            break;
        default:
            echo "Unknown error: " . $response['error'];
    }
} else {
    echo "Success: " . $response;
}
```

## 📊 Provider Management

### Working with Providers
```php
$engine = new AIEngine('your-api-key');

// Get current provider
$provider = $engine->getProvider();
echo "Provider: " . $provider->getName();

// Check if configured
if ($provider->isConfigured()) {
    echo "Provider is ready";
}

// Change model (if supported)
if (method_exists($provider, 'setModel')) {
    $provider->setModel('gemini-1.5-pro');
    echo "Model changed to: " . $provider->getModel();
}

// Change timeout
if (method_exists($provider, 'setTimeout')) {
    $provider->setTimeout(120);
    echo "Timeout set to 120 seconds";
}
```

## 🔍 Configuration Validation

Use the built-in validator to check your configuration:

```php
use AIEngine\Utils\ConfigValidator;

$config = [
    'api_key' => 'your-api-key-here',
    'timeout' => 45,
    'model' => 'gemini-2.0-flash'
];

$errors = ConfigValidator::validateProviderConfig($config);

if (empty($errors)) {
    echo "Configuration is valid!";
    
    // Sanitize configuration
    $sanitized = ConfigValidator::sanitizeConfig($config);
    $engine = new AIEngine($sanitized['api_key'], $sanitized);
} else {
    echo "Configuration errors:\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
}
```

## 🧪 Testing

### Run All Tests
```bash
php test_ai_engine.php
```

### Interactive Testing Options
1. **Automated tests only** - Validates all functionality without API calls
2. **Tests + Sample API calls** - Includes real API testing
3. **Tests + Interactive demo** - Live chat interface
4. **Run all** - Complete testing suite

### Test Features
- ✅ Backward compatibility validation
- ✅ Configuration validation
- ✅ Provider management testing
- ✅ Error handling verification
- ✅ Logging functionality
- ✅ Real API call testing (optional)
- ✅ Interactive demo mode

### Sample API Test
```bash
export GEMINI_API_KEY='your-actual-api-key'
php test_ai_engine.php
```

## 🏗️ Architecture

### Provider Interface
The library uses a provider pattern for extensibility:

```php
interface ProviderInterface
{
    public function generateContent($prompt);
    public function isConfigured();
    public function getName();
}
```

### Adding New Providers
To add a new AI provider:

1. Implement the `ProviderInterface`
2. Add validation logic
3. Update the `AIEngine` class to support the new provider
4. Add tests for the new provider

## 📁 Project Structure

```
ai-engine/
├── src/
│   ├── AIEngine.php                 # Main engine class
│   ├── Providers/
│   │   ├── ProviderInterface.php    # Provider interface
│   │   └── Gemini.php              # Gemini provider
│   ├── Utils/
│   │   └── ConfigValidator.php     # Configuration validation
│   └── Examples/
│       └── BasicUsage.php          # Usage examples
├── tests/
│   └── AIEngineTest.php           # Test suite
├── test_ai_engine.php             # Interactive test runner
├── quick_test.php                 # Quick validation test
├── composer.json                  # Dependencies
└── README.md                      # This file
```

## 🚨 Common Issues & Solutions

### API Key Issues
```php
// Check if API key is configured
if (!$engine->isConfigured()) {
    echo "API key not configured properly";
}

// Validate API key format
if (!ConfigValidator::isValidApiKey($apiKey)) {
    echo "Invalid API key format";
}
```

### Timeout Issues
```php
// Increase timeout for complex requests
$config = ['timeout' => 120];
$engine = new AIEngine($apiKey, $config);

// Or set timeout on provider
$provider = $engine->getProvider();
$provider->setTimeout(120);
```

### Model Issues
```php
// Check available models
$availableModels = [
    'gemini-2.0-flash',
    'gemini-2.0-flash-thinking-exp',
    'gemini-1.5-flash',
    'gemini-1.5-pro'
];

// Set specific model
$provider = $engine->getProvider();
$provider->setModel('gemini-1.5-pro');
```

## 🔄 Migration Guide

### From v1.0 to v2.0
The library maintains backward compatibility, but you can take advantage of new features:

**Old way (still works):**
```php
$engine = new AIEngine('api-key');
$response = $engine->generateContent('Hello');
```

**New way (recommended):**
```php
$config = ['model' => 'gemini-2.0-flash', 'timeout' => 30];
$engine = new AIEngine('api-key', $config);
$response = $engine->generateContent('Hello');
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Issues**: Report bugs or request features via GitHub Issues
- **Documentation**: Check the examples in the `src/Examples/` directory
- **Testing**: Run the test suite to validate your setup

## 🙏 Acknowledgments

- Google for providing the Gemini API
- The PHP community for excellent tools and libraries
- Contributors and users who help improve this library

---

**Made with ❤️ for the PHP community**


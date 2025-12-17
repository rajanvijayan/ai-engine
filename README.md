# AI Engine - Multi-Provider PHP Library

A powerful, flexible PHP library for integrating multiple AI providers into your applications. Supports **Google Gemini**, **Meta Llama**, and **Groq** with conversation history.

## 🚀 Features

- **Multi-Provider Support**: Gemini, Meta Llama, and Groq (Llama)
- **Conversation Mode**: Maintains chat history for context-aware responses
- **Easy Provider Switching**: Switch between providers on the fly
- **System Instructions**: Set AI personality/behavior per conversation
- **Simple API**: Intuitive interface that works out of the box
- **Extensible**: Easy to add new providers

## 📋 Requirements

- PHP 8.0 or higher
- `json` extension
- Composer

## 🛠️ Installation

```bash
composer require rajanvijayan/ai-engine
```

## 🔑 API Keys Setup

Create a `test.ai-key` file in your project root:

```json
{
    "gemini": "YOUR_GEMINI_API_KEY",
    "meta": "YOUR_META_LLAMA_API_KEY",
    "groq": "YOUR_GROQ_API_KEY"
}
```

### Get Your API Keys

| Provider | URL | Notes |
|----------|-----|-------|
| Gemini | [makersuite.google.com](https://makersuite.google.com/app/apikey) | Google AI |
| Meta Llama | [llama.meta.com](https://llama.meta.com) | Official Meta API |
| Groq | [console.groq.com](https://console.groq.com) | **FREE tier available!** |

## 🚀 Quick Start

### Basic Usage

```php
<?php
require_once 'vendor/autoload.php';

use AIEngine\AIEngine;

// Create with Gemini (default)
$ai = new AIEngine('your-gemini-api-key');
$response = $ai->generateContent('Hello! How are you?');
echo $response;
```

### Choose Provider

```php
// Gemini
$ai = new AIEngine($geminiKey, ['provider' => 'gemini']);

// Meta Llama
$ai = new AIEngine($metaKey, ['provider' => 'meta']);

// Groq (Llama models)
$ai = AIEngine::create('groq', $groqKey);
```

### Conversation Mode

```php
$ai = AIEngine::create('groq', $groqKey);
$ai->setSystemInstruction("You are a helpful assistant.");

// Start a conversation
$ai->chat("My name is John");
$ai->chat("What's my name?");  // AI remembers: "John"

// Clear conversation
$ai->newConversation();
```

## 📚 API Reference

### Constructor

```php
new AIEngine($apiKey, $config = [])
```

**Config Options:**
```php
[
    'provider' => 'gemini',     // 'gemini', 'meta', or 'groq'
    'model' => null,            // Model name (uses provider default if null)
    'timeout' => 60,            // Request timeout in seconds
    'enable_logging' => false   // Enable logging
]
```

### Methods

| Method | Description |
|--------|-------------|
| `generateContent($prompt)` | Single prompt, no history |
| `chat($message)` | Send message with conversation history |
| `newConversation()` | Clear conversation history |
| `getHistory()` | Get conversation history array |
| `setSystemInstruction($text)` | Set AI personality/behavior |
| `switchProvider($name, $apiKey)` | Switch to different provider |
| `getProviderName()` | Get current provider name |

### Static Methods

| Method | Description |
|--------|-------------|
| `AIEngine::create($provider, $apiKey)` | Factory method |
| `AIEngine::getModelsForProvider($provider)` | List available models |
| `AIEngine::getDefaultModels()` | Get default model per provider |

## 🤖 Available Models

### Gemini
- `gemini-2.0-flash` (default)
- `gemini-2.0-flash-lite`
- `gemini-2.5-flash`
- `gemini-2.5-pro`

### Meta Llama
- `Llama-4-Maverick-17B-128E-Instruct-FP8` (default)
- `Llama-4-Scout-17B-16E-Instruct`
- `Llama-3.3-70B-Instruct`
- `Llama-3.2-3B-Instruct`
- `Llama-3.2-1B-Instruct`

### Groq
- `llama-3.3-70b-versatile` (default)
- `llama-3.1-8b-instant`
- `mixtral-8x7b-32768`
- `gemma2-9b-it`

## 💬 Usage Examples

### Simple Question

```php
$ai = new AIEngine($apiKey);
$answer = $ai->generateContent('What is the capital of France?');
echo $answer;
```

### Conversation with Memory

```php
$ai = AIEngine::create('groq', $groqKey);
$ai->setSystemInstruction("You are a math tutor. Be concise.");

echo $ai->chat("What is 15 + 27?");      // "42"
echo $ai->chat("Multiply that by 2");     // "84" - remembers previous answer!
```

### Switch Providers Mid-Session

```php
$ai = new AIEngine($geminiKey);
echo $ai->getProviderName();  // "Gemini"

$ai->switchProvider('groq', $groqKey);
echo $ai->getProviderName();  // "Groq"
```

### Error Handling

```php
$response = $ai->chat('Hello');

if (is_array($response) && isset($response['error'])) {
    echo "Error: " . $response['error'];
} else {
    echo "AI: " . $response;
}
```

## 🧪 Testing

### Interactive Test

```bash
php test.php
```

This launches an interactive chat where you can:
- Select a provider
- Chat with the AI
- Use `/new` to clear conversation
- Use `/quit` to exit

## 📁 Project Structure

```
ai-engine/
├── src/
│   ├── AIEngine.php              # Main engine class
│   └── Providers/
│       ├── ProviderInterface.php # Provider contract
│       ├── Gemini.php            # Google Gemini
│       ├── MetaLlama.php         # Meta Llama API
│       └── Groq.php              # Groq API
├── test.php                      # Interactive test
├── test.ai-key                   # API keys (JSON)
├── composer.json
└── README.md
```

## 🔧 Adding New Providers

1. Create a new class implementing `ProviderInterface`
2. Implement required methods: `generateContent()`, `sendMessage()`, `startNewConversation()`, `getConversationHistory()`, `setSystemInstruction()`, `isConfigured()`, `getName()`
3. Add the provider to `AIEngine::createProvider()`

## 🛡️ Error Handling

Common errors returned:

| Error | Cause |
|-------|-------|
| `Provider not properly configured` | Invalid or missing API key |
| `Invalid prompt` | Empty or too long prompt |
| `Error contacting API` | Network or API issue |

## 📄 License

MIT License - see [LICENSE](LICENSE) file.

## 🙏 Acknowledgments

- Google for Gemini API
- Meta for Llama API
- Groq for fast inference

---

**Made with ❤️ for the PHP community**

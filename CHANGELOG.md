# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2026-10-31

### Added
- **OpenAI** provider (`openai`, alias `gpt`) — GPT-6.1 Sol (default), GPT-6 Astra, GPT-6.1 Terra, GPT-6 Luna
- **Anthropic** provider (`anthropic`, alias `claude`) — Claude Opus 5.5 (default), Sonnet 5.5, Haiku 5.5, Fable 5.1
  - `setMaxTokens()` (default 16000) and `enableFallbacks()`; server-side refusal fallbacks enabled by default on supported models
- **xAI** provider (`xai`, alias `grok`) — Grok 4.7 (default) and Grok 4.x models
- **DeepSeek** provider (`deepseek`) — DeepSeek V4.1 Flash (default) and V4 Pro
- **Mistral** provider (`mistral`) — Mistral Large/Medium/Small, Ministral, Codestral
- `OpenAICompatibleProvider` abstract base class: add any OpenAI-compatible API in a few lines
- `DEFAULT_MODEL` constant on OpenAI-compatible providers and `Anthropic`
- README header banner, badges, and docs for all providers
- Tests for new providers and provider aliases (230 tests, 475 assertions)

### Changed
- **Gemini default model is now `gemini-3.8-flash`** — `gemini-2.0-flash` has been shut down by Google
- Gemini model list updated to Gemini 3.x and 2.5 models
- Groq model list updated (removed retired Mixtral, Gemma 2 and Llama 3.2 preview models; added GPT-OSS, Qwen and others)
- `Groq` and `MetaLlama` now extend `OpenAICompatibleProvider` (public API unchanged)
- `AIEngine` resolves providers through a single registry; unknown names still fall back to Gemini
- `ConfigValidator::isValidModel()` accepts `/` in model names (e.g. `openai/gpt-oss-120b`)
- `test.php` lists every provider with a configured key, each with an optional RAG mode

## [2.0.0] - 2026-03-18

### Added
- `Response` class with `getText()`, `getProvider()`, `getModel()`, `getRawResponse()`, and `__toString()`
- Exception hierarchy: `AIEngineException`, `ConfigurationException`, `ApiException`
- `ApiException` includes provider name and API error message for easier debugging
- RAG (Knowledge Base) support for **all providers** — Gemini and MetaLlama now support `setKnowledgeBase()`
- Knowledge base auto-syncs to new provider on `switchProvider()`
- PHPStan static analysis (level 5)
- PHP-CS-Fixer with PER-CS coding standard
- GitHub Actions CI pipeline — lint, test matrix (PHP 8.0–8.4), security audit, leaked secrets scan
- GitHub Actions release pipeline — triggered on version tags
- Composer scripts: `lint`, `ci`, `phpstan`, `cs-check`, `cs-fix`
- `declare(strict_types=1)` on all PHP files
- New test suites: `ResponseTest`, `ExceptionTest`, `ConfigValidatorTest`
- 154 tests with 302 assertions (up from ~90)

### Changed
- **BREAKING:** `generateContent()` and `sendMessage()` now return `Response` instead of `string|array`
- **BREAKING:** Errors now throw `ConfigurationException` or `ApiException` instead of returning `['error' => '...']` arrays
- **BREAKING:** `ProviderInterface` now requires `setKnowledgeBase()`, `hasKnowledgeBase()`, and `getModel()` methods
- Replaced `@file_get_contents()` with `set_error_handler()` for proper error capture
- Upgraded to PHPUnit ^10.0
- Typed properties and return types throughout the codebase
- Alphabetically ordered imports (enforced by PHP-CS-Fixer)

### Removed
- Hardcoded Groq API key from `test.ai-key`
- Redundant `is_string()` checks on typed string parameters

### Security
- Removed exposed API key from `test.ai-key`
- CI pipeline includes `composer audit` and secret scanning

## [1.0.0] - 2024-10-01

### Added
- Initial release
- Multi-provider support: Google Gemini, Meta Llama, Groq
- Conversation history with `sendMessage()` and `getConversationHistory()`
- System instruction support
- Knowledge Base (RAG) for Groq provider
- URL content fetcher with HTML text extraction
- Configuration validator utility
- PHPUnit test suite

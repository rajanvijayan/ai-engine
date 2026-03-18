# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

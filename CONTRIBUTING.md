# Contributing to AI Engine

First off, thank you for considering contributing to AI Engine! It's people like you that make AI Engine such a great tool.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [How to Contribute](#how-to-contribute)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Documentation](#documentation)

## Code of Conduct

This project and everyone participating in it is governed by our commitment to creating a welcoming and inclusive environment. Please be respectful and constructive in all interactions.

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally
3. Set up the development environment
4. Create a branch for your changes
5. Make your changes
6. Push to your fork and submit a pull request

## Development Setup

### Prerequisites

- PHP 8.0 or higher
- Composer
- Git

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/ai-engine.git
cd ai-engine

# Install dependencies
composer install

# Run tests to make sure everything is working
composer test
```

### API Keys for Testing

Create a `test.ai-key` file in the project root with your API keys:

```json
{
    "gemini": "YOUR_GEMINI_API_KEY",
    "meta": "YOUR_META_LLAMA_API_KEY",
    "groq": "YOUR_GROQ_API_KEY"
}
```

> **Note:** Never commit your API keys to the repository!

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When creating a bug report, include:

- A clear and descriptive title
- Steps to reproduce the issue
- Expected behavior
- Actual behavior
- PHP version and environment details
- Any relevant code snippets or error messages

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, include:

- A clear and descriptive title
- Detailed description of the proposed functionality
- Explanation of why this enhancement would be useful
- Examples of how it would be used

### Adding New Providers

To add a new AI provider:

1. Create a new class in `src/Providers/` implementing `ProviderInterface`
2. Implement all required methods:
   - `generateContent(string $prompt): string|array`
   - `sendMessage(string $message): string|array`
   - `startNewConversation(): void`
   - `getConversationHistory(): array`
   - `setSystemInstruction(string $instruction): void`
   - `isConfigured(): bool`
   - `getName(): string`
3. Add the provider to `AIEngine::createProvider()`
4. Add comprehensive tests in `tests/Providers/`
5. Update documentation

## Pull Request Process

1. **Update the CHANGELOG.md** with details of your changes under the `[Unreleased]` section
2. **Ensure all tests pass** by running `composer test`
3. **Follow coding standards** - run code style checks
4. **Update documentation** if you're changing functionality
5. **Write meaningful commit messages** following conventional commits
6. **Keep PRs focused** - one feature or fix per PR

### PR Checklist

- [ ] I have updated the CHANGELOG.md
- [ ] I have added/updated tests for my changes
- [ ] All tests pass locally
- [ ] I have followed the coding standards
- [ ] I have updated relevant documentation
- [ ] My commits have meaningful messages

## Coding Standards

We follow PSR-12 coding standards. Key points:

- Use 4 spaces for indentation (no tabs)
- Opening braces on the same line for classes and methods
- One blank line before return statements
- Type declarations for parameters and return types
- Meaningful variable and method names

### Code Style Check

```bash
# Check coding standards (if PHP_CodeSniffer is installed)
./vendor/bin/phpcs --standard=PSR12 src/ tests/

# Fix automatically fixable issues
./vendor/bin/phpcbf --standard=PSR12 src/ tests/
```

## Testing

All new features and bug fixes must include tests.

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
./vendor/bin/phpunit tests/AIEngineTest.php

# Run tests with coverage
composer test-coverage
```

### Writing Tests

- Place tests in the `tests/` directory
- Follow the same directory structure as `src/`
- Name test files with `Test` suffix (e.g., `AIEngineTest.php`)
- Name test methods starting with `test` or use `@test` annotation
- Test both success and failure cases
- Use meaningful test method names that describe what is being tested

Example:

```php
public function testGenerateContentReturnsStringResponse(): void
{
    $ai = new AIEngine($this->apiKey);
    $response = $ai->generateContent('Hello');
    
    $this->assertIsString($response);
}
```

## Documentation

- Update the README.md for any user-facing changes
- Add PHPDoc comments to all public methods
- Include examples for new features
- Keep documentation up to date with code changes

### PHPDoc Format

```php
/**
 * Generate content from a single prompt.
 *
 * @param string $prompt The prompt to send to the AI
 * @return string|array The AI response or error array
 * @throws InvalidArgumentException If prompt is empty
 */
public function generateContent(string $prompt): string|array
{
    // ...
}
```

## Questions?

If you have questions, feel free to:

- Open a GitHub issue with the `question` label
- Check existing issues and documentation

Thank you for contributing!


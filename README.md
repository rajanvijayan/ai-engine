# AI Engine

AI Engine is a wrapper for AI tools like Gemini and ChatGPT, making it easy to integrate multiple AI services into your PHP application.

## Installation

```bash
composer require rajanvijayan/ai-engine
```

## Usage

```php
<?php

use AIEngine\AIEngine;

$ai = new AIEngine( 'your-gemini-api-key' );
$response = $ai->generateContent('What is AI?');

echo $response;
```


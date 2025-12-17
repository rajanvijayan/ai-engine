<?php
/**
 * Interactive AI Engine Test
 * 
 * Simple interactive test for chatting with AI providers
 */

require_once 'vendor/autoload.php';

use AIEngine\AIEngine;

// Load API keys from JSON file
function loadKeys() {
    $path = __DIR__ . '/test.ai-key';
    if (!file_exists($path)) {
        return [];
    }
    return json_decode(file_get_contents($path), true) ?: [];
}

function isValidKey($key) {
    return $key && strlen($key) > 10 && !str_starts_with($key, 'YOUR_');
}

function clearScreen() {
    echo "\033[2J\033[H";
}

function prompt($message) {
    echo $message;
    return trim(fgets(STDIN));
}

// Load keys
$keys = loadKeys();
$availableProviders = [];

if (isValidKey($keys['gemini'] ?? null)) {
    $availableProviders['1'] = ['name' => 'Gemini', 'key' => 'gemini'];
}
if (isValidKey($keys['meta'] ?? null)) {
    $availableProviders['2'] = ['name' => 'Meta Llama', 'key' => 'meta'];
}
if (isValidKey($keys['groq'] ?? null)) {
    $availableProviders['3'] = ['name' => 'Groq (Llama)', 'key' => 'groq'];
}

clearScreen();
echo "╔════════════════════════════════════════╗\n";
echo "║       AI Engine Interactive Test       ║\n";
echo "╚════════════════════════════════════════╝\n\n";

if (empty($availableProviders)) {
    echo "❌ No API keys configured!\n\n";
    echo "Please edit test.ai-key with your API keys:\n";
    echo "{\n";
    echo "    \"gemini\": \"your-key\",\n";
    echo "    \"meta\": \"your-key\",\n";
    echo "    \"groq\": \"your-key\"\n";
    echo "}\n\n";
    echo "Get keys from:\n";
    echo "  - Gemini: https://makersuite.google.com/app/apikey\n";
    echo "  - Meta:   https://llama.meta.com\n";
    echo "  - Groq:   https://console.groq.com (FREE!)\n";
    exit(1);
}

// Select provider
echo "Available Providers:\n";
foreach ($availableProviders as $num => $provider) {
    echo "  [{$num}] {$provider['name']}\n";
}
echo "\n";

$choice = prompt("Select provider (number): ");

if (!isset($availableProviders[$choice])) {
    echo "Invalid choice. Exiting.\n";
    exit(1);
}

$selectedProvider = $availableProviders[$choice];
$providerKey = $selectedProvider['key'];
$apiKey = $keys[$providerKey];

// Create AI Engine
echo "\n🔄 Connecting to {$selectedProvider['name']}...\n";
$ai = AIEngine::create($providerKey, $apiKey);
$ai->setSystemInstruction("You are a helpful assistant. Keep responses concise.");

echo "✅ Connected to {$ai->getProviderName()}\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Type your messages. Commands:\n";
echo "  /new   - Start new conversation\n";
echo "  /quit  - Exit\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Chat loop
while (true) {
    $input = prompt("You: ");
    
    if (empty($input)) {
        continue;
    }
    
    // Handle commands
    if ($input === '/quit' || $input === '/exit' || $input === '/q') {
        echo "\nGoodbye! 👋\n";
        break;
    }
    
    if ($input === '/new' || $input === '/clear') {
        $ai->newConversation();
        echo "\n🔄 Conversation cleared!\n\n";
        continue;
    }
    
    if ($input === '/history') {
        $history = $ai->getHistory();
        echo "\n📜 Conversation History (" . count($history) . " messages):\n";
        foreach ($history as $i => $msg) {
            $role = $msg['role'] ?? $msg['role'];
            $text = substr($msg['content'] ?? $msg['parts'][0]['text'], 0, 60);
            echo "  " . ($i + 1) . ". [{$role}]: {$text}...\n";
        }
        echo "\n";
        continue;
    }
    
    // Send message
    echo "\n";
    $response = $ai->chat($input);
    
    if (is_array($response) && isset($response['error'])) {
        echo "❌ Error: " . $response['error'] . "\n\n";
    } else {
        echo "AI: " . $response . "\n\n";
    }
}


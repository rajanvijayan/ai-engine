<?php
/**
 * Interactive AI Engine Test
 * 
 * Simple interactive test for chatting with AI providers
 * Supports Knowledge Base (RAG) for Groq provider
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
    $availableProviders['4'] = ['name' => 'Groq + Knowledge Base (RAG)', 'key' => 'groq', 'rag' => true];
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
echo "Available Modes:\n";
foreach ($availableProviders as $num => $provider) {
    echo "  [{$num}] {$provider['name']}\n";
}
echo "\n";

$choice = prompt("Select mode (number): ");

if (!isset($availableProviders[$choice])) {
    echo "Invalid choice. Exiting.\n";
    exit(1);
}

$selectedProvider = $availableProviders[$choice];
$providerKey = $selectedProvider['key'];
$apiKey = $keys[$providerKey];
$useRag = $selectedProvider['rag'] ?? false;

// Create AI Engine
echo "\n🔄 Connecting to {$selectedProvider['name']}...\n";
$ai = AIEngine::create($providerKey, $apiKey);

// RAG Mode: Add knowledge from URLs
if ($useRag) {
    echo "\n📚 Knowledge Base Mode\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Add URLs to train the AI with your content.\n";
    echo "Enter URLs one per line, then type 'done' when finished.\n\n";
    
    $urlCount = 0;
    while (true) {
        $url = prompt("URL (or 'done'): ");
        
        if (strtolower($url) === 'done' || empty($url)) {
            break;
        }
        
        echo "  Fetching... ";
        $result = $ai->addKnowledgeFromUrl($url);
        
        if ($result['success']) {
            $title = $result['title'] ?? 'Untitled';
            echo "✅ Added: {$title}\n";
            $urlCount++;
        } else {
            echo "❌ Failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
    }
    
    if ($urlCount === 0) {
        echo "\n⚠️  No URLs added. Continuing without knowledge base.\n";
    } else {
        $summary = $ai->getKnowledgeSummary();
        echo "\n📊 Knowledge Base Summary:\n";
        echo "   Documents: {$summary['count']}\n";
        echo "   Total chars: " . number_format($summary['totalChars']) . "\n\n";
    }
    
    $ai->setSystemInstruction("You are a helpful assistant. Answer questions based on the provided knowledge base. If the information is not in the knowledge base, say so clearly.");
} else {
    $ai->setSystemInstruction("You are a helpful assistant. Keep responses concise.");
}

echo "✅ Connected to {$ai->getProviderName()}";
if ($useRag && $ai->hasKnowledge()) {
    echo " with Knowledge Base";
}
echo "\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Type your messages. Commands:\n";
echo "  /new      - Start new conversation\n";
echo "  /history  - Show conversation history\n";
if ($useRag) {
    echo "  /kb       - Show knowledge base summary\n";
    echo "  /add      - Add more URLs to knowledge base\n";
    echo "  /clear-kb - Clear knowledge base\n";
}
echo "  /quit     - Exit\n";
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
            $role = $msg['role'] ?? 'unknown';
            $content = $msg['content'] ?? ($msg['parts'][0]['text'] ?? '');
            $text = substr($content, 0, 60);
            if (strlen($content) > 60) $text .= '...';
            echo "  " . ($i + 1) . ". [{$role}]: {$text}\n";
        }
        echo "\n";
        continue;
    }
    
    if ($useRag && $input === '/kb') {
        $summary = $ai->getKnowledgeSummary();
        echo "\n📚 Knowledge Base:\n";
        echo "   Documents: {$summary['count']}\n";
        echo "   Total chars: " . number_format($summary['totalChars']) . "\n";
        if (!empty($summary['sources'])) {
            echo "   Sources:\n";
            foreach ($summary['sources'] as $source) {
                $title = $source['title'] ?? 'Untitled';
                echo "     - {$title} ({$source['source']})\n";
            }
        }
        echo "\n";
        continue;
    }
    
    if ($useRag && $input === '/add') {
        echo "\nAdd more URLs (type 'done' when finished):\n";
        while (true) {
            $url = prompt("URL: ");
            if (strtolower($url) === 'done' || empty($url)) {
                break;
            }
            echo "  Fetching... ";
            $result = $ai->addKnowledgeFromUrl($url);
            if ($result['success']) {
                echo "✅ Added: " . ($result['title'] ?? 'Untitled') . "\n";
            } else {
                echo "❌ Failed: " . ($result['error'] ?? 'Unknown error') . "\n";
            }
        }
        echo "\n";
        continue;
    }
    
    if ($useRag && $input === '/clear-kb') {
        $ai->clearKnowledge();
        echo "\n🗑️  Knowledge base cleared!\n\n";
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

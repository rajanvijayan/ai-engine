<?php
namespace AIEngine\Providers;

class Gemini {

    protected $api_key;

    /**
     * Constructor.
     *
     * @param string $api_key The API key for authentication.
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Extract text from the Gemini API response.
     *
     * @param string $json The JSON response from the API.
     * @return string|null The extracted text or null if not found.
     */
    public function getTextFromParts($json) {
        $data = json_decode($json, true);

        if (isset($data['candidates']) && is_array($data['candidates'])) {
            foreach ($data['candidates'] as $candidate) {
                if (isset($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
                    foreach ($candidate['content']['parts'] as $part) {
                        if (isset($part['text'])) {
                            return $part['text'];
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Fetch data from the Gemini API.
     *
     * @param string $prompt The prompt to send to the API.
     * @return array|string The response data or an error message.
     */
    public function generateContent($prompt) {
        // Sanitize the prompt by removing harmful characters
        $prompt = htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8');
    
        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$this->api_key}";
    
        // Prepare the data for the API request
        $data = array(
            'contents' => array(
                'parts' => array(
                    array(
                        'text' => $prompt
                    )
                )
            )
        );
    
        $options = array(
            'http' => array(
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 60
            )
        );
    
        $context  = stream_context_create($options);
        $response = @file_get_contents($api_url, false, $context);
    
        if ($response === FALSE) {
            return ['error' => 'Error contacting API'];
        }
    
        return $this->getTextFromParts($response);
    }

}
<?php
namespace AIEngine\Providers;

class Gemini implements ProviderInterface {

    protected $api_key;
    protected $model;
    protected $timeout;

    /**
     * Constructor.
     *
     * @param string $api_key The API key for authentication.
     * @param string $model The Gemini model to use (default: gemini-2.0-flash).
     * @param int $timeout Request timeout in seconds (default: 60).
     */
    public function __construct($api_key, $model = 'gemini-2.0-flash', $timeout = 60) {
        $this->api_key = $api_key;
        $this->model = $model;
        $this->timeout = $timeout;
    }

    /**
     * Validate the configuration (API key, etc.).
     *
     * @return bool True if configuration is valid
     */
    public function isConfigured() {
        return !empty($this->api_key) && is_string($this->api_key);
    }

    /**
     * Get the provider name.
     *
     * @return string The provider name
     */
    public function getName() {
        return 'Gemini';
    }

    /**
     * Get the current model being used.
     *
     * @return string The current model name
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * Set the model to use.
     *
     * @param string $model The model name
     */
    public function setModel($model) {
        $this->model = $model;
    }

    /**
     * Set the request timeout.
     *
     * @param int $timeout Timeout in seconds
     */
    public function setTimeout($timeout) {
        $this->timeout = $timeout;
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
     * Validate the prompt input.
     *
     * @param string $prompt The prompt to validate
     * @return bool True if prompt is valid
     */
    protected function validatePrompt($prompt) {
        return is_string($prompt) && !empty(trim($prompt)) && strlen($prompt) <= 30000;
    }

    /**
     * Fetch data from the Gemini API using the new API format.
     *
     * @param string $prompt The prompt to send to the API.
     * @return array|string The response data or an error message.
     */
    public function generateContent($prompt) {
        // Validate configuration
        if (!$this->isConfigured()) {
            return ['error' => 'Provider not properly configured'];
        }

        // Validate prompt
        if (!$this->validatePrompt($prompt)) {
            return ['error' => 'Invalid prompt: must be a non-empty string under 30000 characters'];
        }

        // Sanitize the prompt by removing harmful characters
        $prompt = htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8');
    
        // Updated API URL without query parameter
        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    
        // Prepare the data for the API request with updated structure
        $data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt
                        )
                    )
                )
            )
        );
    
        // Updated options with X-goog-api-key header
        $options = array(
            'http' => array(
                'header'  => "Content-Type: application/json\r\n" . 
                           "X-goog-api-key: {$this->api_key}\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => $this->timeout
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
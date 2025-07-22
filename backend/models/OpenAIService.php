<?php
require_once __DIR__ . '/../vendor/autoload.php';

use OpenAI\Client;

/**
 * OpenAI Service
 * 
 * This class handles communication with the OpenAI API
 */
class OpenAIService {
    private $client;

    public function __construct() {
        // Initialize OpenAI client with API key from environment
        $apiKey = $_ENV['OPENAI_API_KEY'];
        
        if (empty($apiKey)) {
            throw new Exception('OpenAI API key not configured');
        }
        
        $this->client = \OpenAI::client($apiKey);
    }

    /**
     * Send a chat completion request to OpenAI
     * 
     * @param array $messages Array of messages in OpenAI format
     * @param string $model The model to use (default: gpt-3.5-turbo)
     * @return array|null Response from OpenAI or null on error
     */
    public function sendChatRequest($messages, $model = 'gpt-3.5-turbo') {
        try {
            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            return [
                'success' => true,
                'message' => $response->choices[0]->message->content,
                'usage' => [
                    'prompt_tokens' => $response->usage->promptTokens,
                    'completion_tokens' => $response->usage->completionTokens,
                    'total_tokens' => $response->usage->totalTokens
                ]
            ];

        } catch (Exception $e) {
            error_log("OpenAI API error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to get response from AI service'
            ];
        }
    }

    /**
     * Convert chat history to OpenAI message format
     * 
     * @param array $messages Database messages
     * @return array OpenAI formatted messages
     */
    public function formatMessagesForOpenAI($messages) {
        $formatted = [];
        
        // Add system message
        $formatted[] = [
            'role' => 'system',
            'content' => 'You are a helpful assistant. Be friendly and informative in your responses.'
        ];

        // Add conversation history
        foreach ($messages as $message) {
            $formatted[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }

        return $formatted;
    }

    /**
     * Check if OpenAI API is properly configured
     * 
     * @return bool
     */
    public function isConfigured() {
        return !empty($_ENV['OPENAI_API_KEY']) && $_ENV['OPENAI_API_KEY'] !== 'your-openai-api-key-here';
    }

    /**
     * Get a simple test response from OpenAI
     * 
     * @return array
     */
    public function testConnection() {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'OpenAI API key not configured'
            ];
        }

        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => 'Say hello!']
                ],
                'max_tokens' => 50
            ]);

            return [
                'success' => true,
                'message' => $response->choices[0]->message->content
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>

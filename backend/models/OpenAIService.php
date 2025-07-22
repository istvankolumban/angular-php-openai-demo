<?php
require_once __DIR__ . '/../vendor/autoload.php';

use OpenAI\Client;

/**
 * OpenAI Service
 * 
 * This class handles communication with the OpenAI Assistants API
 */
class OpenAIService {
    private $client;
    private $assistantId;

    public function __construct() {
        // Initialize OpenAI client with API key from environment
        $apiKey = $_ENV['OPENAI_API_KEY'];
        
        if (empty($apiKey)) {
            throw new Exception('OpenAI API key not configured');
        }
        
        $this->client = \OpenAI::client($apiKey);
        $this->assistantId = $_ENV['OPENAI_ASSISTANT_ID'] ?? 'asst_6Wx8MRI5fMbK0Y9dfmlpw80c';
    }

    /**
     * Send a message to the OpenAI Assistant
     * 
     * @param string $message The user's message
     * @param string $threadId Optional existing thread ID for conversation continuity
     * @return array Response with assistant's reply and thread information
     */
    public function sendMessageToAssistant($message, $threadId = null) {
        try {
            // Create or use existing thread
            if ($threadId === null) {
                $thread = $this->client->threads()->create([]);
                $threadId = $thread->id;
            }

            // Add user message to thread
            $this->client->threads()->messages()->create($threadId, [
                'role' => 'user',
                'content' => $message,
            ]);

            // Run the assistant
            $run = $this->client->threads()->runs()->create($threadId, [
                'assistant_id' => $this->assistantId,
            ]);

            // Wait for completion (with timeout)
            $timeout = 30; // 30 seconds timeout
            $start = time();
            
            do {
                sleep(1); // Wait 1 second between checks
                $run = $this->client->threads()->runs()->retrieve($threadId, $run->id);
                
                if (time() - $start > $timeout) {
                    throw new Exception('Assistant response timeout');
                }
            } while ($run->status === 'queued' || $run->status === 'in_progress');

            if ($run->status === 'completed') {
                // Get the assistant's response
                $messages = $this->client->threads()->messages()->list($threadId, [
                    'limit' => 1,
                ]);

                $assistantMessage = $messages->data[0];
                $content = $assistantMessage->content[0]->text->value;

                return [
                    'success' => true,
                    'message' => $content,
                    'thread_id' => $threadId,
                    'run_id' => $run->id
                ];
            } else {
                throw new Exception('Assistant run failed with status: ' . $run->status);
            }

        } catch (Exception $e) {
            error_log("OpenAI Assistant error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to get response from AI assistant: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get the assistant information
     * 
     * @return array
     */
    public function getAssistantInfo() {
        try {
            $assistant = $this->client->assistants()->retrieve($this->assistantId);
            return [
                'success' => true,
                'assistant' => [
                    'id' => $assistant->id,
                    'name' => $assistant->name,
                    'description' => $assistant->description,
                    'model' => $assistant->model
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to retrieve assistant info: ' . $e->getMessage()
            ];
        }
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
     * Get a simple test response from the OpenAI Assistant
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
            // First try to get assistant info
            $assistantInfo = $this->getAssistantInfo();
            if (!$assistantInfo['success']) {
                return $assistantInfo;
            }

            // Test with a simple message
            $result = $this->sendMessageToAssistant('Hello! Please respond with a brief greeting.');
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => $result['message'],
                    'assistant_info' => $assistantInfo['assistant']
                ];
            } else {
                return $result;
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>

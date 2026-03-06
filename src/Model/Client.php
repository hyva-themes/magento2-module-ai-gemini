<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\AiGemini\Model;

use Hyva\Ai\Api\ProviderConfigInterface;
use Hyva\Ai\Model\ConcurrencyGuard;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Encryption\EncryptorInterface;

class Client
{
    private const DEFAULT_API_URL = 'https://generativelanguage.googleapis.com/v1/models/%s:generateContent';
    private const CONFIG_PATH_API_KEY = 'hyva_ai/gemini/api_key';

    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private Curl $curl,
        private Json $json,
        private EncryptorInterface $encryptor,
        private ProviderConfigInterface $providerConfig,
        private string $apiUrl = self::DEFAULT_API_URL
    ) {
    }

    /**
     * Make a generate content request to Gemini API
     */
    public function generateContent(
        array $messages,
        ?string $model = null,
        ?float $temperature = null,
        ?int $maxTokens = null
    ): array {
        $model = $model ?? $this->providerConfig->getDefaultModel();
        $temperature = $temperature ?? $this->providerConfig->getDefaultTemperature();
        $maxTokens = $maxTokens ?? $this->providerConfig->getDefaultMaxTokens();
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            throw new LocalizedException(__('Gemini API key is not configured.'));
        }

        $url = sprintf($this->apiUrl, $model) . '?key=' . $apiKey;

        $this->curl->addHeader('Content-Type', 'application/json');

        $timeout = (int) ($this->scopeConfig->getValue(
            ConcurrencyGuard::XML_PATH_REQUEST_TIMEOUT_SECONDS
        ) ?? 0);
        if ($timeout > 0) {
            $this->curl->setTimeout($timeout);
        }

        // Convert OpenAI-style messages to Gemini format
        $contents = $this->convertMessagesToGeminiFormat($messages);

        $requestData = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens
            ]
        ];

        try {
            $this->curl->post($url, $this->json->serialize($requestData));
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'timed out') !== false || stripos($msg, 'timeout') !== false) {
                $timeoutMessage = $timeout > 0
                    ? __('The request to the AI provider timed out after %1 seconds. Try again or increase the timeout in Stores > Configuration > Hyvä AI > Runtime.', $timeout)
                    : __('The request to the AI provider timed out. Try again or set a timeout in Stores > Configuration > Hyvä AI > Runtime.');
                throw new LocalizedException($timeoutMessage);
            }
            throw new LocalizedException(__('Gemini API request failed: %1', $msg), $e);
        }

        $responseBody = $this->curl->getBody();
        $httpStatus = $this->curl->getStatus();

        if ($httpStatus !== 200) {
            $errorMessage = $this->parseErrorMessage($responseBody, $httpStatus);
            throw new LocalizedException(__('Gemini API request failed: %1', $errorMessage));
        }

        $response = $this->json->unserialize($responseBody);

        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            throw new LocalizedException(__('Invalid response from Gemini API'));
        }

        return $response;
    }

    /**
     * Get the content from the first candidate in a generate content response
     */
    public function extractContent(array $response): string
    {
        return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    /**
     * Check if Gemini API key is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->getApiKey());
    }

    /**
     * Get Gemini API key from configuration
     */
    private function getApiKey(): ?string
    {
        $encryptedKey = $this->scopeConfig->getValue(self::CONFIG_PATH_API_KEY);
        if (!$encryptedKey) {
            return null;
        }

        return $this->encryptor->decrypt($encryptedKey);
    }

    /**
     * Convert OpenAI-style messages to Gemini format
     */
    private function convertMessagesToGeminiFormat(array $messages): array
    {
        $contents = [];

        foreach ($messages as $message) {
            $role = $message['role'] === 'system' ? 'user' : $message['role'];
            $role = $role === 'assistant' ? 'model' : $role;

            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $message['content']]
                ]
            ];
        }

        return $contents;
    }

    /**
     * Parse error message from API response
     */
    private function parseErrorMessage(string $responseBody, int $httpStatus): string
    {
        try {
            $errorData = $this->json->unserialize($responseBody);

            // Gemini error format: {"error": {"message": "...", "status": "...", "code": 400}}
            if (isset($errorData['error']['message'])) {
                return $errorData['error']['message'];
            }

            if (isset($errorData['error']) && is_string($errorData['error'])) {
                return $errorData['error'];
            }
        } catch (\Exception $e) {
            // If we can't parse the error, fall through to default message
        }

        return match ($httpStatus) {
            400 => 'Bad request. Please check your input parameters.',
            401 => 'Authentication failed. Please check your API key.',
            403 => 'Access forbidden. Please check your API key permissions.',
            429 => 'Too many requests. Please try again later.',
            500 => 'Gemini service error. Please try again later.',
            503 => 'Service temporarily unavailable. Please try again later.',
            default => "HTTP {$httpStatus}: Unable to process request",
        };
    }
}

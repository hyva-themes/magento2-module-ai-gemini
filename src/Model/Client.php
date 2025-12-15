<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */
declare(strict_types=1);

namespace Hyva\AiGemini\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Encryption\EncryptorInterface;
use Psr\Log\LoggerInterface;

class Client
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1/models/%s:generateContent';
    private const DEFAULT_MODEL = 'gemini-1.5-flash';
    private const CONFIG_PATH_API_KEY = 'hyva_ai/gemini/api_key';

    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private Curl $curl,
        private Json $json,
        private EncryptorInterface $encryptor,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Make a generate content request to Gemini API
     */
    public function generateContent(
        array $messages,
        string $model = self::DEFAULT_MODEL,
        float $temperature = 0.7,
        int $maxTokens = 4000
    ): array {
        $apiKey = $this->getApiKey();
        if (!$apiKey) {
            throw new LocalizedException(__('Gemini API key is not configured.'));
        }

        $url = sprintf(self::GEMINI_API_URL, $model) . '?key=' . $apiKey;

        $this->curl->addHeader('Content-Type', 'application/json');

        // Convert OpenAI-style messages to Gemini format
        $contents = $this->convertMessagesToGeminiFormat($messages);

        $requestData = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens
            ]
        ];

        $this->curl->post($url, $this->json->serialize($requestData));

        $responseBody = $this->curl->getBody();
        $httpStatus = $this->curl->getStatus();

        if ($httpStatus !== 200) {
            throw new LocalizedException(__('Gemini API request failed with status %1: %2', $httpStatus, $responseBody));
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
}

<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */
declare(strict_types=1);

namespace Hyva\AiGemini\Model\Provider;

use Hyva\Ai\Api\ProviderInterface;
use Hyva\AiGemini\Model\Client;
use Magento\Framework\Exception\LocalizedException;

class GeminiProvider implements ProviderInterface
{
    public function __construct(
        private readonly Client $client
    ) {
    }

    public function process(array $data, array $options = []): array
    {
        $allMessages = $data['messages'] ?? [];
        if (empty($allMessages)) {
            throw new LocalizedException(__('Messages are required for Gemini processing.'));
        }

        $model = $options['model'] ?? 'gemini-1.5-flash';
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 4000;

        $responses = [];

        foreach ($allMessages as $batch) {
            $messages = $batch['messages'] ?? [];

            if (empty($messages)) {
                $responses[] = '';
                continue;
            }

            try {
                $response = $this->client->generateContent($messages, $model, $temperature, $maxTokens);
                $content = $this->client->extractContent($response);
                $responses[] = $content;
            } catch (\Exception $e) {
                $responses[] = '';
            }
        }

        return ['responses' => $responses];
    }

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    public function getName(): string
    {
        return 'gemini';
    }
}

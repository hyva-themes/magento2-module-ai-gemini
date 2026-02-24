<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\AiGemini\Model\Provider;

use Hyva\Ai\Api\ProviderConfigInterface;
use Hyva\Ai\Model\Provider\AbstractOptionsSchema;

/**
 * Option schema for Gemini provider
 */
class OptionsSchema extends AbstractOptionsSchema
{
    public function __construct(
        private readonly ProviderConfigInterface $providerConfig
    ) {
    }

    public function getProviderId(): string
    {
        return $this->providerConfig->getProviderName();
    }

    public function getFields(): array
    {
        return [
            [
                'id' => 'temperature',
                'type' => 'number',
                'label' => __('Temperature'),
                'comment' => __('Controls randomness for translations (0.0 - 2.0, recommended: %1)', $this->providerConfig->getDefaultTemperature()),
                'default' => (string) $this->providerConfig->getDefaultTemperature(),
                'validate' => 'validate-number validate-number-range number-range-0-2',
                'required' => false
            ],
            [
                'id' => 'max_tokens',
                'type' => 'number',
                'label' => __('Max Tokens'),
                'comment' => __('Maximum tokens for translation responses'),
                'default' => $this->providerConfig->getDefaultMaxTokens(),
                'validate' => 'validate-digits validate-digits-range digits-range-1-8000',
                'required' => false
            ]
        ];
    }
}

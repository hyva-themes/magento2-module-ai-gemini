<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Hyva\AiGemini\Model;

use Hyva\Ai\Api\ProviderConfigInterface;

class ProviderConfig implements ProviderConfigInterface
{
    public const PROVIDER_NAME = 'gemini';
    public const DEFAULT_MODEL = 'gemini-2.5-flash';
    public const DEFAULT_TEMPERATURE = 0.3;
    public const DEFAULT_MAX_TOKENS = 4000;

    public function getProviderName(): string
    {
        return self::PROVIDER_NAME;
    }

    public function getDefaultModel(): string
    {
        return self::DEFAULT_MODEL;
    }

    public function getDefaultTemperature(): float
    {
        return self::DEFAULT_TEMPERATURE;
    }

    public function getDefaultMaxTokens(): int
    {
        return self::DEFAULT_MAX_TOKENS;
    }
}

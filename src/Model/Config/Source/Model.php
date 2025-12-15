<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */
declare(strict_types=1);

namespace Hyva\AiGemini\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Model implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'gemini-2.0-flash', 'label' => 'Gemini 2.0 Flash'],
            ['value' => 'gemini-2.0-flash-lite', 'label' => 'Gemini 2.0 Flash Lite'],
            ['value' => 'gemini-2.5-flash', 'label' => 'Gemini 2.5 Flash'],
            ['value' => 'gemini-2.5-flash-lite', 'label' => 'Gemini 2.5 Flash Lite'],
        ];
    }
}

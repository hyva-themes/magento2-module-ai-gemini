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
     * @param array $options
     */
    public function __construct(
        private readonly array $options
    ) {
    }

    /**
     * Return array of options as value-label pairs
     */
    public function toOptionArray(): array
    {
        return $this->options;
    }
}

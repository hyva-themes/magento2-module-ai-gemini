<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
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

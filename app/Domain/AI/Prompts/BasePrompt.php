<?php

declare(strict_types=1);

namespace App\Domain\AI\Prompts;

abstract class BasePrompt
{
    abstract public function render(array $context = []): string;
}

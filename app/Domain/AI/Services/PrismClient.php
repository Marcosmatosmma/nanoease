<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use Illuminate\Support\Collection;
use Prism\Prism\Facades\PrismServer;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Text\Response;

final class PrismClient
{
    public function __construct(
        private readonly string $defaultPrismName = 'Larasonic Medium',
    ) {}

    /**
     * @throws \RuntimeException
     */
    public function ask(string $prompt, ?string $prismName = null): string
    {
        $slot = $this->resolvePrism($prismName ?? $this->defaultPrismName);

        /** @var PendingRequest $pending */
        $pending = ($slot['prism'])();

        /** @var Response|null $response */
        $response = $pending->withPrompt($prompt)->asText();

        if (! $response) {
            throw new \RuntimeException('Prism não retornou resposta.');
        }

        return $response->text;
    }

    /**
     * @return array{name:string,prism:callable}
     * @throws \RuntimeException
     */
    private function resolvePrism(string $name): array
    {
        /** @var Collection<int,array{name:string,prism:callable}> $prisms */
        $prisms = PrismServer::prisms();

        $slot = $prisms->firstWhere('name', $name) ?? $prisms->first();

        if (! $slot) {
            throw new \RuntimeException('Nenhum Prism configurado.');
        }

        return $slot;
    }
}

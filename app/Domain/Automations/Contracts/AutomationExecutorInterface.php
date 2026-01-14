<?php

declare(strict_types=1);

namespace App\Domain\Automations\Contracts;

use App\Domain\Automations\Models\Automation;

interface AutomationExecutorInterface
{
    /**
     * Executa uma automação baseado no evento recebido.
     *
     * @param Automation $automation
     * @param array $event Evento normalizado
     * @return array Resultado da execução
     */
    public function execute(Automation $automation, array $event): array;

    /**
     * Verifica se este executor pode processar o tipo de automação.
     *
     * @param string $eventType
     * @return bool
     */
    public function supports(string $eventType): bool;
}

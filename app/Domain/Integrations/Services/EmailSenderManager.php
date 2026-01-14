<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Services;

use App\Domain\Automations\Models\Automation;
use Illuminate\Support\Arr;

final class EmailSenderManager
{
    public function __construct(
        private readonly GmailEmailSender $gmailSender,
        private readonly DefaultEmailSender $defaultSender,
    ) {
    }

    public function send(Automation $automation, string $from, array $recipients, string $subject, string $body): array
    {
        $provider = Arr::get($automation->integration?->toArray() ?? [], 'provider');

        if ($provider === 'gmail') {
            return $this->gmailSender->send($automation->integration, $from, $recipients, $subject, $body);
        }

        return $this->defaultSender->send($from, $recipients, $subject, $body);
    }
}

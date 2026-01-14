<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Services;

use Illuminate\Support\Facades\Mail;

final class DefaultEmailSender
{
    public function send(string $from, array $recipients, string $subject, string $body): array
    {
        foreach ($recipients as $recipient) {
            Mail::raw($body, function ($message) use ($recipient, $from, $subject) {
                $message
                    ->to($recipient)
                    ->from($from)
                    ->subject($subject)
                    ->replyTo($from);
            });
        }

        return [
            'status' => 'executed',
            'message' => sprintf('Encaminhado para %d destinatário(s) via mailer padrão.', count($recipients)),
            'provider' => 'default',
        ];
    }
}

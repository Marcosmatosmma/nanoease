<?php

namespace App\Jobs;

use App\Domain\Automations\Models\MassEmailSend;
use App\Domain\Automations\Models\MassEmailRecipient;
use App\Domain\Automations\Services\MassEmailSenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job responsável por processar envio em massa
 * Processa destinatários em lote e envia emails
 */
class ProcessMassEmailSendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Tempo máximo de execução: 10 minutos
     */
    public $timeout = 600;

    /**
     * Número de tentativas
     */
    public $tries = 3;

    /**
     * Constructor
     * 
     * @param int $massEmailSendId ID do envio em massa
     */
    public function __construct(
        public int $massEmailSendId
    ) {}

    /**
     * Executa o job
     * Processa todos os destinatários pendentes
     */
    public function handle(MassEmailSenderService $senderService): void
    {
        $massEmailSend = MassEmailSend::with('integration')->find($this->massEmailSendId);

        if (!$massEmailSend) {
            return;
        }

        // Atualiza status para processando
        $massEmailSend->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        // Busca destinatários pendentes
        $recipients = $massEmailSend->recipients()
            ->where('status', 'pending')
            ->get();

        $sentCount = 0;
        $failedCount = 0;

        foreach ($recipients as $recipient) {
            // Envia email
            $result = $senderService->send(
                integration: $massEmailSend->integration,
                recipient: $recipient,
                subject: $massEmailSend->subject,
                body: $massEmailSend->body
            );

            if ($result['success']) {
                $recipient->markAsSent($result['message_id']);
                $sentCount++;
            } else {
                $recipient->markAsFailed($result['error']);
                $failedCount++;
            }

            // Rate limit: aguarda 100ms entre envios
            usleep(100000);
        }

        // Atualiza estatísticas
        $massEmailSend->update([
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}

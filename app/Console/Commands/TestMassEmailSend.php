<?php

namespace App\Console\Commands;

use App\Domain\Automations\Models\MassEmailSend;
use App\Domain\Automations\Models\MassEmailRecipient;
use App\Domain\Automations\Models\AutomationEvent;
use App\Domain\Integrations\Models\Integration;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Command para testar envio em massa de emails
 * Cria um envio de teste e processa
 */
class TestMassEmailSend extends Command
{
    protected $signature = 'test:mass-email-send';
    protected $description = 'Testa envio em massa de emails';

    public function handle()
    {
        $this->info('🚀 Iniciando teste de envio em massa...');

        // Busca usuário e integração
        $user = User::first();
        if (!$user) {
            $this->error('❌ Nenhum usuário encontrado');
            return 1;
        }

        $integration = Integration::where('user_id', $user->id)
            ->where('provider', 'gmail')
            ->where('status', 'connected')
            ->first();

        if (!$integration) {
            $this->error('❌ Gmail não conectado');
            return 1;
        }

        $event = AutomationEvent::where('key', 'email_mass_send')->first();

        $this->info("📧 Usuário: {$user->email}");
        $this->info("🔗 Gmail: " . ($integration->metadata['email'] ?? 'N/A'));

        // Lê arquivo CSV de teste
        $csvPath = storage_path('test_recipients.csv');
        if (!file_exists($csvPath)) {
            $this->error('❌ Arquivo test_recipients.csv não encontrado');
            return 1;
        }

        $csvContent = file_get_contents($csvPath);
        $lines = explode("\n", $csvContent);
        $headers = str_getcsv(array_shift($lines));

        $recipients = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $values = str_getcsv($line);
            $row = array_combine($headers, $values);
            $recipients[] = $row;
        }

        $this->info("📊 " . count($recipients) . " destinatários carregados");

        // Cria envio em massa
        $massEmailSend = MassEmailSend::create([
            'user_id' => $user->id,
            'integration_id' => $integration->id,
            'automation_event_id' => $event?->id,
            'name' => 'Teste de Envio - ' . now()->format('d/m/Y H:i'),
            'send_type' => 'now',
            'subject' => 'Teste de Automação - {{nome}}',
            'body' => "Olá {{nome}},\n\nEste é um email de teste do sistema de automação da {{empresa}}.\n\nSaudações,\nEquipe Larasonic",
            'recipient_source' => 'csv',
            'email_column' => 'email',
            'columns' => $headers,
            'total_recipients' => count($recipients),
            'valid_recipients' => count($recipients),
            'invalid_recipients' => 0,
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $this->info("✅ Envio criado: ID {$massEmailSend->id}");

        // Cria destinatários
        foreach ($recipients as $recipientData) {
            MassEmailRecipient::create([
                'mass_email_send_id' => $massEmailSend->id,
                'email' => $recipientData['email'],
                'data' => $recipientData,
                'status' => 'pending',
            ]);
        }

        $this->info('📝 Destinatários cadastrados');

        // Processa envio
        $this->info('📤 Enviando emails...');

        \App\Jobs\ProcessMassEmailSendJob::dispatchSync($massEmailSend->id);

        // Mostra resultados
        $massEmailSend->refresh();

        $this->newLine();
        $this->info('✅ Envio concluído!');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total', $massEmailSend->total_recipients],
                ['Enviados', $massEmailSend->sent_count],
                ['Falhas', $massEmailSend->failed_count],
                ['Status', $massEmailSend->status],
            ]
        );

        // Lista destinatários
        $this->newLine();
        $this->info('📋 Detalhes por destinatário:');
        
        $recipientDetails = $massEmailSend->recipients->map(function ($r) {
            return [
                'Email' => $r->email,
                'Status' => $r->status,
                'Mensagem' => $r->status === 'sent' ? '✅ Enviado' : ($r->error_message ?? $r->ignore_reason ?? 'N/A'),
            ];
        })->toArray();

        $this->table(['Email', 'Status', 'Mensagem'], $recipientDetails);

        return 0;
    }
}

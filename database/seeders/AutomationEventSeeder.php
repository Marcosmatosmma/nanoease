<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Automations\Models\AutomationEvent;
use App\Domain\Automations\Models\AutomationTriggerType;

class AutomationEventSeeder extends Seeder
{
    /**
     * Popula eventos de automação e tipos de gatilho
     * 
     * Eventos disponíveis:
     * - email_received: Quando um email é recebido no Gmail
     * - email_mass_send: Envio em massa de emails
     */
    public function run(): void
    {
        // Evento: Email Recebido
        $emailReceived = AutomationEvent::create([
            'key' => 'email_received',
            'title' => 'E-mail recebido',
            'description' => 'Quando um novo e-mail chega na sua caixa de entrada',
            'icon' => 'lucide:mail',
            'category' => 'email',
            'active' => true,
            'position' => 1,
        ]);

        // Tipos de gatilho para email_received
        $triggers = [
            [
                'automation_event_id' => $emailReceived->id,
                'key' => 'subject_contains',
                'title' => 'Assunto contém',
                'description' => 'Quando o assunto do e-mail contém determinado texto',
                'icon' => 'lucide:text',
                'placeholder' => 'Ex: urgente, proposta, orçamento',
                'uses_ai' => false,
                'position' => 1,
            ],
            [
                'automation_event_id' => $emailReceived->id,
                'key' => 'sender_specific',
                'title' => 'Remetente específico',
                'description' => 'Quando o e-mail vem de um endereço específico',
                'icon' => 'lucide:user',
                'placeholder' => 'Ex: cliente@empresa.com',
                'uses_ai' => false,
                'position' => 2,
            ],
            [
                'automation_event_id' => $emailReceived->id,
                'key' => 'ai_context',
                'title' => 'Contexto da IA',
                'description' => 'Use linguagem natural para descrever quando a automação deve rodar',
                'icon' => 'lucide:sparkles',
                'placeholder' => 'Ex: quando for uma reclamação de cliente, quando pedirem orçamento',
                'uses_ai' => true,
                'position' => 3,
            ],
        ];

        foreach ($triggers as $trigger) {
            AutomationTriggerType::create($trigger);
        }

        // Evento: Envio em Massa
        $massEmailSend = AutomationEvent::create([
            'key' => 'email_mass_send',
            'title' => 'Envio de E-mail em Massa',
            'description' => 'Enviar e-mails operacionais para múltiplos destinatários',
            'icon' => 'lucide:send',
            'category' => 'email',
            'active' => true,
            'position' => 2,
        ]);

        $this->command->info('✅ Eventos e gatilhos de automação criados com sucesso!');
        $this->command->info('   - email_received: 3 tipos de gatilho');
        $this->command->info('   - email_mass_send: envio em massa');
    }
}

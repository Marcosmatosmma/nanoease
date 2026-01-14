<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AutomationEventsSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'key' => 'email_received',
                'title' => 'E-mail recebido',
                'description' => 'Capturamos cada mensagem que chegar na sua caixa',
                'icon' => 'lucide:mail',
                'category' => 'email',
                'position' => 1,
                'active' => true,
                'metadata' => json_encode(['required_integrations' => ['gmail']]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'webhook_received',
                'title' => 'Webhook recebido',
                'description' => 'Evento disparado quando uma requisição HTTP chega',
                'icon' => 'lucide:webhook',
                'category' => 'webhook',
                'position' => 2,
                'active' => false,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'task_created',
                'title' => 'Tarefa criada',
                'description' => 'Quando uma nova tarefa é adicionada ao sistema',
                'icon' => 'lucide:check-square',
                'category' => 'task',
                'position' => 3,
                'active' => false,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('automation_events')->insert($events);

        $emailReceivedId = DB::table('automation_events')->where('key', 'email_received')->value('id');

        $triggerTypes = [
            [
                'automation_event_id' => $emailReceivedId,
                'key' => 'sender_exact',
                'title' => 'Remetente específico',
                'description' => 'Match exato do e-mail',
                'icon' => 'lucide:at-sign',
                'placeholder' => 'mitsloan@getsmarter.com',
                'uses_ai' => false,
                'validation_rules' => json_encode(['email:rfc,dns']),
                'position' => 1,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'automation_event_id' => $emailReceivedId,
                'key' => 'sender_domain',
                'title' => 'Qualquer e-mail do domínio',
                'description' => 'Todos os e-mails de @empresa.com',
                'icon' => 'lucide:globe',
                'placeholder' => '@adidas.com ou adidas.com',
                'uses_ai' => false,
                'validation_rules' => json_encode(['regex:/^@?([a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,})$/i']),
                'position' => 2,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'automation_event_id' => $emailReceivedId,
                'key' => 'subject_contains',
                'title' => 'Assunto contém palavras',
                'description' => 'Busca palavras-chave no assunto',
                'icon' => 'lucide:text',
                'placeholder' => 'fatura, invoice, cobrança',
                'uses_ai' => false,
                'validation_rules' => json_encode(['string', 'min:1']),
                'position' => 3,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'automation_event_id' => $emailReceivedId,
                'key' => 'content_semantic',
                'title' => 'Conteúdo do e-mail (análise IA)',
                'description' => 'IA interpreta intenção e contexto',
                'icon' => 'lucide:brain',
                'placeholder' => 'E-mail falando sobre cancelamento de pedido ou devolução de produto',
                'uses_ai' => true,
                'validation_rules' => json_encode(['string', 'min:10']),
                'position' => 4,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('automation_trigger_types')->insert($triggerTypes);
    }
}

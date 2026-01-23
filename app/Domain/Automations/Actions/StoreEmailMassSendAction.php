<?php

declare(strict_types=1);

namespace App\Domain\Automations\Actions;

use App\Domain\Automations\Models\MassEmailSend;
use App\Domain\Automations\Models\MassEmailRecipient;
use App\Domain\Automations\Models\AutomationEvent;
use App\Domain\Integrations\Models\Integration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Action responsável por criar/atualizar envio em massa de emails
 * 
 * Esta Action orquestra todo o processo de criação/edição de um envio em massa:
 * 1. Cria/atualiza o registro principal (MassEmailSend)
 * 2. Processa e valida destinatários do CSV
 * 3. Cria registros de destinatários (MassEmailRecipient)
 * 4. Dispara Job de processamento se for envio imediato
 * 
 * Segue padrão DDD do projeto, mantendo lógica de negócio separada do controller
 */
class StoreEmailMassSendAction
{
    /**
     * Executa a criação ou atualização de um envio em massa
     * 
     * Processo:
     * - Inicia transação de banco de dados para garantir atomicidade
     * - Busca o evento 'email_mass_send' para vincular
     * - Cria/atualiza registro principal com dados do formulário
     * - Remove destinatários antigos (se editando)
     * - Processa e salva novos destinatários do CSV
     * - Dispara Job de envio se tipo = 'now' (imediato)
     * 
     * @param User $user Usuário autenticado (dono do envio)
     * @param Integration $integration Integração Gmail do usuário
     * @param array $data Dados validados vindos do Request
     * @param MassEmailSend|null $massEmailSend Envio existente (null = criar novo)
     * @return MassEmailSend Registro criado/atualizado com dados frescos
     */
    public function handle(
        User $user,
        Integration $integration,
        array $data,
        ?MassEmailSend $massEmailSend = null
    ): MassEmailSend {
        // Usa transação para garantir que tudo seja salvo ou nada (atomicidade)
        return DB::transaction(function () use ($user, $integration, $data, $massEmailSend) {
            
            // Busca o evento 'email_mass_send' cadastrado na tabela automation_events
            // Serve para categorizar este tipo de automação
            $event = AutomationEvent::where('key', 'email_mass_send')->first();
            
            // Prepara array com dados do envio em massa
            $sendData = [
                'user_id' => $user->id,
                'team_id' => $user->currentTeam->id, // OBRIGATÓRIO - Multi-tenancy
                'integration_id' => $integration->id,
                'automation_event_id' => $event?->id,
                'name' => $data['name'], // Nome identificador do envio
                'send_type' => $data['send_type'], // 'now' ou 'scheduled'
                'scheduled_at' => $data['send_type'] === 'scheduled' ? $data['scheduled_at'] : null,
                'subject' => $data['subject'], // Template do assunto (pode ter {{variáveis}})
                'body' => $data['body'], // Template do corpo (pode ter {{variáveis}})
                'recipient_source' => $data['recipient_source'] ?? 'csv', // Fonte: 'csv' ou 'sheets'
                'email_column' => $data['email_column'], // Nome da coluna que contém emails
                'columns' => $data['recipients_data']['columns'] ?? [], // Todas as colunas do CSV
                'total_recipients' => $data['recipients_data']['validation']['total'] ?? 0,
                'valid_recipients' => $data['recipients_data']['validation']['valid'] ?? 0,
                'invalid_recipients' => $data['recipients_data']['validation']['invalid'] ?? 0,
                'status' => $data['send_type'] === 'scheduled' ? 'scheduled' : 'draft',
            ];

            // Cria novo registro ou atualiza existente
            if ($massEmailSend) {
                $massEmailSend->update($sendData);
            } else {
                $massEmailSend = MassEmailSend::create($sendData);
            }

            // Se está editando (não foi recém-criado), remove destinatários antigos
            // wasRecentlyCreated = false significa que já existia antes
            if ($massEmailSend->wasRecentlyCreated === false) {
                $massEmailSend->recipients()->delete();
            }

            // Processa CSV e cria registros de destinatários
            $this->processRecipients($massEmailSend, $data);

            // Se for envio imediato, inicia processamento agora
            if ($data['send_type'] === 'now') {
                $this->startSending($massEmailSend);
            }

            // Retorna registro atualizado (fresh() recarrega do banco)
            return $massEmailSend->fresh();
        });
    }

    /**
     * Processa destinatários do CSV e cria registros no banco
     * 
     * Processo:
     * - Itera sobre linhas do CSV parseado
     * - Valida formato de email com regex
     * - Remove duplicados (mesmo email não pode repetir)
     * - Cria registro MassEmailRecipient para cada email válido
     * - Salva dados completos da linha para personalização ({{nome}}, {{empresa}}, etc)
     * 
     * Apenas emails VÁLIDOS e NÃO DUPLICADOS são salvos.
     * Emails inválidos já foram identificados no frontend, mas validamos novamente.
     * 
     * @param MassEmailSend $massEmailSend Envio em massa (parent)
     * @param array $data Dados do formulário contendo CSV parseado
     * @return void
     */
    protected function processRecipients(MassEmailSend $massEmailSend, array $data): void
    {
        // Identifica qual coluna contém os emails (ex: 'email', 'e-mail')
        $emailColumn = $data['email_column'];
        
        // Dados parseados do CSV (array de arrays associativos)
        // Ex: [['email' => 'teste@gmail.com', 'nome' => 'João'], ...]
        $csvData = $data['recipients_data']['data'] ?? [];
        
        // Informações de validação feita no frontend
        $validation = $data['recipients_data']['validation'] ?? [];
        $invalidList = collect($validation['invalidList'] ?? []);
        
        // Regex simples para validar formato de email
        // Formato: algo@dominio.com
        $emailRegex = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
        
        // Array para controlar emails já processados (evita duplicados)
        $processedEmails = [];

        // Itera sobre cada linha do CSV
        foreach ($csvData as $index => $row) {
            // Extrai email da coluna especificada
            $email = trim($row[$emailColumn] ?? '');
            
            // Valida formato do email
            // Se vazio ou inválido, pula para próxima linha
            if (empty($email) || !preg_match($emailRegex, $email)) {
                continue; // Ignora inválidos
            }

            // Verifica se email já foi processado (duplicado)
            // Mesmo email não pode aparecer duas vezes
            if (in_array($email, $processedEmails)) {
                continue; // Ignora duplicados
            }

            // Adiciona à lista de processados
            $processedEmails[] = $email;

            // Cria registro de destinatário no banco
            MassEmailRecipient::create([
                'mass_email_send_id' => $massEmailSend->id,
                'email' => $email,
                'data' => $row, // Salva TODA a linha (nome, empresa, etc) para personalização
                'status' => 'pending', // Status inicial: aguardando envio
            ]);
        }
    }

    /**
     * Inicia o processamento de envio imediato
     * 
     * Processo:
     * - Atualiza status do envio para 'processing'
     * - Registra timestamp de início
     * - Dispara Job assíncrono para processar envios
     * 
     * O Job (ProcessMassEmailSendJob) irá:
     * - Buscar destinatários com status 'pending'
     * - Enviar emails um por um via Gmail API
     * - Atualizar status de cada destinatário
     * - Respeitar rate limit (100ms entre envios)
     * 
     * @param MassEmailSend $massEmailSend Envio em massa
     * @return void
     */
    protected function startSending(MassEmailSend $massEmailSend): void
    {
        // Atualiza status e timestamp de início
        $massEmailSend->update([
            'status' => 'processing', // Indica que está em andamento
            'started_at' => now(), // Registra quando começou
        ]);

        // Dispara Job na fila para processar envios
        // O Job é assíncrono para não travar a requisição HTTP
        \App\Jobs\ProcessMassEmailSendJob::dispatch($massEmailSend->id);
    }
}

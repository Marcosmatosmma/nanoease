<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Automations\Models\Automation;
use App\Domain\Automations\Services\Executors\EmailAutomationExecutor;
use App\Domain\AI\Services\Automations\ActionDecisionEngine;
use App\Domain\Integrations\Services\GmailMessageFetcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class WatchEmailAutomations extends Command
{
    protected $signature = 'automations:watch
        {--interval=30 : Intervalo em segundos entre verificações}
        {--force : Forçar execução mesmo se já houver uma instância rodando}';

    protected $description = 'Fica observando continuamente por novos e-mails e executa automações em tempo real';

    private bool $shouldStop = false;
    private const LOCK_KEY = 'automations:watch:lock';
    private const LOCK_TTL = 300;

    public function handle(
        GmailMessageFetcher $fetcher,
        EmailAutomationExecutor $executor,
        ActionDecisionEngine $decisionEngine,
    ): int
    {
        // 🔒 Verificar se já existe uma instância rodando
        if (!$this->option('force') && !Cache::add(self::LOCK_KEY, true, self::LOCK_TTL)) {
            $this->error('❌ Já existe uma instância do watcher rodando!');
            $this->line('   Use --force para ignorar esta verificação.');
            return self::FAILURE;
        }
        
        $interval = max(10, (int) $this->option('interval'));
        
        $this->info("👀 Observando e-mails a cada {$interval} segundos...");
        $this->info('🛑 Pressione Ctrl+C para parar');
        $this->newLine();
        
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, function () {
            $this->shouldStop = true;
            $this->warn("\n🛑 Parando observador...");
        });
        
        $lastCheck = now()->subMinutes(5);
        $iteration = 0;
        
        while (!$this->shouldStop) {
            // Renovar lock a cada iteração
            Cache::put(self::LOCK_KEY, true, self::LOCK_TTL);
            
            $iteration++;
            $now = now();
            
            $this->line("🔄 Verificação #{$iteration} - " . $now->format('H:i:s'));
            
            $automations = Automation::query()
                ->with(['actions', 'integration', 'triggerType', 'event'])
                ->whereHas('integration', fn($q) => $q->where('provider', 'gmail'))
                ->get();
                
            if ($automations->isEmpty()) {
                $this->warn('   ⚠️  Nenhuma automação configurada');
                sleep($interval);
                continue;
            }
            
            $totalProcessed = 0;
            $totalExecuted = 0;
            
            foreach ($automations as $automation) {
                $timestamp = $lastCheck->timestamp;
                $query = "after:{$timestamp} -in:sent -in:drafts";
                
                $result = $fetcher->fetch($automation->integration, $query, 20);
                
                if (($result['status'] ?? 'error') !== 'ok') {
                    continue;
                }
                
                $messages = collect($result['messages'] ?? []);
                
                foreach ($messages as $msg) {
                    // Pular se não tiver Message-ID
                    if (empty($msg['message_id'])) {
                        $this->warn("   ⚠️  E-mail sem Message-ID, pulando...");
                        continue;
                    }
                    
                    $alreadyProcessed = DB::table('automation_executions')
                        ->where('automation_id', $automation->id)
                        ->where('email_id', $msg['message_id'])
                        ->exists();
                        
                    if ($alreadyProcessed) {
                        $subject = substr($msg['subject'] ?? '', 0, 40);
                        $this->line("   ⏭️  Já processado: {$subject}... (Message-ID: {$msg['message_id']})");
                        continue;
                    }
                    
                    $totalProcessed++;
                    
                    $event = [
                        'from' => $msg['from'] ?? '',
                        'subject' => $msg['subject'] ?? '',
                        'body' => $msg['body'] ?? '',
                        'snippet' => $msg['snippet'] ?? '',
                        'gmail_id' => $msg['gmail_id'] ?? $msg['id'] ?? '',
                        'message_id' => $msg['message_id'] ?? '',
                        'date' => $msg['date'] ?? now()->toIso8601String(),
                    ];
                    
                    // DEBUG: Mostrar o que foi recebido
                    $this->line("   📧 From: " . substr($event['from'], 0, 50));
                    $this->line("   📧 Subject: " . substr($event['subject'], 0, 50));
                    $bodyPreview = strip_tags($event['body']);
                    $this->line("   📧 Body (texto): " . substr($bodyPreview, 0, 100) . "...");
                    
                    $availableActions = $automation->actions->map(fn($a) => [
                        'type' => $a->type,
                        'config' => $a->config,
                    ])->toArray();
                    
                    $usesAI = $automation->triggerType->uses_ai ?? false;
                    
                    if ($usesAI) {
                        $planRule = $automation->plan_rule_text ?: $automation->rule_text;
                        $this->line("   🤖 Usando IA para decidir...");
                        $this->line("   🤖 Regra: " . $planRule);
                        
                        // Mostra o que a IA vai analisar (versão limpa)
                        $cleanBody = strip_tags($event['body']);
                        $cleanBody = preg_replace('/\s+/', ' ', $cleanBody);
                        $this->line("   🤖 Body limpo: " . substr($cleanBody, 0, 200) . "...");
                        
                        $decision = $decisionEngine->decide($planRule, $event, $availableActions);
                        $shouldExecute = $decision['should_execute'];
                        $confidence = $decision['confidence'];
                        $reasoning = $decision['reasoning'];
                        $this->line("   🤖 Decisão: " . ($shouldExecute ? 'EXECUTAR ✅' : 'PULAR ❌'));
                        $this->line("   🤖 Confidence: " . round($confidence * 100) . "%");
                        $this->line("   🤖 Reasoning: " . $reasoning);
                    } else {
                        $shouldExecute = $this->matchDirect($automation, $event);
                        $confidence = $shouldExecute ? 1.0 : 0.0;
                        $reasoning = 'Match direto';
                        
                        // DEBUG: Mostrar resultado do match
                        $this->line("   🔍 Trigger: {$automation->triggerType->key}");
                        $this->line("   🔍 Rule: {$automation->rule_text}");
                        $this->line("   🔍 Subject: {$event['subject']}");
                        $this->line("   🔍 Match: " . ($shouldExecute ? '✅ SIM' : '❌ NÃO'));
                    }
                    
                    $status = 'skipped';
                    $actionResult = null;
                    
                    // 🔒 MARCA COMO PROCESSADO ANTES DE EXECUTAR (evita duplicação)
                    try {
                        DB::table('automation_executions')->insert([
                            'automation_id' => $automation->id,
                            'team_id' => $automation->team_id, // Multi-tenancy
                            'email_id' => $msg['message_id'],
                            'email_subject' => $msg['subject'] ?? null,
                            'email_from' => $msg['from'] ?? null,
                            'status' => 'processing',
                            'reasoning' => $reasoning,
                            'confidence' => $confidence,
                            'action_result' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        // Se falhar ao inserir (ex: duplicado), pular este e-mail
                        $this->warn("   ⚠️  Erro ao marcar como processado: " . $e->getMessage());
                        continue;
                    }
                    
                    if ($shouldExecute) {
                        $subject = substr($msg['subject'] ?? '', 0, 60);
                        $this->info("   ✅ NOVO: {$subject}...");
                        $this->line("      Automação: {$automation->rule_text}");
                        
                        $execResult = $executor->execute($automation, $event);
                        $status = $execResult['status'] === 'executed' ? 'success' : 'failed';
                        $actionResult = $execResult;
                        $totalExecuted++;
                        
                        if ($status === 'success') {
                            $this->info("      🚀 Ação executada com sucesso!");
                        } else {
                            $this->error("      ❌ Falha ao executar ação");
                        }
                        
                        // Atualiza o status final
                        DB::table('automation_executions')
                            ->where('automation_id', $automation->id)
                            ->where('email_id', $msg['message_id'])
                            ->update([
                                'status' => $status,
                                'action_result' => $actionResult ? json_encode($actionResult) : null,
                                'updated_at' => now(),
                            ]);
                    } else {
                        // Atualiza para skipped
                        DB::table('automation_executions')
                            ->where('automation_id', $automation->id)
                            ->where('email_id', $msg['message_id'])
                            ->update([
                                'status' => 'skipped',
                                'updated_at' => now(),
                            ]);
                    }
                }
            }
            
            if ($totalProcessed > 0) {
                $this->info("   📊 Processados: {$totalProcessed} | Executados: {$totalExecuted}");
            } else {
                $this->line("   💤 Nenhum e-mail novo");
            }
            
            $lastCheck = $now;
            $this->newLine();
            
            sleep($interval);
        }
        
        // Liberar lock ao sair
        Cache::forget(self::LOCK_KEY);
        
        $this->info('✅ Observador finalizado');
        return self::SUCCESS;
    }
    
    private function matchDirect(Automation $automation, array $event): bool
    {
        $triggerKey = $automation->triggerType->key ?? '';
        $rule = $automation->rule_text;
        
        // Extrai email do formato "Nome <email@domain.com>"
        preg_match('/<(.+?)>/', $event['from'], $emailMatches);
        $email = $emailMatches[1] ?? $event['from'];
        
        return match($triggerKey) {
            'sender_specific' => strcasecmp($email, trim($rule)) === 0,
            'sender_exact' => strcasecmp($email, trim($rule)) === 0,
            'sender_domain' => str_contains(strtolower($email), '@' . str_replace('@', '', strtolower(trim($rule)))),
            'subject_contains' => $this->matchSubjectContains($event['subject'], $rule),
            default => false,
        };
    }

    /**
     * Verifica se o assunto contém alguma das palavras-chave
     * Suporta múltiplas palavras separadas por vírgula
     * 
     * @param string $subject Assunto do email
     * @param string $rule Regra com palavras separadas por vírgula
     * @return bool true se encontrou match
     */
    private function matchSubjectContains(string $subject, string $rule): bool
    {
        // Separa por vírgula e remove espaços
        $keywords = array_map('trim', explode(',', $rule));
        
        // Verifica se o assunto contém alguma das palavras-chave
        foreach ($keywords as $keyword) {
            if (empty($keyword)) continue;
            
            if (stripos($subject, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
}

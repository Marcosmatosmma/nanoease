<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Automations\Models\Automation;
use App\Domain\Automations\Services\Executors\EmailAutomationExecutor;
use App\Domain\AI\Services\Automations\ActionDecisionEngine;
use App\Domain\Integrations\Services\GmailMessageFetcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class RunEmailAutomations extends Command
{
    protected $signature = 'automations:run-email
        {--minutes-ago=5 : Buscar e-mails dos últimos X minutos}
        {--dry-run : Simular sem executar ações}';

    protected $description = 'Executa automações de e-mail para mensagens recentes (usado no scheduler)';

    public function handle(
        GmailMessageFetcher $fetcher,
        EmailAutomationExecutor $executor,
        ActionDecisionEngine $decisionEngine,
    ): int
    {
        $minutesAgo = (int) $this->option('minutes-ago');
        $dryRun = (bool) $this->option('dry-run');
        
        $this->info("🔄 Buscando e-mails dos últimos {$minutesAgo} minutos...");
        
        $automations = Automation::query()
            ->with(['actions', 'integration', 'triggerType', 'event'])
            ->whereHas('integration', fn($q) => $q->where('provider', 'gmail'))
            ->get();
            
        if ($automations->isEmpty()) {
            $this->warn('⚠️  Nenhuma automação de e-mail configurada.');
            return self::SUCCESS;
        }
        
        $this->line("📋 {$automations->count()} automação(ões) ativa(s)");
        $this->newLine();
        
        $totalProcessed = 0;
        $totalExecuted = 0;
        
        foreach ($automations as $automation) {
            $this->line("🤖 Automação #{$automation->id}: {$automation->rule_text}");
            
            $query = $this->buildQuery($minutesAgo);
            $result = $fetcher->fetch($automation->integration, $query, 50);
            
            if (($result['status'] ?? 'error') !== 'ok') {
                $this->error("   ❌ Erro ao buscar e-mails: " . ($result['message'] ?? 'desconhecido'));
                continue;
            }
            
            $messages = collect($result['messages'] ?? []);
            
            if ($messages->isEmpty()) {
                $this->line("   ℹ️  Nenhum e-mail novo");
                continue;
            }
            
            $this->line("   📧 {$messages->count()} e-mail(s) encontrado(s)");
            
            foreach ($messages as $msg) {
                $alreadyProcessed = DB::table('automation_executions')
                    ->where('automation_id', $automation->id)
                    ->where('email_id', $msg['id'])
                    ->exists();
                    
                if ($alreadyProcessed) {
                    continue;
                }
                
                $totalProcessed++;
                
                $event = [
                    'from' => $msg['from'] ?? '',
                    'subject' => $msg['subject'] ?? '',
                    'body' => $msg['body'] ?? '',
                ];
                
                $availableActions = $automation->actions->map(fn($a) => [
                    'type' => $a->type,
                    'config' => $a->config,
                ])->toArray();
                
                $usesAI = $automation->triggerType->uses_ai ?? false;
                
                if ($usesAI) {
                    $planRule = $automation->plan_rule_text ?: $automation->rule_text;
                    $decision = $decisionEngine->decide($planRule, $event, $availableActions);
                    $shouldExecute = $decision['should_execute'];
                    $confidence = $decision['confidence'];
                    $reasoning = $decision['reasoning'];
                } else {
                    $shouldExecute = $this->matchDirect($automation, $event);
                    $confidence = $shouldExecute ? 1.0 : 0.0;
                    $reasoning = 'Match direto';
                }
                
                $status = 'skipped';
                $actionResult = null;
                
                if ($shouldExecute) {
                    $this->info("      ✅ Match: {$msg['subject']}");
                    
                    if (!$dryRun) {
                        $execResult = $executor->execute($automation, $event);
                        $status = $execResult['status'] === 'executed' ? 'success' : 'failed';
                        $actionResult = $execResult;
                        $totalExecuted++;
                    } else {
                        $this->warn("      🔸 [DRY-RUN] Não executando ação");
                    }
                }
                
                DB::table('automation_executions')->insert([
                    'automation_id' => $automation->id,
                    'email_id' => $msg['id'],
                    'email_subject' => $msg['subject'] ?? null,
                    'email_from' => $msg['from'] ?? null,
                    'status' => $status,
                    'reasoning' => $reasoning,
                    'confidence' => $confidence,
                    'action_result' => $actionResult ? json_encode($actionResult) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            $this->newLine();
        }
        
        $this->info("✅ Processados: {$totalProcessed} e-mails");
        $this->info("🚀 Executados: {$totalExecuted} ações");
        
        return self::SUCCESS;
    }
    
    private function buildQuery(int $minutesAgo): string
    {
        $timestamp = now()->subMinutes($minutesAgo)->timestamp;
        return "after:{$timestamp}";
    }
    
    private function matchDirect(Automation $automation, array $event): bool
    {
        $triggerKey = $automation->triggerType->key ?? '';
        $rule = $automation->rule_text;
        
        preg_match('/<(.+?)>/', $event['from'], $emailMatches);
        $email = $emailMatches[1] ?? $event['from'];
        
        return match($triggerKey) {
            'sender_exact' => strcasecmp($email, trim($rule)) === 0,
            'sender_domain' => str_contains(strtolower($email), '@' . str_replace('@', '', strtolower(trim($rule)))),
            'subject_contains' => stripos($event['subject'], $rule) !== false,
            default => false,
        };
    }
}

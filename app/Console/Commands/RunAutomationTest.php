<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Automations\Actions\RunEmailAutomationAction;
use App\Domain\Automations\Models\Automation;
use App\Domain\Automations\Services\Executors\EmailAutomationExecutor;
use App\Domain\AI\Services\RuleInterpreter;
use App\Domain\AI\Services\Automations\ActionDecisionEngine;
use App\Domain\Integrations\Services\GmailMessageFetcher;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

final class RunAutomationTest extends Command
{
    protected $signature = 'automations:test
        {automation_id : ID da automação}
        {--limit=5 : Limite de e-mails a analisar}';

    protected $description = 'Testa uma automação buscando e-mails reais e executando automaticamente com MCP.';

    public function handle(
        GmailMessageFetcher $fetcher,
        EmailAutomationExecutor $mcpExecutor,
        ActionDecisionEngine $decisionEngine,
    ): int
    {
        $automationId = (int) $this->argument('automation_id');
        $automation = Automation::query()
            ->with(['actions', 'integration', 'triggerType', 'event'])
            ->find($automationId);

        if (! $automation) {
            $this->error('❌ Automação não encontrada.');
            return self::FAILURE;
        }

        if (! $automation->integration || $automation->integration->provider !== 'gmail') {
            $this->error('❌ Esta automação precisa ter uma integração Gmail configurada.');
            return self::FAILURE;
        }

        if (! $automation->triggerType) {
            $this->error('❌ Automação sem tipo de trigger configurado.');
            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));

        $usesAI = $automation->triggerType->uses_ai;
        $triggerKey = $automation->triggerType->key;

        $this->info($usesAI ? '🧠 Modo: IA (ActionDecisionEngine)' : '⚡ Modo: Match Direto (Sem IA)');
        $this->line("🔍 Trigger: {$automation->triggerType->title}");
        $this->line("📝 Regra: {$automation->rule_text}");
        $this->newLine();
        $this->line("📥 Buscando últimos {$limit} e-mails...");

        // Busca e-mails reais do Gmail
        $fetched = $fetcher->fetch($automation->integration, '', $limit);

        if (($fetched['status'] ?? 'error') !== 'ok') {
            $this->error('❌ ' . ($fetched['message'] ?? 'Falha ao buscar mensagens.'));
            return self::FAILURE;
        }

        $messages = collect($fetched['messages'] ?? []);
        
        if ($messages->isEmpty()) {
            $this->warn('⚠️  Nenhuma mensagem encontrada.');
            return self::SUCCESS;
        }

        $this->info("✅ {$messages->count()} e-mails encontrados");
        $this->newLine();

        // Debug: Mostrar primeiros e-mails para verificar conteúdo
        if ($usesAI) {
            $this->line('🔍 Primeiros e-mails (para debug IA):');
            foreach ($messages->take(3) as $index => $msg) {
                $num = $index + 1;
                $from = $msg['from'] ?? '';
                $subject = $msg['subject'] ?? '';
                $bodyPreview = substr($msg['body'] ?? '', 0, 200);
                $this->line("   #{$num} - De: {$from}");
                $this->line("        Assunto: {$subject}");
                $this->line("        Corpo (preview): {$bodyPreview}...");
                $this->newLine();
            }
            $this->newLine();
        }

        // Analisa cada e-mail com base no tipo de trigger
        if ($usesAI) {
            // Usa ActionDecisionEngine (IA)
            $analyzed = $this->analyzeWithAI($messages, $automation, $decisionEngine);
        } else {
            // Usa match direto (sem IA)
            $analyzed = $this->analyzeWithDirectMatch($messages, $automation, $triggerKey);
        }

        // Mostra análise
        $toExecute = $analyzed->filter(fn($a) => $a['should_execute']);
        $toIgnore = $analyzed->filter(fn($a) => !$a['should_execute']);

        $this->line('📊 Análise dos e-mails:');
        $this->newLine();

        if ($toExecute->isNotEmpty()) {
            $this->info("✅ {$toExecute->count()} e-mail(s) que ATENDEM a regra:");
            foreach ($toExecute as $item) {
                $confidence = number_format($item['confidence'] * 100, 0);
                $this->line("   #{$item['index']} - De: {$item['event']['from']}");
                $this->line("        Assunto: {$item['event']['subject']}");
                $this->line("        Confiança: {$confidence}%");
                if ($item['action']) {
                    $this->line("        Ação: {$item['action']}");
                }
                $this->line("        Raciocínio: {$item['reasoning']}");
                $this->newLine();
            }
        }

        if ($toIgnore->isNotEmpty()) {
            $this->line("⏭️  {$toIgnore->count()} e-mail(s) IGNORADOS (não atendem a regra)");
            $this->newLine();
        }

        // Executa automaticamente
        if ($toExecute->isEmpty()) {
            $this->warn('⚠️  Nenhum e-mail atende à regra. Nada a executar.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('🚀 EXECUTANDO as ações...');
        $this->newLine();

        $results = collect();
        
        foreach ($toExecute as $item) {
            $result = $mcpExecutor->execute($automation, $item['event']);

            $status = $result['status'] === 'executed' ? '✅' : '❌';
            $this->line("{$status} E-mail #{$item['index']}: {$result['message']}");
            
            if (isset($result['context']['recipients'])) {
                $this->line("   📨 Para: " . implode(', ', $result['context']['recipients']));
            }

            $results->push([
                'index' => $item['index'],
                'status' => $result['status'],
                'message' => $result['message'],
            ]);
        }

        $this->newLine();
        $executed = $results->where('status', 'executed')->count();
        $failed = $results->where('status', '!=', 'executed')->count();

        $this->info("✅ {$executed} executado(s) com sucesso");
        if ($failed > 0) {
            $this->error("❌ {$failed} falharam");
        }

        return $executed > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function analyzeWithDirectMatch(Collection $messages, Automation $automation, string $triggerKey): Collection
    {
        return $messages->map(function (array $msg, $index) use ($automation, $triggerKey) {
            $from = $msg['from'] ?? '';
            $subject = $msg['subject'] ?? '';
            $matches = false;
            $reasoning = '';

            // Extrair e-mail do campo "from" (formato: "Name <email@domain.com>")
            preg_match('/<(.+?)>/', $from, $emailMatches);
            $email = $emailMatches[1] ?? $from;

            switch ($triggerKey) {
                case 'sender_exact':
                    $matches = strcasecmp($email, trim($automation->rule_text)) === 0;
                    $reasoning = $matches 
                        ? "E-mail do remetente ({$email}) corresponde exatamente à regra."
                        : "E-mail do remetente ({$email}) não corresponde.";
                    break;

                case 'sender_domain':
                    $ruleDomain = str_replace('@', '', strtolower(trim($automation->rule_text)));
                    preg_match('/@(.+)$/', $email, $domainMatches);
                    $emailDomain = strtolower($domainMatches[1] ?? '');
                    $matches = $emailDomain === $ruleDomain;
                    $reasoning = $matches 
                        ? "Domínio do remetente ({$emailDomain}) corresponde à regra."
                        : "Domínio do remetente ({$emailDomain}) não corresponde.";
                    break;

                case 'subject_contains':
                    // Separar por vírgula OU espaço
                    $keywords = preg_split('/[,\s]+/', $automation->rule_text, -1, PREG_SPLIT_NO_EMPTY);
                    $keywords = array_map('trim', $keywords);
                    foreach ($keywords as $keyword) {
                        if (stripos($subject, $keyword) !== false) {
                            $matches = true;
                            $reasoning = "Assunto contém a palavra-chave '{$keyword}'.";
                            break;
                        }
                    }
                    if (!$matches) {
                        $reasoning = "Assunto não contém nenhuma das palavras-chave: " . implode(', ', $keywords);
                    }
                    break;

                default:
                    $reasoning = "Tipo de trigger '{$triggerKey}' não suportado no match direto.";
                    break;
            }

            return [
                'index' => $index + 1,
                'message' => $msg,
                'event' => [
                    'from' => $from,
                    'subject' => $subject,
                    'snippet' => $msg['snippet'] ?? '',
                    'body' => $msg['snippet'] ?? '',
                ],
                'should_execute' => $matches,
                'confidence' => $matches ? 1.0 : 0.0,
                'action' => $matches ? 'encaminhar' : null,
                'reasoning' => $reasoning,
            ];
        });
    }

    private function analyzeWithAI(Collection $messages, Automation $automation, ActionDecisionEngine $decisionEngine): Collection
    {
        return $messages->map(function (array $msg, $index) use ($automation, $decisionEngine) {
            $event = [
                'from' => $msg['from'] ?? '',
                'subject' => $msg['subject'] ?? '',
                'snippet' => $msg['snippet'] ?? '',
                'body' => $msg['body'] ?? $msg['snippet'] ?? '',
            ];

            $availableActions = $automation->actions->map(fn ($action) => [
                'type' => $action->type,
                'position' => $action->position,
                'config' => $action->config,
            ])->toArray();

            // Usar plan_rule_text (planejado pela IA) ou rule_text como fallback
            $planRule = $automation->plan_rule_text ?: $automation->rule_text;
            $decision = $decisionEngine->decide($planRule, $event, $availableActions);

            return [
                'index' => $index + 1,
                'message' => $msg,
                'event' => $event,
                'should_execute' => $decision['should_execute'],
                'confidence' => $decision['confidence'],
                'action' => $decision['action_to_execute'] ?? null,
                'reasoning' => $decision['reasoning'],
            ];
        });
    }
}

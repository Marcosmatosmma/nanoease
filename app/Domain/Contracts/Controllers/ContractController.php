<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Controllers;

use App\Domain\Contracts\Actions\StoreContractAction;
use App\Domain\Contracts\Actions\UpdateContractAction;
use App\Domain\Contracts\Actions\DeleteContractAction;
use App\Domain\Contracts\Actions\UploadContractDocumentAction;
use App\Domain\Contracts\Actions\CreateTaskFromContractAction;
use App\Domain\Contracts\Actions\UploadInvoiceAction;
use App\Domain\Contracts\Models\Contract;
use App\Domain\Contracts\Models\ContractAlert;
use App\Domain\Contracts\Models\ContractDocument;
use App\Domain\Contracts\Models\Invoice;
use App\Domain\Contracts\Services\ContractAssistantService;
use App\Domain\Contracts\Services\ContractAlertService;
use App\Domain\Contracts\Services\ContractDataExtractionService;
use App\Domain\Contracts\Services\DocumentStorageService;
use App\Domain\Contracts\Services\CnpjApiService;
use App\Domain\Contracts\Services\InvoicePdfExtractionService;
use App\Domain\Contracts\Requests\StoreContractRequest;
use App\Domain\Contracts\Requests\UpdateContractRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Smalot\PdfParser\Parser as PdfParser;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller para o sistema de contratos
 * 
 * Gerencia CRUD, documentos, alertas e interação com IA
 */
final class ContractController
{
    use AuthorizesRequests;
    /**
     * Lista todos os contratos do team
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        $query = Contract::where('team_id', $teamId)
            ->with(['documents', 'user', 'alerts']);

        // Filtros
        if ($request->has('status') && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }

        if ($request->has('role') && $request->role !== 'todos') {
            $query->where('my_role', $request->role);
        }

        if ($request->has('type') && $request->type) {
            $query->where('contract_type', $request->type);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('contract_type', 'like', "%{$request->search}%");
            });
        }

        // Filtro por Data de Vencimento (Start)
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('end_date', '>=', $request->start_date);
        }

        // Filtro por Data de Vencimento (End)
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        // Filtro por Valor Mínimo
        if ($request->has('min_amount') && $request->min_amount !== null) {
            $query->where('amount', '>=', $request->min_amount);
        }

        // Filtro por Valor Máximo
        if ($request->has('max_amount') && $request->max_amount !== null) {
            $query->where('amount', '<=', $request->max_amount);
        }

        // Ordenação
        $query->orderBy('end_date', 'asc')->orderBy('created_at', 'desc');

        $contracts = $query->get()->map(function ($contract) {
            return [
                'id' => $contract->id,
                'name' => $contract->name,
                'contract_type' => $contract->contract_type,
                'status' => $contract->status,
                'start_date' => $contract->start_date?->format('Y-m-d'),
                'end_date' => $contract->end_date?->format('Y-m-d'),
                'amount' => $contract->amount,
                'currency' => $contract->currency,
                'auto_renewal' => $contract->auto_renewal,
                'is_expired' => $contract->isExpired(),
                'expires_today' => $contract->expirestoday(),
                'expires_soon' => $contract->expiresInDays(30),
                'days_until_expiration' => $contract->end_date ? now()->startOfDay()->diffInDays($contract->end_date->startOfDay(), false) : null,
                'documents_count' => $contract->documents->count(),
                'created_by' => $contract->user->name,
                'created_at' => $contract->created_at->format('d/m/Y'),
                'my_role' => $contract->my_role,
            ];
        });

        return Inertia::render('Contracts/Index', [
            'contracts' => $contracts,
            'filters' => $request->only(['status', 'type', 'search', 'start_date', 'end_date', 'min_amount', 'max_amount', 'role']),
        ]);
    }

    /**
     * Exibe detalhes do contrato
     */
    public function show(Contract $contract): Response
    {
        $this->authorize('view', $contract);

        $contract->load([
            'documents',
            'alerts.task',
            'history.user',
            'user',
            'invoices.uploader',
            'comments.user',
        ]);

        return Inertia::render('Contracts/Show', [
            'contract' => [
                'id' => $contract->id,
                'name' => $contract->name,
                'contract_type' => $contract->contract_type,
                'my_role' => $contract->my_role,
                'contract_object' => $contract->contract_object,
                'contract_number' => $contract->contract_number,
                'contractor' => $contract->contractor,
                'contractor_cpf_cnpj' => $contract->contractor_cpf_cnpj,
                'contracted' => $contract->contracted,
                'contracted_cpf_cnpj' => $contract->contracted_cpf_cnpj,
                'start_date' => $contract->start_date?->format('Y-m-d'),
                'end_date' => $contract->end_date?->format('Y-m-d'),
                'auto_renewal' => $contract->auto_renewal,
                'amount' => $contract->amount,
                'currency' => $contract->currency,
                'payment_terms' => $contract->payment_terms,
                'status' => $contract->status,
                'ai_summary' => $contract->ai_summary,
                'notes' => $contract->notes,
                // Dados de Nota Fiscal
                'invoice_contact_email' => $contract->invoice_contact_email,
                'invoice_contact_link' => $contract->invoice_contact_link,
                'invoice_system' => $contract->invoice_system,
                'invoice_description' => $contract->invoice_description,
                'invoice_internal_notes' => $contract->invoice_internal_notes,
                'invoice_recipient_name' => $contract->invoice_recipient_name,
                'invoice_recipient_cnpj' => $contract->invoice_recipient_cnpj,
                'invoice_state_registration' => $contract->invoice_state_registration,
                'invoice_recipient_address' => $contract->invoice_recipient_address,
                'invoice_service_code' => $contract->invoice_service_code,
                'invoice_due_day' => $contract->invoice_due_day,
                'invoice_cnpj_api_data' => $contract->invoice_cnpj_api_data,
                'documents' => $contract->documents->map(fn($doc) => [
                    'id' => $doc->id,
                    'file_name' => $doc->file_name,
                    'file_type' => $doc->file_type,
                    'file_size' => $doc->formatted_size,
                    'uploaded_at' => $doc->uploaded_at->format('d/m/Y H:i'),
                ]),
                'alerts' => $contract->alerts->map(fn($alert) => [
                    'id' => $alert->id,
                    'alert_type' => $alert->alert_type,
                    'days_before' => $alert->days_before,
                    'is_active' => $alert->is_active,
                    'triggered_at' => $alert->triggered_at?->format('d/m/Y H:i'),
                    'task_id' => $alert->task_id,
                ]),
                'history' => $contract->history->map(fn($event) => [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'description' => $event->description,
                    'user_name' => $event->user?->name ?? 'Sistema',
                    'created_at' => $event->created_at->format('d/m/Y H:i'),
                ]),
                'invoices' => $contract->invoices->map(fn($invoice) => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
                    'due_date' => $invoice->due_date?->format('Y-m-d'),
                    'amount' => $invoice->amount,
                    'description' => $invoice->description,
                    'has_pdf' => (bool) $invoice->pdf_path,
                    'has_xml' => (bool) $invoice->xml_path,
                    'pdf_size' => $invoice->formatted_pdf_size,
                    'is_overdue' => $invoice->isOverdue(),
                    'is_due_today' => $invoice->isDueToday(),
                    'is_paid' => $invoice->is_paid,
                    'paid_at' => $invoice->paid_at?->format('d/m/Y'),
                    'uploaded_by' => $invoice->uploader->name,
                    'uploaded_at' => $invoice->created_at->format('d/m/Y H:i'),
                ]),
                'comments' => $contract->comments->map(fn($comment) => [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ],
                    'created_at' => $comment->created_at->format('d/m/Y H:i'),
                    'is_author' => $comment->user_id === Auth::id(),
                ]),
                'created_by' => $contract->user->name,
                'created_at' => $contract->created_at->format('d/m/Y H:i'),
            ],
            'report' => $this->calculateReportData($contract),
        ]);
    }

    /**
     * Exibe formulário de criação
     */
    public function create(): Response
    {
        return Inertia::render('Contracts/Create');
    }

    /**
     * Cria novo contrato
     */
    public function store(StoreContractRequest $request, StoreContractAction $action): RedirectResponse
    {
        $user = Auth::user();
        
        // Pegar documento se foi enviado
        $document = $request->hasFile('document') ? $request->file('document') : null;
        
        $contract = $action->handle(
            $request->validated(),
            $user->id,
            $user->currentTeam->id,
            $document
        );

        return Redirect::route('contracts.show', $contract->id)
            ->with('success', 'Contrato criado com sucesso!');
    }

    /**
     * Exibe formulário de edição
     */
    public function edit(Contract $contract): Response
    {
        $this->authorize('update', $contract);

        // Carregar relacionamentos
        $contract->load(['documents', 'alerts', 'invoices.uploader', 'comments.user']);

        return Inertia::render('Contracts/Edit', [
            'contract' => [
                'id' => $contract->id,
                'name' => $contract->name,
                'contract_type' => $contract->contract_type,
                'my_role' => $contract->my_role,
                'contract_object' => $contract->contract_object,
                'contract_number' => $contract->contract_number,
                'contractor' => $contract->contractor,
                'contractor_cpf_cnpj' => $contract->contractor_cpf_cnpj,
                'contracted' => $contract->contracted,
                'contracted_cpf_cnpj' => $contract->contracted_cpf_cnpj,
                'start_date' => $contract->start_date?->format('Y-m-d'),
                'end_date' => $contract->end_date?->format('Y-m-d'),
                'auto_renewal' => $contract->auto_renewal,
                'amount' => $contract->amount,
                'currency' => $contract->currency,
                'payment_terms' => $contract->payment_terms,
                'status' => $contract->status,
                'notes' => $contract->notes,
                // Dados de Nota Fiscal
                'invoice_contact_email' => $contract->invoice_contact_email,
                'invoice_contact_link' => $contract->invoice_contact_link,
                'invoice_system' => $contract->invoice_system,
                'invoice_description' => $contract->invoice_description,
                'invoice_internal_notes' => $contract->invoice_internal_notes,
                'invoice_recipient_name' => $contract->invoice_recipient_name,
                'invoice_recipient_cnpj' => $contract->invoice_recipient_cnpj,
                'invoice_state_registration' => $contract->invoice_state_registration,
                'invoice_recipient_address' => $contract->invoice_recipient_address,
                'invoice_service_code' => $contract->invoice_service_code,
                'invoice_due_day' => $contract->invoice_due_day,
                // Relacionamentos (contagens)
                'documents' => $contract->documents->map(fn($doc) => [
                    'id' => $doc->id,
                    'file_name' => $doc->file_name,
                ]),
                'alerts' => $contract->alerts->map(fn($alert) => [
                    'id' => $alert->id,
                    'alert_type' => $alert->alert_type,
                    'days_before' => $alert->days_before,
                    'is_active' => $alert->is_active,
                    'triggered_at' => $alert->triggered_at?->format('d/m/Y H:i'),
                    'task_id' => $alert->task_id,
                    'send_email' => $alert->send_email,
                    'email_to' => $alert->email_to,
                    'email_cc' => $alert->email_cc,
                ]),
                'invoices' => $contract->invoices->map(fn($invoice) => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
                    'due_date' => $invoice->due_date?->format('Y-m-d'),
                    'amount' => $invoice->amount,
                    'description' => $invoice->description,
                    'has_pdf' => (bool) $invoice->pdf_path,
                    'has_xml' => (bool) $invoice->xml_path,
                    'is_paid' => $invoice->is_paid,
                    'paid_at' => $invoice->paid_at?->format('d/m/Y'),
                    'uploaded_by' => $invoice->uploader ? [
                        'id' => $invoice->uploader->id,
                        'name' => $invoice->uploader->name,
                    ] : null,
                    'is_overdue' => $invoice->isOverdue(),
                    'is_due_today' => $invoice->isDueToday(),
                    'created_at' => $invoice->created_at->format('d/m/Y H:i'),
                ]),
                'comments' => $contract->comments->map(fn($comment) => [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ],
                    'created_at' => $comment->created_at->format('d/m/Y H:i'),
                    'is_author' => $comment->user_id === Auth::id(),
                ]),
            ],
            'report' => $this->calculateReportData($contract),
        ]);
    }

    /**
     * Atualiza contrato
     */
    public function update(UpdateContractRequest $request, Contract $contract, UpdateContractAction $action): RedirectResponse
    {
        $this->authorize('update', $contract);

        $action->handle($contract, $request->validated(), Auth::id());

        return Redirect::route('contracts.show', $contract->id)
            ->with('success', 'Contrato atualizado com sucesso!');
    }

    /**
     * Exclui contrato
     */
    public function destroy(Contract $contract, DeleteContractAction $action): RedirectResponse
    {
        $this->authorize('delete', $contract);

        $action->handle($contract, Auth::id());

        return Redirect::route('contracts.index')
            ->with('success', 'Contrato excluído com sucesso!');
    }

    /**
     * Upload de documento
     */
    public function uploadDocument(Request $request, Contract $contract, UploadContractDocumentAction $action): RedirectResponse
    {
        $this->authorize('update', $contract);

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:20480'], // 20MB
        ]);

        $action->handle($contract, $request->file('document'), Auth::id());

        return Redirect::back()
            ->with('success', 'Documento enviado com sucesso!');
    }

    /**
     * Faz pergunta ao Assistente Jurídico IA
     */
    public function askAssistant(Request $request, Contract $contract, ContractAssistantService $assistant): RedirectResponse
    {
        $this->authorize('view', $contract);

        $request->validate([
            'question' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $response = $assistant->ask($contract, $request->question, Auth::id());

            return Redirect::back()->with('ai_response', $response);
        } catch (\Exception $e) {
            return Redirect::back()->withErrors(['ai_error' => $e->getMessage()]);
        }
    }

    /**
     * Cria tarefa a partir de alerta
     */
    public function createTask(Request $request, ContractAlert $alert, CreateTaskFromContractAction $action): RedirectResponse
    {
        $this->authorize('view', $alert->contract);

        $request->validate([
            'board_list_id' => ['required', 'integer', 'exists:board_lists,id'],
        ]);

        $task = $action->handle($alert, $request->board_list_id);

        return Redirect::back()
            ->with('success', 'Tarefa criada com sucesso!');
    }

    /**
     * Busca e-mail do usuário da integração conectada
     */
    public function getUserEmail(): JsonResponse
    {
        $user = Auth::user();

        // Tentar buscar e-mail da integração Gmail
        $integration = \App\Domain\Integrations\Models\Integration::where('team_id', $user->currentTeam->id)
            ->where('provider', 'gmail')
            ->where('status', 'connected')
            ->first();

        if ($integration && isset($integration->metadata['email'])) {
            return response()->json([
                'email' => $integration->metadata['email'],
                'source' => 'gmail_integration'
            ]);
        }

        // Fallback: e-mail do usuário logado
        return response()->json([
            'email' => $user->email,
            'source' => 'user_account'
        ]);
    }

    /**
     * Cria novo alerta para o contrato
     */
    public function storeAlert(Request $request, Contract $contract, ContractAlertService $alertService): RedirectResponse
    {
        $this->authorize('update', $contract);

        $validated = $request->validate([
            'alert_type' => ['required', 'string', 'in:before_expiration,on_expiration,after_expiration'],
            'days_before' => ['nullable', 'integer', 'min:1', 'max:365'],
            'send_email' => ['boolean'],
            'email_to' => ['nullable', 'required_if:send_email,true', 'email'],
            'email_cc' => ['nullable', 'array'],
            'email_cc.*' => ['email'],
        ]);

        $alert = ContractAlert::create([
            'contract_id' => $contract->id,
            'team_id' => $contract->team_id,
            'alert_type' => $validated['alert_type'],
            'days_before' => $validated['days_before'] ?? null,
            'send_email' => $validated['send_email'] ?? false,
            'email_to' => $validated['email_to'] ?? null,
            'email_cc' => $validated['email_cc'] ?? null,
            'is_active' => true,
        ]);

        return Redirect::back()
            ->with('success', 'Alerta criado com sucesso!');
    }

    /**
     * Atualiza alerta existente
     */
    public function updateAlert(Request $request, ContractAlert $alert, ContractAlertService $alertService): RedirectResponse
    {
        $this->authorize('update', $alert->contract);

        $request->validate([
            'alert_type' => ['required', 'string', 'in:before_expiration,on_expiration,after_expiration'],
            'days_before' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $alert->update([
            'alert_type' => $request->alert_type,
            'days_before' => $request->days_before,
        ]);

        return Redirect::back()
            ->with('success', 'Alerta atualizado com sucesso!');
    }

    /**
     * Remove alerta
     */
    public function destroyAlert(ContractAlert $alert): RedirectResponse
    {
        $this->authorize('update', $alert->contract);

        $alert->delete();

        return Redirect::back()
            ->with('success', 'Alerta removido com sucesso!');
    }

    /**
     * Ativa/Desativa alerta
     */
    public function toggleAlert(ContractAlert $alert, ContractAlertService $alertService): RedirectResponse
    {
        $this->authorize('update', $alert->contract);

        if ($alert->is_active) {
            $alertService->deactivateAlert($alert);
            $message = 'Alerta desativado com sucesso!';
        } else {
            $alertService->reactivateAlert($alert);
            $message = 'Alerta reativado com sucesso!';
        }

        return Redirect::back()
            ->with('success', $message);
    }

    /**
     * Cria tarefa a partir do alerta
     */
    public function createTaskFromAlert(ContractAlert $alert, CreateTaskFromContractAction $action): RedirectResponse
    {
        $this->authorize('view', $alert->contract);

        $user = Auth::user();

        // Buscar primeira lista do primeiro board do team
        $defaultList = \App\Domain\Tasks\Models\BoardList::whereHas('board', function ($query) use ($user) {
            $query->where('team_id', $user->currentTeam->id);
        })->first();

        if (!$defaultList) {
            return Redirect::back()
                ->with('error', 'Nenhum board encontrado. Crie um board primeiro.');
        }

        $task = $action->handle($alert, $defaultList->id);

        // Vincular tarefa ao alerta
        $alert->task_id = $task->id;
        $alert->save();

        return Redirect::back()
            ->with('success', 'Tarefa criada com sucesso!');
    }

    /**
     * Cria novo comentário para o contrato
     */
    public function storeComment(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('view', $contract);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:5000'],
        ]);

        $user = Auth::user();

        \App\Domain\Contracts\Models\ContractComment::create([
            'contract_id' => $contract->id,
            'user_id' => $user->id,
            'team_id' => $contract->team_id,
            'comment' => $validated['comment'],
        ]);

        return Redirect::back()
            ->with('success', 'Comentário adicionado com sucesso!');
    }

    /**
     * Remove comentário
     */
    public function destroyComment(\App\Domain\Contracts\Models\ContractComment $comment): RedirectResponse
    {
        // Apenas o autor ou admin pode deletar
        $user = Auth::user();
        
        if ($comment->user_id !== $user->id) {
            $this->authorize('update', $comment->contract);
        }

        $comment->delete();

        return Redirect::back()
            ->with('success', 'Comentário removido com sucesso!');
    }

    /**
     * Extrai dados do documento com IA
     */
    public function extractData(Request $request, ContractDataExtractionService $extractionService): JsonResponse
    {
        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:20480'], // 20MB
        ]);

        try {
            $user = Auth::user();
            $teamName = $user->currentTeam->name ?? '';
            
            $file = $request->file('document');
            
            // Extrair texto do documento
            $extractedText = $this->extractTextFromFile($file);
            
            if (empty($extractedText)) {
                return response()->json([
                    'error' => 'Não foi possível extrair texto do documento'
                ], 422);
            }

            // Criar objeto temporário ContractDocument para usar o serviço
            $tempDocument = new \App\Domain\Contracts\Models\ContractDocument();
            $tempDocument->extracted_text = $extractedText;

            // Extrair dados com IA (passando nome do team)
            $data = $extractionService->extract($tempDocument, $teamName);

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error('Erro ao extrair dados do contrato: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Erro ao processar documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extrai texto de arquivo PDF ou DOCX
     */
    private function extractTextFromFile($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            if ($extension === 'pdf') {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($file->getRealPath());
                $text = $pdf->getText();
            } elseif (in_array($extension, ['doc', 'docx'])) {
                // Para DOCX, usar library PhpOffice\PhpWord
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($file->getRealPath());
                $text = '';
                
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText() . "\n";
                        }
                    }
                }
            } else {
                return '';
            }

            // Limpar texto
            $text = trim($text);
            $text = preg_replace('/\s+/', ' ', $text);
            
            return $text;
        } catch (\Exception $e) {
            \Log::error('Erro ao extrair texto: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Download de documento do contrato
     */
    public function downloadDocument(ContractDocument $document): StreamedResponse
    {
        // Verificar autorização (documento pertence ao contrato do team do usuário)
        $user = Auth::user();
        if ($document->team_id !== $user->currentTeam->id) {
            abort(403, 'Acesso negado');
        }

        // Verificar se arquivo existe
        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'Arquivo não encontrado');
        }

        // Fazer download
        return Storage::disk('private')->download(
            $document->file_path,
            $document->file_name
        );
    }
    
    /**
     * Busca dados de um CNPJ na API pública
     */
    public function fetchCnpjData(Request $request, CnpjApiService $cnpjService): JsonResponse
    {
        $request->validate([
            'cnpj' => ['required', 'string', 'min:14', 'max:18'],
        ]);
        
        $cnpj = $request->input('cnpj');
        
        // Consultar API
        $data = $cnpjService->consultarCnpj($cnpj);
        
        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'CNPJ não encontrado ou API indisponível',
            ], 404);
        }
        
        // Formatar dados
        $dadosFormatados = $cnpjService->formatarDados($data);
        
        return response()->json([
            'success' => true,
            'data' => $dadosFormatados,
            'raw' => $data, // Dados brutos para salvar no banco
        ]);
    }

    /**
     * Upload de nota fiscal
     */
    public function uploadInvoice(Request $request, Contract $contract, UploadInvoiceAction $action): RedirectResponse
    {
        $this->authorize('update', $contract);

        \Log::info('Iniciando upload de nota fiscal', [
            'contract_id' => $contract->id,
            'has_pdf' => $request->hasFile('pdf_file'),
            'has_xml' => $request->hasFile('xml_file'),
            'data' => $request->only(['invoice_date', 'due_date', 'amount', 'invoice_number', 'description']),
        ]);

        $request->validate([
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'xml_file' => ['nullable', 'file', function ($attribute, $value, $fail) {
                if ($value) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    if ($extension !== 'xml') {
                        $fail('O arquivo deve ter extensão .xml');
                    }
                }
            }, 'max:10240'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'invoice_number' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_paid' => ['nullable', 'boolean'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $user = Auth::user();

        try {
            $invoice = $action->handle(
                $contract,
                $request->only(['invoice_date', 'due_date', 'amount', 'invoice_number', 'description', 'is_paid', 'paid_at']),
                $request->file('pdf_file'),
                $request->file('xml_file'),
                $user->id,
                $user->currentTeam->id
            );

            \Log::info('Nota fiscal salva com sucesso', ['invoice_id' => $invoice->id]);

            return Redirect::back()->with('success', 'Nota fiscal enviada com sucesso!');
        } catch (\Exception $e) {
            \Log::error('Erro ao salvar nota fiscal: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Redirect::back()->withErrors(['error' => 'Erro ao salvar nota fiscal: ' . $e->getMessage()]);
        }
    }

    /**
     * Download do PDF da nota fiscal
     */
    public function downloadInvoicePdf(Invoice $invoice): StreamedResponse
    {
        $user = Auth::user();
        if ($invoice->team_id !== $user->currentTeam->id) {
            abort(403, 'Acesso negado');
        }

        if (!$invoice->pdf_path || !Storage::disk('private')->exists($invoice->pdf_path)) {
            abort(404, 'PDF não encontrado');
        }

        return Storage::disk('private')->download($invoice->pdf_path, 'NF_' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Download do XML da nota fiscal
     */
    public function downloadInvoiceXml(Invoice $invoice): StreamedResponse
    {
        $user = Auth::user();
        if ($invoice->team_id !== $user->currentTeam->id) {
            abort(403, 'Acesso negado');
        }

        if (!$invoice->xml_path || !Storage::disk('private')->exists($invoice->xml_path)) {
            abort(404, 'XML não encontrado');
        }

        return Storage::disk('private')->download($invoice->xml_path, 'NF_' . $invoice->invoice_number . '.xml');
    }

    /**
     * Excluir nota fiscal
     */
    public function destroyInvoice(Invoice $invoice): RedirectResponse
    {
        $user = Auth::user();
        
        // Verificar permissão
        if ($invoice->team_id !== $user->currentTeam->id) {
            abort(403, 'Acesso negado');
        }

        // Deletar arquivos
        if ($invoice->pdf_path) {
            Storage::disk('private')->delete($invoice->pdf_path);
        }
        if ($invoice->xml_path) {
            Storage::disk('private')->delete($invoice->xml_path);
        }

        $invoice->delete();

        return Redirect::back()->with('success', 'Nota fiscal excluída com sucesso!');
    }

    /**
     * Atualizar nota fiscal
     */
    public function updateInvoice(Request $request, Invoice $invoice): RedirectResponse
    {
        $user = Auth::user();
        
        // Verificar permissão
        if ($invoice->team_id !== $user->currentTeam->id) {
            abort(403, 'Acesso negado');
        }

        $request->validate([
            'invoice_number' => ['required', 'string', 'max:100'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_paid' => ['nullable', 'boolean'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $invoice->update([
            'invoice_number' => $request->invoice_number,
            'invoice_date' => $request->invoice_date,
            'due_date' => $request->due_date,
            'amount' => $request->amount,
            'description' => $request->description,
            'is_paid' => $request->is_paid ?? false,
            'paid_at' => $request->paid_at,
        ]);

        return Redirect::back()->with('success', 'Nota fiscal atualizada com sucesso!');
    }

    /**
     * Extrai dados de PDF de nota fiscal usando IA
     */
    public function extractInvoiceDataFromPdf(Request $request, InvoicePdfExtractionService $extractionService): JsonResponse
    {
        $request->validate([
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        try {
            $file = $request->file('pdf_file');
            
            // Extrair dados com IA
            $data = $extractionService->extractFromPdf($file->getRealPath());
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao extrair dados do PDF da NF: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Erro ao processar PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Importa notas fiscais a partir de arquivo CSV
     */
    public function importInvoicesCsv(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('update', $contract);

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // 10MB
        ]);

        $user = Auth::user();
        $file = $request->file('csv_file');
        
        try {
            $content = file_get_contents($file->getRealPath());
            
            // Detectar separador (vírgula ou ponto-e-vírgula)
            $separator = ',';
            if (substr_count($content, ';') > substr_count($content, ',')) {
                $separator = ';';
            }
            
            \Log::info('Detectado separador CSV: ' . $separator);
            
            $handle = fopen($file->getRealPath(), 'r');
            
            // Ler header
            $header = fgetcsv($handle, 0, $separator);
            
            if (!$header) {
                return Redirect::back()->withErrors(['csv' => 'Arquivo CSV vazio ou inválido']);
            }

            // Normalizar header (remover BOM e espaços)
            $header = array_map(function($col) {
                return trim(str_replace("\xEF\xBB\xBF", '', $col));
            }, $header);
            
            \Log::info('Header CSV normalizado:', $header);

            $imported = 0;
            $errors = [];
            $line = 1; // Linha 1 é o header

            while (($data = fgetcsv($handle, 0, $separator)) !== false) {
                $line++;
                
                \Log::info("Linha {$line} dados brutos:", $data);
                
                // Pular linhas vazias
                if (count(array_filter($data)) === 0) {
                    continue;
                }
                
                // Validar se tem o mesmo número de colunas
                if (count($data) !== count($header)) {
                    $errors[] = "Linha {$line}: número de colunas não corresponde ao header (esperado: " . count($header) . ", encontrado: " . count($data) . ")";
                    continue;
                }
                
                // Criar array associativo
                $row = array_combine($header, $data);
                
                \Log::info("Linha {$line} array associativo:", $row);
                
                // Validar campos obrigatórios
                if (empty(trim($row['numero_nf'] ?? '')) || empty(trim($row['valor'] ?? ''))) {
                    $errors[] = "Linha {$line}: número e valor são obrigatórios";
                    continue;
                }

                try {
                    // Converter datas de forma segura
                    $invoiceDate = null;
                    if (!empty(trim($row['data_emissao'] ?? ''))) {
                        $timestamp = strtotime($row['data_emissao']);
                        if ($timestamp !== false) {
                            $invoiceDate = date('Y-m-d', $timestamp);
                        }
                    }
                    
                    $dueDate = null;
                    if (!empty(trim($row['data_vencimento'] ?? ''))) {
                        $timestamp = strtotime($row['data_vencimento']);
                        if ($timestamp !== false) {
                            $dueDate = date('Y-m-d', $timestamp);
                        }
                    }
                    
                    // Normalizar valor (remover pontos de milhar e converter vírgula em ponto)
                    $valor = trim($row['valor']);
                    // Se tiver vírgula, assumir que é decimal europeu (1.500,50)
                    if (strpos($valor, ',') !== false) {
                        $valor = str_replace('.', '', $valor); // Remover pontos de milhar
                        $valor = str_replace(',', '.', $valor); // Converter vírgula em ponto
                    }
                    
                    // Processar campo "pago" (aceita: sim, não, true, false, 1, 0, s, n)
                    $isPaid = false;
                    $paidAt = null;
                    if (isset($row['pago'])) {
                        $pagoValue = strtolower(trim($row['pago']));
                        $isPaid = in_array($pagoValue, ['sim', 's', 'true', '1', 'yes']);
                        
                        // Se está pago e tem data de pagamento, usar
                        if ($isPaid && isset($row['data_pagamento']) && !empty(trim($row['data_pagamento']))) {
                            $timestamp = strtotime($row['data_pagamento']);
                            if ($timestamp !== false) {
                                $paidAt = date('Y-m-d', $timestamp);
                            }
                        }
                    }
                    
                    // Criar nota fiscal
                    \App\Domain\Contracts\Models\Invoice::create([
                        'contract_id' => $contract->id,
                        'team_id' => $contract->team_id,
                        'uploaded_by' => $user->id,
                        'invoice_number' => trim($row['numero_nf']),
                        'invoice_date' => $invoiceDate,
                        'due_date' => $dueDate,
                        'amount' => floatval($valor),
                        'description' => !empty(trim($row['descricao'] ?? '')) ? trim($row['descricao']) : null,
                        'is_paid' => $isPaid,
                        'paid_at' => $paidAt,
                    ]);
                    
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Linha {$line}: {$e->getMessage()}";
                    \Log::error("Erro ao importar linha {$line}: " . $e->getMessage(), ['row' => $row]);
                }
            }
            
            fclose($handle);

            if ($imported > 0) {
                $message = "✓ {$imported} nota(s) fiscal(is) importada(s) com sucesso!";
                if (count($errors) > 0) {
                    $message .= " " . count($errors) . " erro(s) encontrado(s).";
                }
                
                return Redirect::back()->with('success', $message);
            } else {
                return Redirect::back()->withErrors([
                    'csv' => 'Nenhuma nota fiscal foi importada. ' . implode(', ', array_slice($errors, 0, 3))
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao importar CSV de notas fiscais: ' . $e->getMessage());
            
            return Redirect::back()->withErrors([
                'csv' => 'Erro ao processar arquivo CSV: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calcular dados do relatório do contrato
     */
    private function calculateReportData(Contract $contract): array
    {
        // Cálculos financeiros
        $totalInvoiced = $contract->invoices()->sum('amount');
        $totalPaid = $contract->invoices()->where('is_paid', true)->sum('amount');
        $contractAmount = $contract->amount ?? 0;
        
        $liquidatedPercentage = $contractAmount > 0 
            ? ($totalPaid / $contractAmount) * 100 
            : 0;
        
        // Cálculos de tempo
        $today = now();
        $startDate = $contract->start_date;
        $endDate = $contract->end_date;
        
        $totalDays = 0;
        $elapsedDays = 0;
        $remainingDays = 0;
        $timeElapsedPercentage = 0;
        $isExpired = false;
        
        if ($startDate && $endDate) {
            $totalDays = $startDate->diffInDays($endDate);
            $elapsedDays = $startDate->diffInDays($today);
            $remainingDays = max(0, $today->diffInDays($endDate, false));
            
            $timeElapsedPercentage = $totalDays > 0 
                ? min(100, ($elapsedDays / $totalDays) * 100) 
                : 0;
            
            $isExpired = $endDate->isPast();
        }
        
        // Análise de notas fiscais
        $totalInvoices = $contract->invoices()->count();
        $paidInvoices = $contract->invoices()->where('is_paid', true)->count();
        
        $paidInvoicesPercentage = $totalInvoices > 0 
            ? ($paidInvoices / $totalInvoices) * 100 
            : 0;
        
        return [
            'financial' => [
                'contract_amount' => $contractAmount,
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'liquidated_percentage' => round($liquidatedPercentage, 2),
                'remaining_amount' => $contractAmount - $totalPaid,
                'pending_payment' => $totalInvoiced - $totalPaid,
            ],
            'time' => [
                'total_days' => $totalDays,
                'elapsed_days' => min($elapsedDays, $totalDays),
                'remaining_days' => $remainingDays,
                'time_elapsed_percentage' => round($timeElapsedPercentage, 2),
                'is_expired' => $isExpired,
            ],
            'invoices' => [
                'total' => $totalInvoices,
                'paid' => $paidInvoices,
                'unpaid' => $totalInvoices - $paidInvoices,
                'paid_percentage' => round($paidInvoicesPercentage, 2),
            ],
        ];
    }
}

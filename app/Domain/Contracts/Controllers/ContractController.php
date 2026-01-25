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

        if ($request->has('type') && $request->type) {
            $query->where('contract_type', $request->type);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('contract_type', 'like', "%{$request->search}%");
            });
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
                'documents_count' => $contract->documents->count(),
                'created_by' => $contract->user->name,
                'created_at' => $contract->created_at->format('d/m/Y'),
            ];
        });

        return Inertia::render('Contracts/Index', [
            'contracts' => $contracts,
            'filters' => $request->only(['status', 'type', 'search']),
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
                    'uploaded_by' => $invoice->uploader->name,
                    'uploaded_at' => $invoice->created_at->format('d/m/Y H:i'),
                ]),
                'created_by' => $contract->user->name,
                'created_at' => $contract->created_at->format('d/m/Y H:i'),
            ],
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
            ],
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
     * Gerencia alertas do contrato
     */
    public function manageAlerts(Request $request, Contract $contract, ContractAlertService $alertService): RedirectResponse
    {
        $this->authorize('update', $contract);

        $request->validate([
            'alert_type' => ['required', 'string', 'in:before_expiration,on_expiration,after_expiration'],
            'days_before' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $alertService->createAlert(
            $contract,
            $request->alert_type,
            $request->days_before
        );

        return Redirect::back()
            ->with('success', 'Alerta criado com sucesso!');
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
            'xml_file' => ['nullable', 'file', 'mimes:xml', 'max:10240'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        // Pelo menos um arquivo deve ser enviado
        if (!$request->hasFile('pdf_file') && !$request->hasFile('xml_file')) {
            \Log::warning('Tentativa de upload sem arquivos');
            return Redirect::back()->withErrors(['file' => 'Envie ao menos um arquivo (PDF ou XML)']);
        }

        $user = Auth::user();

        try {
            $invoice = $action->handle(
                $contract,
                $request->only(['invoice_date', 'due_date', 'amount', 'invoice_number', 'description']),
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
}

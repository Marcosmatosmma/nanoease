<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Controllers;

use App\Domain\Contracts\Models\Contract;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class PublicContractController extends Controller
{
    /**
     * Exibe a tela de login (entrada do CNPJ).
     */
    public function index()
    {
        return Inertia::render('Portal/InvoiceUpload/Login');
    }

    /**
     * Processa o CNPJ e lista os contratos ativos.
     */
    public function search(Request $request)
    {
        $request->validate([
            'cnpj' => 'required|string|size:18', // Formato 00.000.000/0000-00
        ]);

        $cnpj = $request->input('cnpj');

        // Remove caracteres não numéricos para a busca
        $cleanCnpj = preg_replace('/\D/', '', $cnpj);

        // Busca contratos onde o usuário é o CONTRATANTE e o fornecedor tem esse CNPJ
        // Procura tanto o CNPJ limpo quanto o formatado para garantir compatibilidade
        $contracts = Contract::query()
            ->where('my_role', 'contratante') // Eu sou o contratante
            ->where(function($q) use ($cleanCnpj, $cnpj) {
                $q->where('contracted_cpf_cnpj', $cleanCnpj)
                  ->orWhere('contracted_cpf_cnpj', $cnpj);
            })
            ->where('status', 'ativo')
            ->select(['id', 'name', 'contract_number', 'invoice_due_day', 'invoice_description'])
            ->get();

        return Inertia::render('Portal/InvoiceUpload/List', [
            'contracts' => $contracts,
            'cnpj' => $cnpj,
        ]);
    }

    /**
     * Processa o upload da Nota Fiscal.
     */
    public function storeInvoice(Request $request, Contract $contract)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,xml|max:10240', // Max 10MB
            'cnpj' => 'required|string', 
            'invoice_number' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
        ]);

        // Normalizar CNPJs para comparação
        $requestCnpjClean = preg_replace('/\D/', '', $request->input('cnpj'));
        $contractCnpjClean = preg_replace('/\D/', '', $contract->contracted_cpf_cnpj);

        if ($contractCnpjClean !== $requestCnpjClean) {
            abort(403, 'Acesso não autorizado a este contrato.');
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        
        // Salva o arquivo - Organize by Year/Month
        $path = $file->storeAs(
            'invoices/' . date('Y/m'),
            uniqid() . '_' . $file->getClientOriginalName(),
            'private' // Secure storage
        );
        
        // Determinar campos de path
        $pdfPath = $extension === 'pdf' ? $path : null;
        $xmlPath = $extension === 'xml' ? $path : null;

        // Criar registro na tabela invoices
        // Importante: uploaded_by é obrigatório na tabela (foreign key users).
        // Como o fornecedor é externo, vamos usar o ID do dono do contrato para que ele veja a NF.
        $contract->invoices()->create([
            'team_id' => $contract->team_id,
            'uploaded_by' => $contract->user_id, // Atribui ao dono para visibilidade
            'pdf_path' => $pdfPath,
            'xml_path' => $xmlPath,
            'invoice_number' => $request->input('invoice_number'),
            'amount' => $request->input('amount'),
            'invoice_date' => $request->input('invoice_date'),
            'due_date' => $request->input('due_date'),
            'description' => $request->input('description'),
            'is_paid' => false, // Padrão
        ]);

        // Registrar no Histórico do Contrato
        $contract->history()->create([
            'team_id' => $contract->team_id,
            'user_id' => $contract->user_id, // Atribui ao dono, já que o fornecedor é externo
            'event_type' => 'invoice_uploaded',
            'description' => 'Nota fiscal enviada pelo fornecedor',
            'metadata' => [
                'invoice_number' => $request->input('invoice_number'),
                'amount' => $request->input('amount'),
                'file_name' => $file->getClientOriginalName(),
                'sender_cnpj' => $requestCnpjClean, // Adiciona o CNPJ de quem enviou
            ],
        ]);

        return back()->with('success', 'Nota Fiscal enviada com sucesso!');
    }
}

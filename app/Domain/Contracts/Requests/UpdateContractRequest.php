<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:500'],
            'my_role' => ['nullable', 'string', 'in:contratante,contratado'],
            'contract_type' => ['nullable', 'string', 'max:100'],
            'contract_object' => ['nullable', 'string', 'max:1000'],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'contractor' => ['nullable', 'string', 'max:255'],
            'contractor_cpf_cnpj' => ['nullable', 'string', 'max:18'],
            'contracted' => ['nullable', 'string', 'max:255'],
            'contracted_cpf_cnpj' => ['nullable', 'string', 'max:18'],
            'parties_involved' => ['nullable', 'array'],
            'parties_involved.*' => ['string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'auto_renewal' => ['nullable', 'boolean'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_terms' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'string', Rule::in(['ativo', 'vencido', 'encerrado'])],
            'ai_summary' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:5000'],
            // Campos de Nota Fiscal
            'invoice_contact_email' => ['nullable', 'email', 'max:255'],
            'invoice_contact_link' => ['nullable', 'url', 'max:500'],
            'invoice_system' => ['nullable', 'string', 'max:255'],
            'invoice_description' => ['nullable', 'string', 'max:2000'],
            'invoice_internal_notes' => ['nullable', 'string', 'max:2000'],
            'invoice_recipient_name' => ['nullable', 'string', 'max:255'],
            'invoice_recipient_cnpj' => ['nullable', 'string', 'max:18'],
            'invoice_state_registration' => ['nullable', 'string', 'max:50'],
            'invoice_recipient_address' => ['nullable', 'string', 'max:500'],
            'invoice_service_code' => ['nullable', 'string', 'max:50'],
            'invoice_due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do contrato.',
            'name.max' => 'O nome não pode ter mais de 500 caracteres.',
            'end_date.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
            'amount.numeric' => 'O valor deve ser um número válido.',
            'amount.min' => 'O valor não pode ser negativo.',
            'currency.size' => 'A moeda deve ter exatamente 3 caracteres (ex: BRL, USD).',
            'status.in' => 'Status inválido. Use: ativo, vencido ou encerrado.',
        ];
    }
}

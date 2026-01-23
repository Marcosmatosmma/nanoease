<?php

declare(strict_types=1);

namespace App\Domain\Automations\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validação para criação/edição de envio em massa
 * Valida todos os dados do formulário
 */
class StoreEmailMassSendRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'send_type' => ['required', 'in:now,scheduled'],
            'scheduled_at' => ['required_if:send_type,scheduled', 'nullable', 'date', 'after:' . now()->subMinute()],
            'subject' => ['required', 'string', 'min:1', 'max:500'],
            'body' => ['required', 'string', 'min:10'],
            'recipient_source' => ['required', 'in:csv,sheets'],
            'email_column' => ['required', 'string'],
            'recipients_data' => ['required', 'array'],
            'recipients_data.columns' => ['required', 'array', 'min:1'],
            'recipients_data.data' => ['required', 'array', 'min:1'],
            'recipients_data.validation' => ['required', 'array'],
            'recipients_data.validation.valid' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Mensagens de erro personalizadas
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Digite um nome para identificar este envio.',
            'name.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'send_type.required' => 'Escolha quando enviar.',
            'scheduled_at.required_if' => 'Selecione a data e hora do envio agendado.',
            'scheduled_at.after' => 'A data deve ser futura.',
            'subject.required' => 'Digite o assunto do email.',
            'body.required' => 'Digite o corpo do email.',
            'body.min' => 'O corpo do email deve ter pelo menos 10 caracteres.',
            'email_column.required' => 'Selecione a coluna que contém os emails.',
            'recipients_data.required' => 'Faça upload do arquivo CSV com destinatários.',
            'recipients_data.data.min' => 'O arquivo CSV deve conter pelo menos 1 destinatário.',
            'recipients_data.validation.valid.min' => 'É necessário ter pelo menos 1 email válido.',
        ];
    }
}

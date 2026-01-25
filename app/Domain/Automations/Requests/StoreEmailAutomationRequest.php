<?php

declare(strict_types=1);

namespace App\Domain\Automations\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreEmailAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        $rules = [
            'trigger_type_id' => ['required', 'integer', 'exists:automation_trigger_types,id'],
            'rule' => ['required', 'string', 'min:1'],
            'action_type' => ['required', 'string', Rule::in(['organizar', 'encaminhar', 'responder', 'tarefa'])],
            'action_config' => ['nullable', 'array'],
            'action_config.forward_to' => ['required_if:action_type,encaminhar', 'array', 'min:1'],
            'action_config.forward_to.*' => ['email:rfc,dns'],
            'action_config.reply_subject' => ['required_if:action_type,responder', 'string', 'max:500'],
            'action_config.reply_body' => ['required_if:action_type,responder', 'string', 'min:1'],
            'action_config.board_list_id' => ['required_if:action_type,tarefa', 'integer', 'exists:board_lists,id'],
            'action_config.assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'action_config.include_sender' => ['nullable', 'boolean'],
            'action_config.include_date' => ['nullable', 'boolean'],
            'action_config.auto_due_date' => ['nullable', 'boolean'],
            'action_config.include_email_body' => ['nullable', 'boolean'],
            'gmail_label' => ['nullable', 'required_if:action_type,organizar', 'string', 'max:255'],
            'automation' => ['nullable', 'integer'],
        ];

        // Buscar o trigger type para validação condicional
        $triggerTypeId = $this->input('trigger_type_id');
        if ($triggerTypeId) {
            $triggerType = \App\Domain\Automations\Models\AutomationTriggerType::find($triggerTypeId);
            
            if ($triggerType && $triggerType->key === 'sender_exact') {
                $rules['rule'][] = 'email:rfc,dns';
            }
            
            if ($triggerType && $triggerType->key === 'sender_domain') {
                $rules['rule'][] = 'regex:/^@?([a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,})$/i';
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'rule.required' => 'A condição não pode estar vazia.',
            'rule.email' => 'Informe um e-mail válido (ex: usuario@dominio.com)',
            'rule.regex' => 'Informe um domínio válido (ex: @empresa.com ou empresa.com)',
            'trigger_type_id.required' => 'Selecione um tipo de condição.',
            'trigger_type_id.exists' => 'Tipo de condição inválido.',
            'action_config.reply_subject.required_if' => 'Informe o assunto da resposta.',
            'action_config.reply_body.required_if' => 'Digite o corpo da resposta.',
            'action_config.board_list_id.required_if' => 'Selecione uma lista para criar as tarefas.',
            'action_config.board_list_id.exists' => 'Lista inválida ou não encontrada.',
            'action_config.assigned_to.exists' => 'Usuário atribuído inválido.',
            'gmail_label.required_if' => 'Informe o nome da label no Gmail.',
        ];
    }
}

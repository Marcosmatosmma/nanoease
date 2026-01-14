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
        ];
    }
}

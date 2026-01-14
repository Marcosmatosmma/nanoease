<?php

declare(strict_types=1);

namespace App\Domain\Automations\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SimulateEmailAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'rule' => ['required', 'string', 'min:6'],
            'action_type' => ['required', 'string', Rule::in(['organizar', 'encaminhar', 'responder', 'tarefa'])],
            'action_config' => ['nullable', 'array'],
            'action_config.forward_to' => ['required_if:action_type,encaminhar', 'array', 'min:1'],
            'action_config.forward_to.*' => ['email:rfc,dns'],
        ];
    }
}

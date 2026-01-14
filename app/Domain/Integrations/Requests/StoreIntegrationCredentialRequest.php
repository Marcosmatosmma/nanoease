<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreIntegrationCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'redirect_uri' => ['required', 'url'],
        ];
    }
}

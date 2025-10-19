<?php

namespace App\Modules\Clients\Http\Requests;

use App\Modules\Shared\Http\BaseFormRequest;

class StoreClientRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true; // Add policy-based authorization later
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];
    }
}

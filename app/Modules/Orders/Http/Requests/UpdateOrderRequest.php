<?php

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Shared\Http\BaseFormRequest;

class UpdateOrderRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['sometimes', 'required', 'integer', 'exists:clients,id'],
            'location_id' => ['sometimes', 'required', 'integer', 'exists:locations,id'],
            'fuel_liters' => ['sometimes', 'required', 'integer', 'min:100'],
            'window_start' => ['nullable', 'date'],
            'window_end' => ['nullable', 'date', 'after:window_start'],
        ];
    }
}

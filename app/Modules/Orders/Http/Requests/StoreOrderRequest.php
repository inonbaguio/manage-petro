<?php

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Shared\Http\BaseFormRequest;

class StoreOrderRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'fuel_liters' => ['required', 'integer', 'min:100'],
            'window_start' => ['nullable', 'date'],
            'window_end' => ['nullable', 'date', 'after:window_start'],
        ];
    }
}

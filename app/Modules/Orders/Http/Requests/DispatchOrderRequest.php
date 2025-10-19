<?php

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Shared\Http\BaseFormRequest;

class DispatchOrderRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}

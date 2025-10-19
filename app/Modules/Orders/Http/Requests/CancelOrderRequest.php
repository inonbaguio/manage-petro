<?php

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Shared\Http\BaseFormRequest;

class CancelOrderRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

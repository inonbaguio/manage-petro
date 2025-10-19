<?php

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Shared\Http\BaseFormRequest;

class ScheduleOrderRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'truck_id' => ['required', 'integer', 'exists:delivery_trucks,id'],
        ];
    }
}

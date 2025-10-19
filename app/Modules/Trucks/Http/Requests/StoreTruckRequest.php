<?php

namespace App\Modules\Trucks\Http\Requests;

use App\Modules\Shared\Http\BaseFormRequest;

class StoreTruckRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plate_no' => ['required', 'string', 'max:50'],
            'tank_capacity_l' => ['required', 'integer', 'min:1000', 'max:50000'],
            'active' => ['boolean'],
        ];
    }
}

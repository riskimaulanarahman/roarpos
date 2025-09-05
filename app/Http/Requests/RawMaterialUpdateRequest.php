<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RawMaterialUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'sku' => ['sometimes','string','max:50','unique:raw_materials,sku,'.$this->route('id')],
            'name' => ['sometimes','string','max:255'],
            'unit' => ['sometimes','in:g,ml,pcs,kg,l'],
            'unit_cost' => ['sometimes','numeric','min:0'],
            'stock_qty' => ['sometimes','numeric','min:0'],
            'min_stock' => ['sometimes','numeric','min:0'],
        ];
    }
}


<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RawMaterialStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'sku' => ['required','string','max:50','unique:raw_materials,sku'],
            'name' => ['required','string','max:255'],
            'unit' => ['required','in:g,ml,pcs,kg,l'],
            'unit_cost' => ['required','numeric','min:0'],
            'stock_qty' => ['nullable','numeric','min:0'],
            'min_stock' => ['nullable','numeric','min:0'],
        ];
    }
}


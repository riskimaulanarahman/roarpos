<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RawMaterialUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('sku') && trim((string) $this->input('sku')) === '') {
            $this->merge(['sku' => null]);
        }
        if ($this->has('min_stock') && $this->input('min_stock') === '') {
            $this->merge(['min_stock' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'sku' => ['sometimes','nullable','string','max:50','unique:raw_materials,sku,'.$this->route('id')],
            'name' => ['sometimes','string','max:255'],
            'unit' => ['sometimes','in:g,ml,pcs,kg,l'],
            'min_stock' => ['sometimes','numeric','min:0'],
        ];
    }
}

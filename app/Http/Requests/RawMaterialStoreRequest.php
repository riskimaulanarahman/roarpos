<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RawMaterialStoreRequest extends FormRequest
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
            'sku' => ['nullable','string','max:50','unique:raw_materials,sku'],
            'name' => ['required','string','max:255'],
            'unit' => ['required','in:g,ml,pcs,kg,l'],
            'min_stock' => ['nullable','numeric','min:0'],
        ];
    }
}

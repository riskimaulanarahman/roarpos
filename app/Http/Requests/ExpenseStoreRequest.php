<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $rawItems = $this->input('items');
        if (is_string($rawItems)) {
            $decoded = json_decode($rawItems, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['items' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'date' => ['required','date'],
            'amount' => ['nullable','numeric','min:0'],
            'category_id' => ['nullable','exists:expense_categories,id'],
            'vendor' => ['nullable','string'],
            'notes' => ['nullable','string'],
            'attachment' => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:5120'],
            'items' => ['required','array','min:1'],
            'items.*.raw_material_id' => ['nullable','exists:raw_materials,id'],
            'items.*.description' => ['nullable','string','max:255'],
            'items.*.unit' => ['nullable','string','max:50'],
            'items.*.qty' => ['required','numeric','min:0.0001'],
            'items.*.item_price' => ['nullable','numeric','min:0'],
            'items.*.unit_cost' => ['nullable','numeric','min:0'],
            'items.*.notes' => ['nullable','string'],
        ];
    }
}

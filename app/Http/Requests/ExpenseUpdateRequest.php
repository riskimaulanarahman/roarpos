<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'date' => ['sometimes','date'],
            'amount' => ['sometimes','numeric','min:0.01'],
            'category_id' => ['nullable','exists:expense_categories,id'],
            'vendor' => ['nullable','string'],
            'notes' => ['nullable','string'],
        ];
    }
}


<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'date' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'category_id' => ['nullable','exists:expense_categories,id'],
            'vendor' => ['nullable','string'],
            'notes' => ['nullable','string'],
        ];
    }
}


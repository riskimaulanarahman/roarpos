<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProduceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'batches' => ['required','integer','min:1'],
            'notes' => ['nullable','string']
        ];
    }
}


<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePledgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pledger_name' => 'required|string|min:2|max:255',
            'pledger_email' => 'required|email|max:255',
            'amount' => 'required|numeric|min:0.01',
        ];
    }

    public function messages(): array
    {
        return [
            'pledger_name.required' => 'Le nom est requis',
            'pledger_name.min' => 'Le nom doit contenir au moins 2 caractères',
            'pledger_email.required' => 'L\'email est requis',
            'pledger_email.email' => 'L\'email doit être valide',
            'amount.required' => 'Le montant est requis',
            'amount.numeric' => 'Le montant doit être un nombre',
            'amount.min' => 'Le montant doit être supérieur à 0',
        ];
    }
}

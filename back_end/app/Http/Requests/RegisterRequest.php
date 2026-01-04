<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:191|unique:users',
            'password' => 'required|string|min:8',
            'photo_profil' => 'required|image|mimes:jpeg,jpg,png,svg|max:2048',
            'etablissement' => 'required|string|max:191',
            'filiere' => 'required|string|in:GL,GLT,SWE,MVC,LTM',
            'niveau' => 'required|string|in:1,2',
        ];
    }
}

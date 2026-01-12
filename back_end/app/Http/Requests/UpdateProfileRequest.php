<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // L'autorisation est gérée par la Policy ou le controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        
        return [
            'nom' => 'required|string|max:191|unique:users,nom,' . $user->id,
            'photo_profil' => 'nullable|image|mimes:jpeg,jpg,png,svg|max:2048',
            'bio' => 'nullable|string',
        ];
    }
}

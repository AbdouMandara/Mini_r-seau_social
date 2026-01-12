<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Pour donner des règles à mes champs donc comment ils seront etc ...
    public function rules(): array
    {
        return [
            'description' => 'required|string|max:100',
            'img_post' => 'nullable|image|mimes:jpeg,jpg,png,svg|max:2048',
            'allow_comments' => 'boolean',
            'tag' => 'required|string|max:50',
        ];
    }
}

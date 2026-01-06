<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:100',
            'img_post' => 'nullable|image|mimes:jpeg,jpg,png,svg|max:2048',
            'allow_comments' => 'boolean',
            'tag' => 'required|string|max:50',
            'filiere' => 'required|string|in:GL,GLT,SWE,MVC,LTM',
            'niveau' => 'required|string|in:1,2',
            'matiere' => 'nullable|string|max:191',
        ];
    }
}

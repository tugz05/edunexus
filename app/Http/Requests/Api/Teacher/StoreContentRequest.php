<?php

namespace App\Http\Requests\Api\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'teacher';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:video,pdf,link,quiz'],
            'url' => ['required', 'string', 'url', 'max:500'],
            'subject' => ['required', 'string', 'max:255'],
            'difficulty' => ['required', 'string', 'in:Beginner,Intermediate,Advanced'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:content_tags,id'],
        ];
    }
}

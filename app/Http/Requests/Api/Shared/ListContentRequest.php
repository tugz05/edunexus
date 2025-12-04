<?php

namespace App\Http\Requests\Api\Shared;

use Illuminate\Foundation\Http\FormRequest;

class ListContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['nullable', 'string', 'in:Beginner,Intermediate,Advanced'],
            'tag' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:video,pdf,link,quiz'],
        ];
    }
}

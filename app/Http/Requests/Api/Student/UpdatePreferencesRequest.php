<?php

namespace App\Http\Requests\Api\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grade_level' => ['nullable', 'string', 'max:50'],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => ['string', 'max:100'],
            'preferred_difficulty' => ['nullable', 'in:Beginner,Intermediate,Advanced'],
            'learning_style' => ['nullable', 'in:visual,reading,practice,mixed'],
            'goals' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

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
        $type = $this->input('type');
        
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:video,pdf,link,quiz'],
            'subject' => ['required', 'string', 'max:255'],
            'difficulty' => ['required', 'string', 'in:Beginner,Intermediate,Advanced'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:content_tags,id'],
            'file' => ['nullable', 'file', 'max:10240'], // Max 10MB
        ];

        // URL is required for link and quiz types, or if no file is uploaded
        if (in_array($type, ['link', 'quiz'])) {
            $rules['url'] = ['required', 'string', 'url', 'max:500'];
        } else {
            // For video and pdf, either URL or file is required
            $rules['url'] = ['nullable', 'string', 'url', 'max:500'];
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize tags to always be an array
        if ($this->has('tags')) {
            $tags = $this->input('tags');
            if (is_string($tags) && $tags === '[]') {
                $this->merge(['tags' => []]);
            } elseif (!is_array($tags) && !empty($tags)) {
                // If single tag is sent as string/integer, convert to array
                $this->merge(['tags' => [(int) $tags]]);
            } elseif (is_array($tags)) {
                // Ensure all values are integers
                $this->merge(['tags' => array_map('intval', array_filter($tags))]);
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $url = $this->input('url');
            $file = $this->file('file');

            // For video and pdf types, require either URL or file
            if (in_array($type, ['video', 'pdf'])) {
                if (empty($url) && !$file) {
                    $validator->errors()->add('url', 'Either URL or file upload is required for ' . $type . ' content.');
                    $validator->errors()->add('file', 'Either URL or file upload is required for ' . $type . ' content.');
                }
            }
        });
    }
}

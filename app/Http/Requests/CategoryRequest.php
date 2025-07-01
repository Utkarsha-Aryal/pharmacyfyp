<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
     public function rules(): array
    {
        $rules = [
            'name' => 'required|string|min:2|max:50',
            'order_number' => 'required|string|min:1|max:50',
        ];
        if (!$this->has('id')) {
            $rules['image'] = 'required|image|mimes:jpg,jpeg,png|max:512';
        } else {
            $rules['image'] = 'nullable|image|mimes:jpg,jpeg,png|max:512';
        }
        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'The category name field is required.',
            'name.string' => 'The category name must be a valid string.',
            'name.min' => 'The category name must not be less than 2 characters.',
            'name.max' => 'The category name must not exceed 50 characters.',

            'images.required' => 'The image field is required.',
            'images.file' => 'The image must be a file.',

            'images.mimes' => 'The image must be a file of type: JPG, PNG AND JPEG',
            'images.max' => 'The image size must not exceed 512MB.',
        ];
    }
}

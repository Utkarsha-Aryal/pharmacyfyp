<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitRequest extends FormRequest
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
            'unit_name' => 'required|string|min:2|max:50',
            'description' => 'nullable|string|max:255',
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'unit_name.required' => 'The unit name field is required.',
            'unit_name.string' => 'The unit name must be a valid string.',
            'unit_name.min' => 'The unit name must not be less than 2 characters.',
            'unit_name.max' => 'The unit name must not exceed 50 characters.',

            'description.string' => 'The description must be a valid string.',
            'description.max' => 'The description must not exceed 255 characters.',
        ];
    }
}

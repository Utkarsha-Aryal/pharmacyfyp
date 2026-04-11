<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
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
    // Company master now keeps type and default CC rate instead of display order and image.
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:50',
            'company_type' => 'required|in:domestic,foreign',
            'default_cc_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'The company name field is required.',
            'name.string' => 'The company name must be a valid string.',
            'name.min' => 'The company name must not be less than 2 characters.',
            'name.max' => 'The company name must not exceed 50 characters.',
            'company_type.required' => 'Please choose company type.',
            'company_type.in' => 'Company type must be Domestic or Foreign.',
            'default_cc_rate.numeric' => 'Default CC Rate must be numeric.',
            'default_cc_rate.min' => 'Default CC Rate cannot be negative.',
            'default_cc_rate.max' => 'Default CC Rate cannot be greater than 100.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TransferRequest extends FormRequest
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
        return [
            'value' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999999.99',
            ],
            'payer' => [
                'required',
                'integer',
                'exists:wallets,id',
                'different:payee',
            ],
            'payee' => [
                'required',
                'integer',
                'exists:wallets,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'value.required' => 'The transfer value is required.',
            'value.numeric' => 'The transfer value must be a number.',
            'value.min' => 'The transfer value must be at least 0.01.',
            'value.max' => 'The transfer value is too large.',
            'payer.required' => 'The payer ID is required.',
            'payer.integer' => 'The payer ID must be an integer.',
            'payer.exists' => 'The selected payer does not exist.',
            'payer.different' => 'The payer and payee must be different.',
            'payee.required' => 'The payee ID is required.',
            'payee.integer' => 'The payee ID must be an integer.',
            'payee.exists' => 'The selected payee does not exist.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderFiltersRequest extends FormRequest
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
            'destination' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:requested,approved,cancelled'],

            'departure_date' => ['nullable', 'array'],
            'departure_date.from' => ['required_with:departure_date', 'date'],
            'departure_date.to' => ['required_with:departure_date', 'date', 'after_or_equal:departure_date.from'],

            'return_date' => ['nullable', 'array'],
            'return_date.from' => ['required_with:return_date', 'date'],
            'return_date.to' => ['required_with:return_date', 'date', 'after_or_equal:return_date.from'],
        ];
    }
}

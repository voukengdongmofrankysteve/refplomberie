<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnicianRequestRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:255'],
            'service' => ['required', 'string', Rule::in(config('shop.services'))],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'preferred_time' => ['nullable', 'string', 'max:10'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_name' => 'nom',
            'customer_phone' => 'téléphone',
            'address' => 'adresse',
            'service' => 'service',
            'preferred_date' => 'date souhaitée',
            'preferred_time' => 'heure',
            'description' => 'description',
        ];
    }
}

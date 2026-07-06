<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_address' => ['required', 'string'],
            'phone' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'payment_method' => ['sometimes', 'string', 'in:cod,midtrans'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use PaulAdams985\Core\Types\Tenant;

class UserExistsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower((string) $this->email),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            // Optional: check a specific tenant directly instead of fanning
            // out across every joinable tenant. Used to reach tenants that
            // are normally excluded from the fan-out (e.g. isolated ones).
            'tenant' => ['sometimes', 'string', Rule::enum(Tenant::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'Email cannot exceed 255 characters.',
            'tenant.enum' => 'Please provide a valid tenant.',
        ];
    }
}

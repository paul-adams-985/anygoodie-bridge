<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use PaulAdams985\Core\Types\Tenant;

class AppVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => 'required|in:ios,android',
            'version' => 'sometimes|string',
            'tenant' => ['sometimes', 'string', Rule::enum(Tenant::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.required' => 'Platform is required.',
            'platform.in' => 'Platform must be either ios or android.',
            'tenant.enum' => 'Please provide a valid tenant.',
        ];
    }
}

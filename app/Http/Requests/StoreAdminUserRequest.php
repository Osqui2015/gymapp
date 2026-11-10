<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\RolePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nick' => ['required', 'string', 'max:255', 'unique:users,nick'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', 'string', Rule::in(RolePermissions::ROLES)],
            'trainer_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}

<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::baseRules();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return self::attributeNames();
    }

    /**
     * @return array<string, string>
     */
    public static function attributeNames(): array
    {
        return [
            'name' => __('auth.fields.name'),
            'email' => __('auth.fields.email'),
            'password' => __('auth.fields.password'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return self::messageLines();
    }

    /**
     * @return array<string, string>
     */
    public static function messageLines(): array
    {
        return [
            'email.unique' => __('auth.validation.email_unique'),
        ];
    }
}

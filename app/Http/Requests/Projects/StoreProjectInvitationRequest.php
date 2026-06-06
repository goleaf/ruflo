<?php

namespace App\Http\Requests\Projects;

use App\Rules\Projects\ProjectInvitationExpiryDays;
use App\Rules\Projects\ProjectInviteRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectInvitationRequest extends FormRequest
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
            'inviteRole' => ['required', 'string', new ProjectInviteRole],
            'inviteExpiresInDays' => ['required', new ProjectInvitationExpiryDays],
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
            'inviteRole' => __('todos.collaboration.invites.fields.role'),
            'inviteExpiresInDays' => __('todos.collaboration.invites.fields.expires_in_days'),
        ];
    }
}

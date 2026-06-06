<?php

namespace App\Http\Requests\Todos;

use App\Rules\Todos\TodoCommentBody;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreTodoCommentRequest extends FormRequest
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
     * @return array<string, list<ValidationRule|string>>
     */
    public static function baseRules(): array
    {
        return [
            'body' => ['required', 'string', new TodoCommentBody],
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
            'body' => __('todos.comments.fields.body'),
        ];
    }
}

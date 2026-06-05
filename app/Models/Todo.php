<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use App\Policies\TodoPolicy;
use Database\Factories\TodoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A private task owned by a single user (their workspace).
 *
 * Mass assignment is restricted to user-controllable fields only. Ownership
 * (`user_id`) is never fillable and must be assigned through the owning
 * relationship in an action, never from request input.
 */
#[Fillable(['title', 'is_completed'])]
#[UsePolicy(TodoPolicy::class)]
class Todo extends Model
{
    /** @use HasFactory<TodoFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_completed' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
        ];
    }
}

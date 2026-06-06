<?php

namespace Database\Factories;

use App\Models\TodoComment;
use App\Models\TodoCommentMention;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TodoCommentMention>
 */
class TodoCommentMentionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'todo_comment_id' => fn (array $attributes) => TodoComment::factory()->state([
                'user_id' => $attributes['user_id'],
            ]),
            'mentioned_user_id' => User::factory(),
            'handle' => fake()->unique()->slug(2),
        ];
    }

    public function forComment(TodoComment $comment): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $comment->user_id,
            'todo_comment_id' => $comment->id,
        ]);
    }

    public function mentionedUser(User $user, ?string $handle = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'mentioned_user_id' => $user->id,
            'handle' => $handle ?? str($user->name)->slug()->value(),
        ]);
    }
}

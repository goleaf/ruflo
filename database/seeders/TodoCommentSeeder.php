<?php

namespace Database\Seeders;

use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TodoCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'demo'])) {
            return;
        }

        $avery = User::query()
            ->where('email', (string) config('demo.login_panel.users.0.email', 'test@example.com'))
            ->first();
        $morgan = User::query()
            ->where('email', (string) config('demo.login_panel.users.1.email', 'second@example.com'))
            ->first();

        if (! $avery instanceof User || ! $morgan instanceof User) {
            return;
        }

        DB::transaction(function () use ($avery, $morgan): void {
            $this->seedThread($avery, $morgan, 'Review the current flow', [
                [
                    'author' => $avery,
                    'body' => 'I tightened the next action and left the remaining review point in the checklist.',
                ],
                [
                    'author' => $morgan,
                    'body' => 'Shared note: I can review the flow after the owner confirms the final decision point.',
                    'edited_at' => now()->subMinutes(15),
                ],
                [
                    'author' => $avery,
                    'body' => 'Deleted placeholder for an outdated implementation note.',
                    'deleted_at' => now()->subMinutes(5),
                ],
            ]);

            $this->seedThread($morgan, $avery, 'Send the overdue report', [
                [
                    'author' => $morgan,
                    'body' => 'The overdue report is ready for final numbers before it goes out.',
                ],
                [
                    'author' => $avery,
                    'body' => 'Shared manager note: confirm the audience and keep the summary short.',
                ],
            ]);
        });
    }

    /**
     * @param  list<array{author: User, body: string, edited_at?: mixed, deleted_at?: mixed}>  $comments
     */
    private function seedThread(User $owner, User $sharedAuthor, string $taskTitle, array $comments): void
    {
        $todo = Todo::query()
            ->where('user_id', $owner->id)
            ->where('title', $taskTitle)
            ->first();

        if (! $todo instanceof Todo) {
            return;
        }

        foreach ($comments as $comment) {
            $author = $comment['author'];

            if (! $author instanceof User) {
                $author = $sharedAuthor;
            }

            $todoComment = TodoComment::withTrashed()
                ->where('todo_id', $todo->id)
                ->where('author_id', $author->id)
                ->where('body', $comment['body'])
                ->first() ?? new TodoComment;

            $todoComment->forceFill([
                'todo_id' => $todo->id,
                'author_id' => $author->id,
                'body' => $comment['body'],
                'user_id' => $todo->user_id,
                'edited_at' => $comment['edited_at'] ?? null,
                'deleted_at' => $comment['deleted_at'] ?? null,
            ])->save();
        }
    }
}

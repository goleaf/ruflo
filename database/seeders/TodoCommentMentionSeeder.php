<?php

namespace Database\Seeders;

use App\Actions\Todos\SyncTodoCommentMentions;
use App\Models\TodoComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class TodoCommentMentionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'demo'])) {
            return;
        }

        TodoComment::query()
            ->with(['author', 'user'])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunkById(100, function ($comments): void {
                $comments->each(function (TodoComment $comment): void {
                    $actor = $comment->author ?? $comment->user;

                    if (! $actor instanceof User) {
                        return;
                    }

                    app(SyncTodoCommentMentions::class)->handle($actor, $comment, notify: false);
                });
            });
    }
}

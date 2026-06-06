<?php

use App\Actions\Todos\CreateTodoComment;
use App\Actions\Todos\DeleteTodoComment;
use App\Actions\Todos\UpdateTodoComment;
use App\Livewire\Todos\Comments;
use App\Models\ActivityRecord;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\ProjectMembershipSeeder;
use Database\Seeders\TodoCommentSeeder;
use Database\Seeders\TodoSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Livewire;

test('shared task participants can post escaped comments with activity and database notifications', function () {
    $owner = User::factory()->create(['name' => 'Task Owner']);
    $member = User::factory()->create(['name' => 'Shared Editor']);
    $viewer = User::factory()->create(['name' => 'Shared Viewer']);
    $project = Project::factory()->for($owner)->create();
    $todo = Todo::factory()->forProject($project)->create(['title' => 'Commented launch task']);

    ProjectMembership::factory()->forProject($project)->forMember($member)->editor()->create();
    ProjectMembership::factory()->forProject($project)->forMember($viewer)->viewer()->create();

    Livewire::actingAs($member)
        ->test(Comments::class, ['todoId' => $todo->id])
        ->set('body', "Review <script>alert('x')</script>\nsecond line")
        ->call('create')
        ->assertHasNoErrors()
        ->assertSee("Review <script>alert('x')</script>")
        ->assertDontSee('<script>', false);

    $comment = TodoComment::query()->firstOrFail();

    expect($comment->isOwnedBy($owner))->toBeTrue()
        ->and($comment->author->is($member))->toBeTrue()
        ->and($comment->body)->toBe("Review <script>alert('x')</script>\nsecond line")
        ->and(ActivityRecord::query()->where('event', 'todo.comment_created')->where('actor_id', $member->id)->exists())->toBeTrue();

    $notification = DatabaseNotification::query()
        ->where('notifiable_id', $owner->id)
        ->where('type', 'todo-comment-added')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification?->data['comment_id'])->toBe($comment->id)
        ->and($notification?->data['todo_id'])->toBe($todo->id);

    expect($member->notifications()->count())->toBe(0)
        ->and($viewer->notifications()->count())->toBe(0);
});

test('viewers can read comments but cannot write and removed members lose access', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $project = Project::factory()->for($owner)->create();
    $todo = Todo::factory()->forProject($project)->create(['title' => 'Viewer comment task']);
    $membership = ProjectMembership::factory()->forProject($project)->forMember($viewer)->viewer()->create();

    TodoComment::factory()->forTodo($todo)->create(['body' => 'Owner-visible shared context']);

    $this->actingAs($viewer)
        ->get(route('todos.show', $todo))
        ->assertOk()
        ->assertSee('Owner-visible shared context')
        ->assertSee(__('todos.comments.locked.heading'));

    Livewire::actingAs($viewer)
        ->test(Comments::class, ['todoId' => $todo->id])
        ->set('body', 'Viewer should not write')
        ->call('create')
        ->assertNotFound();

    expect(TodoComment::query()->where('body', 'Viewer should not write')->exists())->toBeFalse();

    $membership->forceFill(['removed_at' => now()])->save();

    expect(fn () => Livewire::actingAs($viewer)->test(Comments::class, ['todoId' => $todo->id]))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => app(CreateTodoComment::class)->handle($viewer, $todo, 'This should not post.'))
        ->toThrow(AuthorizationException::class);
});

test('comment authors can edit and delete their own comments while preserving deleted placeholders', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Editable comment task']);
    $comment = TodoComment::factory()->forTodo($todo)->create(['body' => 'Original comment']);

    Livewire::actingAs($user)
        ->test(Comments::class, ['todoId' => $todo->id])
        ->call('startEditing', $comment->id)
        ->assertSet('editingBody', 'Original comment')
        ->set('editingBody', 'Updated comment')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSee(__('todos.comments.status.edited'))
        ->call('delete', $comment->id)
        ->assertHasNoErrors()
        ->assertSee(__('todos.comments.deleted_body'))
        ->assertDontSee('Updated comment');

    $comment->refresh();

    expect($comment->edited_at)->not->toBeNull()
        ->and($comment->trashed())->toBeTrue()
        ->and(ActivityRecord::query()->where('event', 'todo.comment_updated')->exists())->toBeTrue()
        ->and(ActivityRecord::query()->where('event', 'todo.comment_deleted')->exists())->toBeTrue();
});

test('comment mutation is limited to the author even when another participant can view the task', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $project = Project::factory()->for($owner)->create();
    $todo = Todo::factory()->forProject($project)->create();
    $comment = TodoComment::factory()->forTodo($todo)->create(['body' => 'Owner note']);

    ProjectMembership::factory()->forProject($project)->forMember($viewer)->viewer()->create();

    expect(fn () => app(UpdateTodoComment::class)->handle($viewer, $comment, 'Changed by viewer'))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(DeleteTodoComment::class)->handle($viewer, $comment))
        ->toThrow(AuthorizationException::class);

    expect($comment->refresh()->body)->toBe('Owner note')
        ->and($comment->trashed())->toBeFalse();
});

test('comment body validation rejects empty and oversized input', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Comments::class, ['todoId' => $todo->id])
        ->set('body', " \n \t ")
        ->call('create')
        ->assertHasErrors(['body']);

    Livewire::actingAs($user)
        ->test(Comments::class, ['todoId' => $todo->id])
        ->set('body', str_repeat('x', 2001))
        ->call('create')
        ->assertHasErrors(['body']);

    expect(TodoComment::query()->count())->toBe(0);
});

test('plain mention text does not leak users or create mention notifications in this step', function () {
    $owner = User::factory()->create();
    $privateUser = User::factory()->create([
        'name' => 'Private Mention Target',
        'email' => 'private-mention@example.com',
    ]);
    $todo = Todo::factory()->for($owner)->create(['title' => 'Plain mention task']);

    app(CreateTodoComment::class)->handle($owner, $todo, 'Please ask @private about the next safe action.');

    $this->actingAs($owner)
        ->get(route('todos.show', $todo))
        ->assertOk()
        ->assertSee('Please ask @private about the next safe action.')
        ->assertDontSee('data-test="comment-mention-candidates"', false)
        ->assertDontSee('Private Mention Target')
        ->assertDontSee('private-mention@example.com');

    expect($privateUser->notifications()->count())->toBe(0);
});

test('comment factory and seeder create idempotent local demo threads', function () {
    $todo = Todo::factory()->create();
    $comment = TodoComment::factory()->forTodo($todo)->edited()->demoBody('Factory comment')->create();

    expect($comment->isOwnedBy($todo->user))->toBeTrue()
        ->and($comment->author->is($todo->user))->toBeTrue()
        ->and($comment->edited_at)->not->toBeNull()
        ->and($comment->excerpt())->toBe('Factory comment');

    $this->seed([DemoUserSeeder::class, TodoSeeder::class, ProjectMembershipSeeder::class, TodoCommentSeeder::class]);

    expect(TodoComment::query()->count())->toBeGreaterThanOrEqual(5);

    $counts = TodoComment::withTrashed()->count();

    $this->seed(TodoCommentSeeder::class);

    expect(TodoComment::withTrashed()->count())->toBe($counts)
        ->and(TodoComment::onlyTrashed()->count())->toBeGreaterThanOrEqual(1);
});

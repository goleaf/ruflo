<?php

use App\Actions\Todos\CaptureInboxTodo;
use App\Actions\Todos\TriageInboxTodo;
use App\Data\Todos\TodoData;
use App\Enums\Priority;
use App\Livewire\Todos\Inbox;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function inboxRouteMiddleware(string $routeName): array
{
    return Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
}

test('inbox route redirects guests and unverified users', function () {
    $this->get(route('todos.inbox'))->assertRedirect(route('login'));

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('todos.inbox'))
        ->assertRedirect(route('verification.notice'));
});

test('inbox renders only current user active captured tasks', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-10 09:30:00', config('app.timezone')));

    try {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $captured = Todo::factory()
            ->for($user)
            ->inbox(now()->subMinutes(12))
            ->urgentPriority()
            ->create(['title' => 'Captured owner task']);

        Todo::factory()->for($user)->create(['title' => 'Already triaged']);
        Todo::factory()->for($user)->inbox()->completed()->create(['title' => 'Completed capture']);
        Todo::factory()->for($user)->inbox()->archived()->create(['title' => 'Archived capture']);
        Todo::factory()->for($user)->inbox()->deleted()->create(['title' => 'Deleted capture']);
        Todo::factory()->for($other)->inbox()->create(['title' => 'Foreign capture']);

        $this->actingAs($user)
            ->get(route('todos.inbox'))
            ->assertOk()
            ->assertSee(__('todos.pages.inbox.title'))
            ->assertSee(__('todos.inbox.badge'))
            ->assertSee('Captured owner task')
            ->assertSee(Priority::Urgent->label())
            ->assertSee(route('todos.show', $captured), false)
            ->assertDontSee('Already triaged')
            ->assertDontSee('Completed capture')
            ->assertDontSee('Archived capture')
            ->assertDontSee('Deleted capture')
            ->assertDontSee('Foreign capture');
    } finally {
        Carbon::setTestNow();
    }
});

test('users can quickly capture inbox tasks', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Inbox::class)
        ->set('captureTitle', '  Review   the loose note  ')
        ->call('capture')
        ->assertHasNoErrors()
        ->assertSet('captureTitle', '');

    $todo = $user->todos()->firstWhere('title', 'Review the loose note');

    expect($todo)->not->toBeNull()
        ->and($todo->isOwnedBy($user))->toBeTrue()
        ->and($todo->isInInbox())->toBeTrue()
        ->and($todo->isActive())->toBeTrue()
        ->and($todo->priority)->toBe(Priority::Normal)
        ->and($todo->project_id)->toBeNull()
        ->and($todo->due_date)->toBeNull();
});

test('invalid inbox capture input is rejected in livewire and action layers', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Inbox::class)
        ->set('captureTitle', '     ')
        ->call('capture')
        ->assertHasErrors(['captureTitle'])
        ->assertSet('captureTitle', '     ');

    expect($user->todos()->count())->toBe(0);

    expect(fn () => app(CaptureInboxTodo::class)->handle($user, '     '))
        ->toThrow(ValidationException::class);

    expect(fn () => app(CaptureInboxTodo::class)->handle($user, str_repeat('x', 121)))
        ->toThrow(ValidationException::class);
});

test('users can triage captured tasks into normal organization fields', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'Launch']);
    $tag = Tag::factory()->for($user)->create(['name' => 'focus']);
    $todo = Todo::factory()->for($user)->inbox()->create(['title' => 'Captured draft']);

    Livewire::actingAs($user)
        ->test(Inbox::class)
        ->call('startTriage', $todo->id)
        ->assertSet('showTriageModal', true)
        ->assertSet('triageForm.title', 'Captured draft')
        ->set('triageForm.title', 'Organized task')
        ->set('triageForm.priority', Priority::High->value)
        ->set('triageForm.due_date', today()->addDay()->toDateString())
        ->set('triageForm.project_id', (string) $project->id)
        ->set('triageForm.tag_ids', [$tag->id])
        ->call('saveTriage')
        ->assertHasNoErrors()
        ->assertSet('showTriageModal', false);

    $todo->refresh();

    expect($todo->title)->toBe('Organized task')
        ->and($todo->isInInbox())->toBeFalse()
        ->and($todo->priority)->toBe(Priority::High)
        ->and($todo->due_date->toDateString())->toBe(today()->addDay()->toDateString())
        ->and($todo->project_id)->toBe($project->id)
        ->and($todo->tags()->pluck('tags.id')->all())->toBe([$tag->id]);
});

test('foreign and non inbox tasks cannot be triaged from the inbox', function () {
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    $foreignTodo = Todo::factory()->for($owner)->inbox()->create();
    $notInbox = Todo::factory()->for($viewer)->create();

    expect(fn () => Livewire::actingAs($viewer)
        ->test(Inbox::class)
        ->call('startTriage', $foreignTodo->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($viewer)
        ->test(Inbox::class)
        ->call('startTriage', $notInbox->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => app(TriageInboxTodo::class)->handle($viewer, $notInbox, new TodoData(title: 'No inbox marker')))
        ->toThrow(ValidationException::class);
});

test('inbox route and component keep private view guardrails', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Inbox.php'));

    expect(inboxRouteMiddleware('todos.inbox'))
        ->toContain('auth', 'verified')
        ->and(route('todos.inbox'))->toBe('https://ruflo.test/todos/inbox')
        ->and($source)
        ->toContain('TodoInboxQuery')
        ->toContain('CaptureInboxTodo')
        ->toContain('TriageInboxTodo')
        ->toContain('InboxCaptureTitle')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()');
});

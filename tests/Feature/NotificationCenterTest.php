<?php

use App\Livewire\Notifications\Inbox as NotificationInbox;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\User;
use App\Notifications\TodoReminderDueNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;

function createReminderNotification(User $user, string $title): DatabaseNotification
{
    $todo = Todo::factory()->for($user)->active()->create(['title' => $title]);
    $reminder = Reminder::factory()->forTodo($todo)->due(now()->subMinute())->create();

    $user->notify(new TodoReminderDueNotification($reminder));

    /** @var DatabaseNotification $notification */
    $notification = $user->notifications()->latest()->firstOrFail();

    return $notification;
}

test('notification center renders only the authenticated users private notifications', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    createReminderNotification($user, 'Owner reminder task');
    createReminderNotification($other, 'Foreign reminder task');

    $this->actingAs($user)
        ->get(route('notifications.inbox'))
        ->assertOk()
        ->assertSeeText(__('notifications.pages.inbox.title'))
        ->assertSee('data-test="notification-center"', false)
        ->assertSee('data-test="notification-list"', false)
        ->assertSeeText(__('notifications.status.unread'))
        ->assertSeeText('Owner reminder task')
        ->assertDontSeeText('Foreign reminder task');
});

test('notification read state actions stay scoped to the authenticated user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $notification = createReminderNotification($user, 'Scoped read task');
    $foreignNotification = createReminderNotification($other, 'Foreign read task');

    Livewire::actingAs($user)
        ->test(NotificationInbox::class)
        ->call('markRead', $notification->id)
        ->assertOk();

    expect($notification->refresh()->read_at)->not->toBeNull();

    Livewire::actingAs($user)
        ->test(NotificationInbox::class)
        ->call('markUnread', $notification->id)
        ->assertOk();

    expect($notification->refresh()->read_at)->toBeNull();

    expect(fn () => Livewire::actingAs($user)
        ->test(NotificationInbox::class)
        ->call('markRead', $foreignNotification->id))
        ->toThrow(ModelNotFoundException::class);
});

test('mark all read only changes the authenticated users unread notifications', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    createReminderNotification($user, 'First mark all task');
    createReminderNotification($user, 'Second mark all task');
    createReminderNotification($other, 'Foreign mark all task');

    Livewire::actingAs($user)
        ->test(NotificationInbox::class)
        ->call('markAllRead')
        ->assertOk();

    expect($user->unreadNotifications()->count())->toBe(0)
        ->and($other->unreadNotifications()->count())->toBe(1);
});

test('notification action links are same host hints and target routes still authorize access', function () {
    $viewer = User::factory()->create();
    $other = User::factory()->create();
    $ownTodo = Todo::factory()->for($viewer)->active()->create(['title' => 'Open safe task']);
    $foreignTodo = Todo::factory()->for($other)->active()->create(['title' => 'Do not leak task']);
    $ownActionPath = route('todos.show', $ownTodo, false);
    $foreignActionPath = route('todos.show', $foreignTodo, false);

    $viewer->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'manual-safe-link',
        'data' => [
            'kind' => 'manual',
            'title' => 'Safe link',
            'message' => 'Open the owner task.',
            'action_url' => $ownActionPath,
        ],
    ]);

    $viewer->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'manual-external-link',
        'data' => [
            'kind' => 'manual',
            'title' => 'External link',
            'message' => 'External links are hidden.',
            'action_url' => 'https://example.com/blocked',
        ],
    ]);

    $viewer->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'manual-stale-link',
        'data' => [
            'kind' => 'manual',
            'title' => 'Stale task link',
            'message' => 'The target route must still authorize access.',
            'action_url' => $foreignActionPath,
        ],
    ]);

    $viewer->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'manual-protocol-relative-link',
        'data' => [
            'kind' => 'manual',
            'title' => 'Protocol relative link',
            'message' => 'Protocol relative links are hidden.',
            'action_url' => '//evil.test/todos/'.$ownTodo->id,
        ],
    ]);

    $viewer->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'manual-unsupported-scheme-link',
        'data' => [
            'kind' => 'manual',
            'title' => 'Unsupported scheme link',
            'message' => 'Unsupported schemes are hidden.',
            'action_url' => 'ftp://ruflo.test/todos/'.$ownTodo->id,
        ],
    ]);

    $this->actingAs($viewer)
        ->get(route('notifications.inbox'))
        ->assertOk()
        ->assertSee('href="'.$ownActionPath.'"', false)
        ->assertDontSee('href="'.$foreignActionPath.'"', false)
        ->assertDontSee('https://example.com/blocked', false)
        ->assertDontSee('//evil.test/todos/'.$ownTodo->id, false)
        ->assertDontSee('ftp://ruflo.test/todos/'.$ownTodo->id, false);

    $this->actingAs($viewer)
        ->get($foreignActionPath)
        ->assertNotFound()
        ->assertDontSeeText('Do not leak task');
});

test('notification filter URL state is sanitized and empty state is translated', function () {
    $user = User::factory()->create();

    Livewire::withQueryParams(['filter' => 'unexpected'])
        ->actingAs($user)
        ->test(NotificationInbox::class)
        ->assertSet('filter', 'all')
        ->assertSee(__('notifications.empty.title'));
});

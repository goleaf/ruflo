<?php

use App\Actions\Todos\BulkCompleteTodos;
use App\Livewire\Todos\Index;
use App\Models\Todo;
use App\Models\User;
use Livewire\Livewire;

test('bulk action results report selected affected skipped and failed counts', function () {
    $owner = User::factory()->create();
    $active = Todo::factory()->for($owner)->create();
    $completed = Todo::factory()->for($owner)->completed()->create();
    $archived = Todo::factory()->for($owner)->archived()->create();
    $foreign = Todo::factory()->create();

    $result = app(BulkCompleteTodos::class)->handle($owner, [
        $active->id,
        $completed->id,
        $archived->id,
        $foreign->id,
    ]);

    expect($result->selected)->toBe(4)
        ->and($result->affected)->toBe(1)
        ->and($result->skipped)->toBe(3)
        ->and($result->failed)->toBe(0)
        ->and($active->fresh()->is_completed)->toBeTrue()
        ->and($completed->fresh()->is_completed)->toBeTrue()
        ->and($archived->fresh()->is_completed)->toBeFalse()
        ->and($foreign->fresh()->is_completed)->toBeFalse();
});

test('users can select visible tasks clear selection and see bulk result counts', function () {
    $user = User::factory()->create();
    $first = Todo::factory()->for($user)->create(['title' => 'Alpha visible']);
    $second = Todo::factory()->for($user)->create(['title' => 'Beta visible']);
    Todo::factory()->create(['title' => 'Foreign hidden']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('selectVisible')
        ->assertSet('selected', [$second->id, $first->id])
        ->assertSee(__('todos.bulk.selected', ['count' => 2]))
        ->call('bulkComplete')
        ->assertSet('selected', [])
        ->assertSet('bulkResult', [
            'selected' => 2,
            'affected' => 2,
            'skipped' => 0,
            'failed' => 0,
        ])
        ->assertSee(__('todos.bulk.result', [
            'selected' => 2,
            'affected' => 2,
            'skipped' => 0,
            'failed' => 0,
        ]))
        ->call('selectVisible')
        ->call('clearSelection')
        ->assertSet('selected', [])
        ->assertSet('bulkResult', null);
});

test('livewire bulk actions report skipped owned non actionable tasks', function () {
    $user = User::factory()->create();
    $active = Todo::factory()->for($user)->create();
    $completed = Todo::factory()->for($user)->completed()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('selected', [$active->id, $completed->id])
        ->call('bulkComplete')
        ->assertHasNoErrors()
        ->assertSet('bulkResult', [
            'selected' => 2,
            'affected' => 1,
            'skipped' => 1,
            'failed' => 0,
        ])
        ->assertSee(__('todos.bulk.result', [
            'selected' => 2,
            'affected' => 1,
            'skipped' => 1,
            'failed' => 0,
        ]));
});

test('bulk delete uses a Flux confirmation modal before moving selected tasks to trash', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['title' => 'Delete through modal']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('selected', [$todo->id])
        ->set('showBulkDeleteModal', true)
        ->assertSee(__('todos.modals.bulk_delete.heading'))
        ->assertSee(__('todos.modals.bulk_delete.description', ['count' => 1]))
        ->call('bulkDelete')
        ->assertSet('showBulkDeleteModal', false)
        ->assertSet('bulkResult', [
            'selected' => 1,
            'affected' => 1,
            'skipped' => 0,
            'failed' => 0,
        ]);

    expect(Todo::query()->find($todo->id))->toBeNull()
        ->and(Todo::withTrashed()->find($todo->id)?->trashed())->toBeTrue();
});

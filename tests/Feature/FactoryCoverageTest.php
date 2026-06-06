<?php

use App\Enums\Priority;
use App\Enums\TodoStatus;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('tracked models can be created from their default factories', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $tag = Tag::factory()->for($user)->create();
    $todo = Todo::factory()->for($user)->forProject($project)->withTags($tag)->create();
    $reminder = Reminder::factory()->create();

    expect($user->exists)->toBeTrue()
        ->and($project->isOwnedBy($user))->toBeTrue()
        ->and($tag->isOwnedBy($user))->toBeTrue()
        ->and($todo->isOwnedBy($user))->toBeTrue()
        ->and($todo->project_id)->toBe($project->id)
        ->and($todo->tags()->pluck('tags.id')->all())->toBe([$tag->id])
        ->and($reminder->exists)->toBeTrue();
});

test('user factory covers authentication and demo states', function () {
    $passwordUser = User::factory()->withPassword('custom-secret')->create();
    $admin = User::factory()->admin()->create();
    $unverified = User::factory()->unverified()->create();
    $twoFactor = User::factory()->withTwoFactor()->create();
    $primaryDemo = User::factory()->demoPrimary()->create();
    $secondaryDemo = User::factory()->demoSecondary()->create();

    expect(Hash::check('custom-secret', $passwordUser->password))->toBeTrue()
        ->and($unverified->email_verified_at)->toBeNull()
        ->and($admin->is_admin)->toBeTrue()
        ->and($twoFactor->two_factor_secret)->not->toBeNull()
        ->and($twoFactor->two_factor_recovery_codes)->not->toBeNull()
        ->and($twoFactor->two_factor_confirmed_at)->not->toBeNull()
        ->and($primaryDemo->email)->toBe('test@example.com')
        ->and($primaryDemo->is_admin)->toBeTrue()
        ->and(Hash::check('password', $primaryDemo->password))->toBeTrue()
        ->and($secondaryDemo->email)->toBe('second@example.com')
        ->and($secondaryDemo->is_admin)->toBeFalse()
        ->and(Hash::check('password', $secondaryDemo->password))->toBeTrue();
});

test('project and tag factories cover named color and archive states', function () {
    $activeProject = Project::factory()->work()->active()->create();
    $archivedProject = Project::factory()->home()->archived()->create();
    $urgentTag = Tag::factory()->urgent()->create();
    $waitingTag = Tag::factory()->waiting()->create();
    $customTag = Tag::factory()->named('deep-work')->color('purple')->create();

    expect($activeProject->name)->toBe('Work')
        ->and($activeProject->color)->toBe('blue')
        ->and($activeProject->isArchived())->toBeFalse()
        ->and($archivedProject->name)->toBe('Home')
        ->and($archivedProject->color)->toBe('green')
        ->and($archivedProject->isArchived())->toBeTrue()
        ->and($urgentTag->name)->toBe('urgent')
        ->and($urgentTag->color)->toBe('red')
        ->and($waitingTag->name)->toBe('waiting')
        ->and($waitingTag->color)->toBe('amber')
        ->and($customTag->name)->toBe('deep-work')
        ->and($customTag->color)->toBe('purple');
});

test('todo factory covers priority date and lifecycle states', function () {
    $active = Todo::factory()->active()->create();
    $completed = Todo::factory()->completed()->create();
    $archived = Todo::factory()->archived()->create();
    $archivedCompleted = Todo::factory()->completed()->archived()->create();
    $deleted = Todo::factory()->deleted()->create();
    $dueToday = Todo::factory()->dueToday()->create();
    $overdue = Todo::factory()->overdue()->create();
    $upcoming = Todo::factory()->upcoming()->create();
    $withoutDueDate = Todo::factory()->withoutDueDate()->create();
    $longTitle = Todo::factory()->longTitle()->create();
    $low = Todo::factory()->lowPriority()->create();
    $normal = Todo::factory()->normalPriority()->create();
    $high = Todo::factory()->highPriority()->create();
    $urgent = Todo::factory()->urgentPriority()->create();

    expect($active->status())->toBe(TodoStatus::Active)
        ->and($completed->status())->toBe(TodoStatus::Completed)
        ->and($archived->status())->toBe(TodoStatus::Archived)
        ->and($archivedCompleted->status())->toBe(TodoStatus::Archived)
        ->and($archivedCompleted->is_completed)->toBeTrue()
        ->and($deleted->status())->toBe(TodoStatus::Trash)
        ->and($deleted->trashed())->toBeTrue()
        ->and($dueToday->isDueToday())->toBeTrue()
        ->and($overdue->isOverdue())->toBeTrue()
        ->and($upcoming->due_date->isFuture())->toBeTrue()
        ->and($withoutDueDate->due_date)->toBeNull()
        ->and(strlen($longTitle->title))->toBe(120)
        ->and($low->priority)->toBe(Priority::Low)
        ->and($normal->priority)->toBe(Priority::Normal)
        ->and($high->priority)->toBe(Priority::High)
        ->and($urgent->priority)->toBe(Priority::Urgent);
});

test('todo relationship helpers keep project and tag data inside the same owner boundary', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $project = Project::factory()->for($owner)->create();
    $ownerTag = Tag::factory()->for($owner)->urgent()->create();
    $foreignTag = Tag::factory()->for($intruder)->waiting()->create();

    $todo = Todo::factory()
        ->forProject($project)
        ->withTags($ownerTag, $foreignTag)
        ->create();

    $tagTodo = Todo::factory()->forTag($ownerTag)->create();

    expect($todo->user_id)->toBe($owner->id)
        ->and($todo->project_id)->toBe($project->id)
        ->and($todo->tags()->pluck('tags.id')->all())->toBe([$ownerTag->id])
        ->and($tagTodo->user_id)->toBe($owner->id)
        ->and($tagTodo->tags()->pluck('tags.id')->all())->toBe([$ownerTag->id]);
});

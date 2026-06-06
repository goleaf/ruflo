<?php

use App\Enums\Priority;
use App\Enums\TaskTemplateKind;
use App\Enums\TodoStatus;
use App\Models\Project;
use App\Models\Reminder;
use App\Models\SavedTodoView;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('tracked models can be created from their default factories', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $tag = Tag::factory()->for($user)->create();
    $todo = Todo::factory()->for($user)->forProject($project)->withTags($tag)->create();
    $checklistItem = TodoChecklistItem::factory()->forTodo($todo)->completed()->position(1)->create();
    $template = TodoTemplate::factory()->for($user)->routine()->create();
    $savedView = SavedTodoView::factory()->for($user)->create();
    $reminder = Reminder::factory()->create();

    expect($user->exists)->toBeTrue()
        ->and($project->isOwnedBy($user))->toBeTrue()
        ->and($tag->isOwnedBy($user))->toBeTrue()
        ->and($todo->isOwnedBy($user))->toBeTrue()
        ->and($todo->project_id)->toBe($project->id)
        ->and($todo->tags()->pluck('tags.id')->all())->toBe([$tag->id])
        ->and($checklistItem->isOwnedBy($user))->toBeTrue()
        ->and($checklistItem->todo->is($todo))->toBeTrue()
        ->and($checklistItem->is_completed)->toBeTrue()
        ->and($checklistItem->position)->toBe(1)
        ->and($template->isOwnedBy($user))->toBeTrue()
        ->and($template->checklist_items)->toHaveCount(3)
        ->and($savedView->isOwnedBy($user))->toBeTrue()
        ->and($savedView->criteria['sort'])->toBe('created')
        ->and($reminder->exists)->toBeTrue();
});

test('todo template factory covers kind visibility checklist and edge states', function () {
    $task = TodoTemplate::factory()->task()->private()->create();
    $project = TodoTemplate::factory()->project()->shared()->create();
    $checklist = TodoTemplate::factory()->checklist()->create();
    $routine = TodoTemplate::factory()->routine()->dueIn(1)->create();
    $heavy = TodoTemplate::factory()->heavyChecklist()->create();
    $longName = TodoTemplate::factory()->longName()->create();

    expect($task->kind)->toBe(TaskTemplateKind::Task)
        ->and($task->visibility)->toBe('private')
        ->and($project->kind)->toBe(TaskTemplateKind::Project)
        ->and($project->visibility)->toBe('shared')
        ->and($project->project_name)->toBe('Project launch')
        ->and($checklist->kind)->toBe(TaskTemplateKind::Checklist)
        ->and($checklist->checklist_items)->toHaveCount(2)
        ->and($routine->kind)->toBe(TaskTemplateKind::Routine)
        ->and($routine->due_offset_days)->toBe(1)
        ->and($heavy->checklist_items)->toHaveCount(10)
        ->and(strlen($longName->name))->toBe(80);
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

test('saved todo view factory covers common saved view states', function () {
    $today = SavedTodoView::factory()->dueToday()->create();
    $urgent = SavedTodoView::factory()->urgent()->create();
    $completed = SavedTodoView::factory()->completed()->create();
    $custom = SavedTodoView::factory()->criteria([
        'tab' => 'active',
        'search' => 'alpha',
        'project' => 'none',
        'tag' => '',
        'priorityFilter' => '',
        'due' => '',
        'sort' => 'title',
        'direction' => 'asc',
    ])->create();

    expect($today->name)->toBe('Due today')
        ->and($today->criteria['due'])->toBe('today')
        ->and($today->criteria['sort'])->toBe('due')
        ->and($urgent->criteria['priorityFilter'])->toBe(Priority::Urgent->value)
        ->and($completed->criteria['tab'])->toBe(TodoStatus::Completed->value)
        ->and($custom->criteria['search'])->toBe('alpha')
        ->and($custom->criteria['direction'])->toBe('asc');
});

test('todo factory covers priority date and lifecycle states', function () {
    $active = Todo::factory()->active()->create();
    $completed = Todo::factory()->completed()->create();
    $archived = Todo::factory()->archived()->create();
    $archivedCompleted = Todo::factory()->completed()->archived()->create();
    $deleted = Todo::factory()->deleted()->create();
    $inbox = Todo::factory()->inbox()->create();
    $triaged = Todo::factory()->inbox()->triaged()->create();
    $dueToday = Todo::factory()->dueToday()->create();
    $overdue = Todo::factory()->overdue()->create();
    $upcoming = Todo::factory()->upcoming()->create();
    $withoutDueDate = Todo::factory()->withoutDueDate()->create();
    $longTitle = Todo::factory()->longTitle()->create();
    $low = Todo::factory()->lowPriority()->create();
    $normal = Todo::factory()->normalPriority()->create();
    $high = Todo::factory()->highPriority()->create();
    $urgent = Todo::factory()->urgentPriority()->create();
    $focusCandidate = Todo::factory()->focusCandidate()->create();

    expect($active->status())->toBe(TodoStatus::Active)
        ->and($completed->status())->toBe(TodoStatus::Completed)
        ->and($archived->status())->toBe(TodoStatus::Archived)
        ->and($archivedCompleted->status())->toBe(TodoStatus::Archived)
        ->and($archivedCompleted->is_completed)->toBeTrue()
        ->and($deleted->status())->toBe(TodoStatus::Trash)
        ->and($deleted->trashed())->toBeTrue()
        ->and($inbox->isInInbox())->toBeTrue()
        ->and($inbox->isActive())->toBeTrue()
        ->and($triaged->isInInbox())->toBeFalse()
        ->and($dueToday->isDueToday())->toBeTrue()
        ->and($overdue->isOverdue())->toBeTrue()
        ->and($upcoming->due_date->isFuture())->toBeTrue()
        ->and($withoutDueDate->due_date)->toBeNull()
        ->and(strlen($longTitle->title))->toBe(120)
        ->and($low->priority)->toBe(Priority::Low)
        ->and($normal->priority)->toBe(Priority::Normal)
        ->and($high->priority)->toBe(Priority::High)
        ->and($urgent->priority)->toBe(Priority::Urgent)
        ->and($focusCandidate->priority)->toBe(Priority::High)
        ->and($focusCandidate->isDueToday())->toBeTrue();
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

test('todo checklist item factory covers ownership completion and ordering states', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();
    $pending = TodoChecklistItem::factory()->forTodo($todo)->pending()->position(2)->create(['title' => 'Pending item']);
    $completed = TodoChecklistItem::factory()->forTodo($todo)->completed()->position(1)->create(['title' => 'Completed item']);
    $longTitle = TodoChecklistItem::factory()->forTodo($todo)->longTitle()->create();

    expect($pending->isOwnedBy($user))->toBeTrue()
        ->and($pending->todo->is($todo))->toBeTrue()
        ->and($pending->is_completed)->toBeFalse()
        ->and($pending->completed_at)->toBeNull()
        ->and($pending->position)->toBe(2)
        ->and($completed->is_completed)->toBeTrue()
        ->and($completed->completed_at)->not->toBeNull()
        ->and($completed->position)->toBe(1)
        ->and(strlen($longTitle->title))->toBe(120);
});

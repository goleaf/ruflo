<?php

use App\Enums\AutomationRuleKind;
use App\Enums\AutomationRunStatus;
use App\Enums\PomodoroSessionStatus;
use App\Enums\Priority;
use App\Enums\ProjectRole;
use App\Enums\RecurrenceEndType;
use App\Enums\RecurrenceExceptionType;
use App\Enums\RecurrenceFrequency;
use App\Enums\RecurrenceWeekday;
use App\Enums\ReminderStatus;
use App\Enums\TaskTemplateKind;
use App\Enums\TimeEntrySource;
use App\Enums\TimeEntryStatus;
use App\Enums\TodoStatus;
use App\Models\AutomationRule;
use App\Models\AutomationRuleRun;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\PomodoroSession;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectMembership;
use App\Models\Reminder;
use App\Models\SavedTodoView;
use App\Models\Tag;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoDependency;
use App\Models\TodoRecurrenceException;
use App\Models\TodoRecurrenceRule;
use App\Models\TodoTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('tracked models can be created from their default factories', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $projectMember = User::factory()->create();
    $projectMembership = ProjectMembership::factory()->forProject($project)->forMember($projectMember)->editor()->create();
    $projectInvitation = ProjectInvitation::factory()->forProject($project)->invitedBy($user)->manager()->create();
    $goal = Goal::factory()->forProject($project)->create();
    $milestone = GoalMilestone::factory()->forGoal($goal)->completed()->position(1)->create();
    $habit = Habit::factory()->forGoal($goal)->daily()->create();
    $checkIn = HabitCheckIn::factory()->forHabit($habit)->today()->create();
    $tag = Tag::factory()->for($user)->create();
    $todo = Todo::factory()->for($user)->forProject($project)->forMilestone($milestone)->forHabit($habit)->withTags($tag)->create();
    $checklistItem = TodoChecklistItem::factory()->forTodo($todo)->completed()->position(1)->create();
    $blocker = Todo::factory()->for($user)->create();
    $dependency = TodoDependency::factory()->forTodos($todo, $blocker)->create();
    $pomodoroSession = PomodoroSession::factory()->forTodo($todo)->completed()->create();
    $timeEntry = TimeEntry::factory()->forTodo($todo)->manual(45)->create();
    $template = TodoTemplate::factory()->for($user)->routine()->create();
    $savedView = SavedTodoView::factory()->for($user)->create();
    $reminder = Reminder::factory()->forTodo($todo)->due()->create();
    $recurrenceRule = TodoRecurrenceRule::factory()->forTodo($todo)->weekly()->afterOccurrences(6)->create();
    $recurrenceOccurrence = Todo::factory()->generatedOccurrence($recurrenceRule, '2026-06-07')->create();
    $recurrenceException = TodoRecurrenceException::factory()->forOccurrence($recurrenceOccurrence)->moved('2026-06-08')->create();
    $automationRule = AutomationRule::factory()->for($user)->promoteOverdueTasks()->create();
    $automationRuleRun = AutomationRuleRun::factory()->forRule($automationRule)->dryRun()->create();

    expect($user->exists)->toBeTrue()
        ->and($project->isOwnedBy($user))->toBeTrue()
        ->and($projectMembership->project->is($project))->toBeTrue()
        ->and($projectMembership->user->is($projectMember))->toBeTrue()
        ->and($projectMembership->role)->toBe(ProjectRole::Editor)
        ->and($projectMembership->isActive())->toBeTrue()
        ->and($projectInvitation->project->is($project))->toBeTrue()
        ->and($projectInvitation->invitedBy->is($user))->toBeTrue()
        ->and($projectInvitation->role)->toBe(ProjectRole::Manager)
        ->and($projectInvitation->isPending())->toBeTrue()
        ->and($projectInvitation->shareUrl())->toStartWith('https://ruflo.test/project-invitations/')
        ->and($goal->isOwnedBy($user))->toBeTrue()
        ->and($goal->project_id)->toBe($project->id)
        ->and($milestone->isOwnedBy($user))->toBeTrue()
        ->and($milestone->goal_id)->toBe($goal->id)
        ->and($milestone->isCompleted())->toBeTrue()
        ->and($habit->isOwnedBy($user))->toBeTrue()
        ->and($habit->goal_id)->toBe($goal->id)
        ->and($checkIn->isOwnedBy($user))->toBeTrue()
        ->and($checkIn->habit_id)->toBe($habit->id)
        ->and($tag->isOwnedBy($user))->toBeTrue()
        ->and($todo->isOwnedBy($user))->toBeTrue()
        ->and($todo->project_id)->toBe($project->id)
        ->and($todo->goal_id)->toBe($goal->id)
        ->and($todo->goal_milestone_id)->toBe($milestone->id)
        ->and($todo->habit_id)->toBe($habit->id)
        ->and($todo->tags()->pluck('tags.id')->all())->toBe([$tag->id])
        ->and($checklistItem->isOwnedBy($user))->toBeTrue()
        ->and($checklistItem->todo->is($todo))->toBeTrue()
        ->and($checklistItem->is_completed)->toBeTrue()
        ->and($checklistItem->position)->toBe(1)
        ->and($dependency->isOwnedBy($user))->toBeTrue()
        ->and($dependency->todo->is($todo))->toBeTrue()
        ->and($dependency->blocker->is($blocker))->toBeTrue()
        ->and($dependency->isOpen())->toBeTrue()
        ->and($pomodoroSession->isOwnedBy($user))->toBeTrue()
        ->and($pomodoroSession->todo->is($todo))->toBeTrue()
        ->and($pomodoroSession->status)->toBe(PomodoroSessionStatus::Completed)
        ->and($timeEntry->isOwnedBy($user))->toBeTrue()
        ->and($timeEntry->todo->is($todo))->toBeTrue()
        ->and($timeEntry->duration_seconds)->toBe(2700)
        ->and($timeEntry->status)->toBe(TimeEntryStatus::Completed)
        ->and($template->isOwnedBy($user))->toBeTrue()
        ->and($template->checklist_items)->toHaveCount(3)
        ->and($savedView->isOwnedBy($user))->toBeTrue()
        ->and($savedView->criteria['sort'])->toBe('created')
        ->and($reminder->isOwnedBy($user))->toBeTrue()
        ->and($reminder->todo->is($todo))->toBeTrue()
        ->and($reminder->status)->toBe(ReminderStatus::Pending)
        ->and($reminder->isDue())->toBeTrue()
        ->and($recurrenceRule->isOwnedBy($user))->toBeTrue()
        ->and($recurrenceRule->todo->is($todo))->toBeTrue()
        ->and($recurrenceRule->frequency)->toBe(RecurrenceFrequency::Weekly)
        ->and($recurrenceRule->weekdays)->toBe([RecurrenceWeekday::Monday->value, RecurrenceWeekday::Wednesday->value])
        ->and($recurrenceRule->end_type)->toBe(RecurrenceEndType::AfterOccurrences)
        ->and($recurrenceRule->max_occurrences)->toBe(6)
        ->and($recurrenceException->isOwnedBy($user))->toBeTrue()
        ->and($recurrenceException->todo->is($recurrenceOccurrence))->toBeTrue()
        ->and($recurrenceException->type)->toBe(RecurrenceExceptionType::Moved)
        ->and($recurrenceException->original_occurs_on->toDateString())->toBe('2026-06-07')
        ->and($recurrenceException->adjusted_occurs_on->toDateString())->toBe('2026-06-08')
        ->and($automationRule->isOwnedBy($user))->toBeTrue()
        ->and($automationRule->kind)->toBe(AutomationRuleKind::PromoteOverdueTasks)
        ->and($automationRule->settings)->toBe(AutomationRuleKind::PromoteOverdueTasks->defaultSettings())
        ->and($automationRuleRun->isOwnedBy($user))->toBeTrue()
        ->and($automationRuleRun->rule->is($automationRule))->toBeTrue()
        ->and($automationRuleRun->status)->toBe(AutomationRunStatus::Completed)
        ->and($automationRuleRun->dry_run)->toBeTrue();
});

test('todo recurrence rule factory covers cadence ending and paused states', function () {
    $todo = Todo::factory()->dueToday()->create();
    $daily = TodoRecurrenceRule::factory()->forTodo($todo)->create();
    $weekly = TodoRecurrenceRule::factory()->forTodo(Todo::factory()->for($todo->user)->create())->weekly([RecurrenceWeekday::Friday])->paused()->create();
    $monthly = TodoRecurrenceRule::factory()->forTodo(Todo::factory()->for($todo->user)->create())->monthly(15)->endingOn('2026-12-31')->create();

    expect($daily->frequency)->toBe(RecurrenceFrequency::Daily)
        ->and($daily->is_enabled)->toBeTrue()
        ->and($weekly->frequency)->toBe(RecurrenceFrequency::Weekly)
        ->and($weekly->weekdays)->toBe([RecurrenceWeekday::Friday->value])
        ->and($weekly->is_enabled)->toBeFalse()
        ->and($monthly->frequency)->toBe(RecurrenceFrequency::Monthly)
        ->and($monthly->month_day)->toBe(15)
        ->and($monthly->end_type)->toBe(RecurrenceEndType::OnDate)
        ->and($monthly->ends_on->toDateString())->toBe('2026-12-31');
});

test('automation rule factories cover enabled disabled kind and run states', function () {
    $user = User::factory()->create();
    $promoteRule = AutomationRule::factory()->for($user)->promoteOverdueTasks()->create();
    $archiveRule = AutomationRule::factory()->for($user)->archiveCompletedTasks(14)->disabled()->create();
    $completedRun = AutomationRuleRun::factory()->forRule($promoteRule)->create();
    $disabledRun = AutomationRuleRun::factory()->forRule($archiveRule)->disabled()->dryRun()->create();

    expect($promoteRule->isOwnedBy($user))->toBeTrue()
        ->and($promoteRule->kind)->toBe(AutomationRuleKind::PromoteOverdueTasks)
        ->and($promoteRule->is_enabled)->toBeTrue()
        ->and($promoteRule->settings)->toBe(AutomationRuleKind::PromoteOverdueTasks->defaultSettings())
        ->and($archiveRule->kind)->toBe(AutomationRuleKind::ArchiveCompletedTasks)
        ->and($archiveRule->settings)->toBe(['days' => 14])
        ->and($archiveRule->is_enabled)->toBeFalse()
        ->and($completedRun->isOwnedBy($user))->toBeTrue()
        ->and($completedRun->rule->is($promoteRule))->toBeTrue()
        ->and($completedRun->status)->toBe(AutomationRunStatus::Completed)
        ->and($disabledRun->isOwnedBy($user))->toBeTrue()
        ->and($disabledRun->rule->is($archiveRule))->toBeTrue()
        ->and($disabledRun->status)->toBe(AutomationRunStatus::Disabled)
        ->and($disabledRun->dry_run)->toBeTrue();
});

test('todo dependency factory covers open and resolved blocker states', function () {
    $todo = Todo::factory()->create();
    $openBlocker = Todo::factory()->for($todo->user)->create();
    $resolvedBlocker = Todo::factory()->for($todo->user)->completed()->create();

    $open = TodoDependency::factory()->forTodos($todo, $openBlocker)->create();
    $resolved = TodoDependency::factory()->forTodos($todo, $resolvedBlocker)->create();

    expect($open->isOwnedBy($todo->user))->toBeTrue()
        ->and($open->todo->is($todo))->toBeTrue()
        ->and($open->blocker->is($openBlocker))->toBeTrue()
        ->and($open->isOpen())->toBeTrue()
        ->and($resolved->blocker->is($resolvedBlocker))->toBeTrue()
        ->and($resolved->isOpen())->toBeFalse();
});

test('habit factories cover frequency goal check in and archive states', function () {
    $goal = Goal::factory()->create();
    $daily = Habit::factory()->forGoal($goal)->daily()->titled('Plan every day')->create();
    $weekly = Habit::factory()->for($goal->user)->weekly(3)->create();
    $archived = Habit::factory()->archived()->create();
    $checkIn = HabitCheckIn::factory()->forHabit($daily)->yesterday()->create();

    expect($daily->isOwnedBy($goal->user))->toBeTrue()
        ->and($daily->goal_id)->toBe($goal->id)
        ->and($daily->frequency->value)->toBe('daily')
        ->and($daily->target_count)->toBe(1)
        ->and($weekly->frequency->value)->toBe('weekly')
        ->and($weekly->target_count)->toBe(3)
        ->and($archived->isArchived())->toBeTrue()
        ->and($checkIn->isOwnedBy($goal->user))->toBeTrue()
        ->and($checkIn->occurred_on->isSameDay(today()->subDay()))->toBeTrue();
});

test('pomodoro session factory covers task duration and lifecycle states', function () {
    $todo = Todo::factory()->focusCandidate()->create();
    $running = PomodoroSession::factory()->forTodo($todo)->duration(15)->running(120)->create();
    $paused = PomodoroSession::factory()->forTodo($todo)->paused(300)->create();
    $completed = PomodoroSession::factory()->forTodo($todo)->completed(1500)->create();
    $abandoned = PomodoroSession::factory()->forTodo($todo)->abandoned(180)->create();
    $demo = PomodoroSession::factory()->forTodo($todo)->demo()->create();

    expect($running->isOwnedBy($todo->user))->toBeTrue()
        ->and($running->todo->is($todo))->toBeTrue()
        ->and($running->duration_minutes)->toBe(15)
        ->and($running->status)->toBe(PomodoroSessionStatus::Running)
        ->and($running->isRunning())->toBeTrue()
        ->and($paused->status)->toBe(PomodoroSessionStatus::Paused)
        ->and($paused->elapsed_seconds)->toBe(300)
        ->and($paused->isPaused())->toBeTrue()
        ->and($completed->status)->toBe(PomodoroSessionStatus::Completed)
        ->and($completed->completed_at)->not->toBeNull()
        ->and($abandoned->status)->toBe(PomodoroSessionStatus::Abandoned)
        ->and($abandoned->abandoned_at)->not->toBeNull()
        ->and($demo->status)->toBe(PomodoroSessionStatus::Paused)
        ->and($demo->elapsed_seconds)->toBe(480);
});

test('time entry factory covers manual timer project pomodoro and lifecycle states', function () {
    $project = Project::factory()->work()->create();
    $todo = Todo::factory()->forProject($project)->create();
    $session = PomodoroSession::factory()->forTodo($todo)->completed(1500)->create();

    $manual = TimeEntry::factory()->forTodo($todo)->manual(45)->create();
    $timer = TimeEntry::factory()->forTodo($todo)->timer(30)->create();
    $running = TimeEntry::factory()->forProject($project)->running(12)->create();
    $discarded = TimeEntry::factory()->forTodo($todo)->discarded(5)->create();
    $pomodoro = TimeEntry::factory()->fromPomodoro($session)->create();
    $demo = TimeEntry::factory()->forTodo($todo)->demo()->create();

    expect($manual->isOwnedBy($project->user))->toBeTrue()
        ->and($manual->todo->is($todo))->toBeTrue()
        ->and($manual->project->is($project))->toBeTrue()
        ->and($manual->duration_seconds)->toBe(2700)
        ->and($manual->source)->toBe(TimeEntrySource::Manual)
        ->and($manual->status)->toBe(TimeEntryStatus::Completed)
        ->and($timer->source)->toBe(TimeEntrySource::Timer)
        ->and($timer->duration_seconds)->toBe(1800)
        ->and($timer->started_at)->not->toBeNull()
        ->and($running->isRunning())->toBeTrue()
        ->and($running->todo_id)->toBeNull()
        ->and($running->project->is($project))->toBeTrue()
        ->and($discarded->isDiscarded())->toBeTrue()
        ->and($pomodoro->source)->toBe(TimeEntrySource::Pomodoro)
        ->and($pomodoro->pomodoroSession->is($session))->toBeTrue()
        ->and($demo->notes)->toBe('Reviewed task flow and captured the next improvement.');
});

test('goal and milestone factories cover ownership project and lifecycle states', function () {
    $project = Project::factory()->work()->create();
    $goal = Goal::factory()->forProject($project)->targetDate('2026-04-01')->create();
    $completedGoal = Goal::factory()->completed()->create();
    $archivedGoal = Goal::factory()->archived()->create();
    $milestone = GoalMilestone::factory()->forGoal($goal)->position(2)->targetDate('2026-03-15')->create();
    $completedMilestone = GoalMilestone::factory()->forGoal($goal)->completed()->create();

    expect($goal->isOwnedBy($project->user))->toBeTrue()
        ->and($goal->project_id)->toBe($project->id)
        ->and($goal->target_date->toDateString())->toBe('2026-04-01')
        ->and($completedGoal->isCompleted())->toBeTrue()
        ->and($archivedGoal->isArchived())->toBeTrue()
        ->and($milestone->isOwnedBy($goal->user))->toBeTrue()
        ->and($milestone->position)->toBe(2)
        ->and($milestone->target_date->toDateString())->toBe('2026-03-15')
        ->and($completedMilestone->isCompleted())->toBeTrue();
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

test('reminder factory covers due future processed skipped and owner states', function () {
    $user = User::factory()->create();
    $todos = Todo::factory()->for($user)->active()->count(4)->create();

    $due = Reminder::factory()->forTodo($todos[0])->due(now()->subMinutes(10))->create();
    $future = Reminder::factory()->forTodo($todos[1])->future(now()->addDay())->create();
    $processed = Reminder::factory()->forTodo($todos[2])->processed()->create();
    $skipped = Reminder::factory()->forTodo($todos[3])->skipped('preferences_disabled')->create();

    expect($due->isOwnedBy($user))->toBeTrue()
        ->and($due->todo->is($todos[0]))->toBeTrue()
        ->and($due->status)->toBe(ReminderStatus::Pending)
        ->and($due->isDue())->toBeTrue()
        ->and($future->isDue())->toBeFalse()
        ->and($processed->status)->toBe(ReminderStatus::Processed)
        ->and($processed->processed_at)->not->toBeNull()
        ->and($skipped->status)->toBe(ReminderStatus::Skipped)
        ->and($skipped->skipped_reason)->toBe('preferences_disabled')
        ->and($skipped->skipped_at)->not->toBeNull();
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

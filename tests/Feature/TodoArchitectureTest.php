<?php

use App\Actions\Automation\CreateAutomationRule;
use App\Actions\Automation\Processes\ArchiveCompletedTasksProcess;
use App\Actions\Automation\Processes\PromoteOverdueTasksProcess;
use App\Actions\Automation\RunAutomationRule;
use App\Actions\Automation\ToggleAutomationRule;
use App\Actions\Goals\CheckInGoalMilestone;
use App\Actions\Goals\CreateGoal;
use App\Actions\Goals\CreateGoalMilestone;
use App\Actions\Goals\LinkTodoToGoal;
use App\Actions\Habits\CreateHabit;
use App\Actions\Habits\LinkTodoToHabit;
use App\Actions\Habits\ToggleHabitCheckIn;
use App\Actions\Processing\RunManualWebProcess;
use App\Actions\Reminders\ProcessDueReminders;
use App\Actions\Reminders\Processes\ProcessDueRemindersProcess;
use App\Actions\Reminders\SyncTodoReminder;
use App\Actions\Reminders\ToggleReminderPreference;
use App\Actions\Todos\AbandonPomodoroSession;
use App\Actions\Todos\AddTodoDependency;
use App\Actions\Todos\CaptureInboxTodo;
use App\Actions\Todos\ClearCompletedTodos;
use App\Actions\Todos\CompletePomodoroSession;
use App\Actions\Todos\CompleteTodo;
use App\Actions\Todos\CreateManualTimeEntry;
use App\Actions\Todos\CreatePomodoroTimeEntry;
use App\Actions\Todos\CreateSavedTodoView;
use App\Actions\Todos\CreateTodo;
use App\Actions\Todos\CreateTodoChecklistItem;
use App\Actions\Todos\CreateTodoFromTemplate;
use App\Actions\Todos\CreateTodoTemplate;
use App\Actions\Todos\DeleteSavedTodoView;
use App\Actions\Todos\DeleteTimeEntry;
use App\Actions\Todos\DeleteTodo;
use App\Actions\Todos\DeleteTodoChecklistItem;
use App\Actions\Todos\DeleteTodoRecurrenceRule;
use App\Actions\Todos\DeleteTodoTemplate;
use App\Actions\Todos\DiscardTimeEntryTimer;
use App\Actions\Todos\MoveTodoChecklistItem;
use App\Actions\Todos\MoveTodoOnBoard;
use App\Actions\Todos\PausePomodoroSession;
use App\Actions\Todos\RemoveTodoDependency;
use App\Actions\Todos\ReopenTodo;
use App\Actions\Todos\RescheduleFocusedTodo;
use App\Actions\Todos\RestoreDeletedTodo;
use App\Actions\Todos\ResumePomodoroSession;
use App\Actions\Todos\SaveTodoRecurrenceRule;
use App\Actions\Todos\StartPomodoroSession;
use App\Actions\Todos\StartTimeEntryTimer;
use App\Actions\Todos\StopTimeEntryTimer;
use App\Actions\Todos\TodoLifecycleStateMachine;
use App\Actions\Todos\ToggleTodoChecklistItem;
use App\Actions\Todos\ToggleTodoRecurrenceRule;
use App\Actions\Todos\TriageInboxTodo;
use App\Actions\Todos\UpdateTodoChecklistItem;
use App\Actions\Todos\UpdateTodoTemplate;
use App\Contracts\Processing\ManualWebProcess;
use App\Data\Goals\GoalData;
use App\Data\Goals\GoalMilestoneData;
use App\Data\Goals\GoalProgress;
use App\Data\Habits\HabitData;
use App\Data\Habits\HabitProgress;
use App\Data\Processing\ManualWebProcessResult;
use App\Data\Reminders\ReminderData;
use App\Data\Reminders\ReminderProcessingResult;
use App\Data\Todos\BulkActionResult;
use App\Data\Todos\RecurrenceRuleData;
use App\Data\Todos\SavedTodoViewData;
use App\Data\Todos\TimeEntryData;
use App\Data\Todos\TodoCleanupFilters;
use App\Data\Todos\TodoData;
use App\Data\Todos\TodoTemplateData;
use App\Enums\AutomationRuleKind;
use App\Enums\AutomationRunStatus;
use App\Enums\PomodoroSessionStatus;
use App\Enums\RecurrenceEndType;
use App\Enums\RecurrenceFrequency;
use App\Enums\RecurrenceWeekday;
use App\Enums\ReminderStatus;
use App\Enums\TaskTemplateKind;
use App\Enums\TimeEntrySource;
use App\Enums\TimeEntryStatus;
use App\Enums\TodoTransition;
use App\Events\TodoChecklistChanged;
use App\Livewire\Forms\Todos\TodoForm;
use App\Livewire\Goals\Create as GoalsCreate;
use App\Livewire\Goals\CreateMilestone as GoalsCreateMilestone;
use App\Livewire\Goals\Index as GoalsIndex;
use App\Livewire\Habits\Create as HabitsCreate;
use App\Livewire\Habits\Index as HabitsIndex;
use App\Livewire\Notifications\Inbox as NotificationInbox;
use App\Livewire\Projects\Show as ProjectShow;
use App\Livewire\Todos\Automations as TodoAutomations;
use App\Livewire\Todos\Blocked as TodoBlocked;
use App\Livewire\Todos\Board as TodoBoard;
use App\Livewire\Todos\Calendar as TodoCalendar;
use App\Livewire\Todos\Cleanup as TodoCleanup;
use App\Livewire\Todos\Focus as TodoFocus;
use App\Livewire\Todos\Inbox as TodoInbox;
use App\Livewire\Todos\RecurringRules as TodoRecurringRules;
use App\Livewire\Todos\Reminders as TodoReminders;
use App\Livewire\Todos\Show as TodoShow;
use App\Livewire\Todos\Templates as TodoTemplates;
use App\Livewire\Todos\Time as TodoTime;
use App\Policies\AutomationRulePolicy;
use App\Policies\AutomationRuleRunPolicy;
use App\Policies\GoalMilestonePolicy;
use App\Policies\GoalPolicy;
use App\Policies\HabitCheckInPolicy;
use App\Policies\HabitPolicy;
use App\Policies\PomodoroSessionPolicy;
use App\Policies\ReminderPolicy;
use App\Policies\SavedTodoViewPolicy;
use App\Policies\TimeEntryPolicy;
use App\Policies\TodoChecklistItemPolicy;
use App\Policies\TodoDependencyPolicy;
use App\Policies\TodoPolicy;
use App\Policies\TodoRecurrenceRulePolicy;
use App\Policies\TodoTemplatePolicy;
use App\Queries\Automation\AutomationRuleQuery;
use App\Queries\Dashboard\DailyDashboardQuery;
use App\Queries\Dashboard\DailySummaryQuery;
use App\Queries\Goals\GoalListQuery;
use App\Queries\Habits\HabitListQuery;
use App\Queries\Notifications\NotificationInboxQuery;
use App\Queries\Reminders\ReminderListQuery;
use App\Queries\Todos\PomodoroSessionQuery;
use App\Queries\Todos\SavedTodoViewListQuery;
use App\Queries\Todos\TimeEntryQuery;
use App\Queries\Todos\TodoBoardQuery;
use App\Queries\Todos\TodoCalendarQuery;
use App\Queries\Todos\TodoChecklistItemListQuery;
use App\Queries\Todos\TodoCleanupQuery;
use App\Queries\Todos\TodoDependencyQuery;
use App\Queries\Todos\TodoFocusQuery;
use App\Queries\Todos\TodoInboxQuery;
use App\Queries\Todos\TodoListQuery;
use App\Queries\Todos\TodoRecurrenceRuleQuery;
use App\Queries\Todos\TodoTemplateListQuery;
use App\Rules\Automation\AutomationRuleName;
use App\Rules\Goals\GoalTitle;
use App\Rules\Goals\MilestoneTitle;
use App\Rules\Habits\HabitTargetCount;
use App\Rules\Habits\HabitTitle;
use App\Rules\Reminders\ReminderAt;
use App\Rules\Todos\AcyclicTodoDependency;
use App\Rules\Todos\BoardStatus;
use App\Rules\Todos\CalendarMonth;
use App\Rules\Todos\ChecklistItemTitle;
use App\Rules\Todos\InboxCaptureTitle;
use App\Rules\Todos\OwnedActiveTodo;
use App\Rules\Todos\PomodoroDuration;
use App\Rules\Todos\RecurrenceRule;
use App\Rules\Todos\SavedViewName;
use App\Rules\Todos\TemplateChecklistItems;
use App\Rules\Todos\TemplateName;
use App\Rules\Todos\TimeEntryDuration;

test('todo foundation classes exist', function () {
    expect(class_exists(TodoPolicy::class))->toBeTrue()
        ->and(class_exists(TodoForm::class))->toBeTrue()
        ->and(class_exists(TodoData::class))->toBeTrue()
        ->and(class_exists(TodoListQuery::class))->toBeTrue()
        ->and(class_exists(CreateTodo::class))->toBeTrue()
        ->and(class_exists(CompleteTodo::class))->toBeTrue()
        ->and(class_exists(ReopenTodo::class))->toBeTrue()
        ->and(class_exists(DeleteTodo::class))->toBeTrue()
        ->and(class_exists(RestoreDeletedTodo::class))->toBeTrue()
        ->and(class_exists(MoveTodoOnBoard::class))->toBeTrue()
        ->and(class_exists(CreateTodoChecklistItem::class))->toBeTrue()
        ->and(class_exists(UpdateTodoChecklistItem::class))->toBeTrue()
        ->and(class_exists(ToggleTodoChecklistItem::class))->toBeTrue()
        ->and(class_exists(MoveTodoChecklistItem::class))->toBeTrue()
        ->and(class_exists(DeleteTodoChecklistItem::class))->toBeTrue()
        ->and(class_exists(CreateTodoTemplate::class))->toBeTrue()
        ->and(class_exists(UpdateTodoTemplate::class))->toBeTrue()
        ->and(class_exists(DeleteTodoTemplate::class))->toBeTrue()
        ->and(class_exists(CreateTodoFromTemplate::class))->toBeTrue()
        ->and(class_exists(CaptureInboxTodo::class))->toBeTrue()
        ->and(class_exists(TriageInboxTodo::class))->toBeTrue()
        ->and(class_exists(RescheduleFocusedTodo::class))->toBeTrue()
        ->and(class_exists(AddTodoDependency::class))->toBeTrue()
        ->and(class_exists(RemoveTodoDependency::class))->toBeTrue()
        ->and(class_exists(SaveTodoRecurrenceRule::class))->toBeTrue()
        ->and(class_exists(DeleteTodoRecurrenceRule::class))->toBeTrue()
        ->and(class_exists(ToggleTodoRecurrenceRule::class))->toBeTrue()
        ->and(class_exists(RecurrenceRuleData::class))->toBeTrue()
        ->and(class_exists(TodoRecurrenceRuleQuery::class))->toBeTrue()
        ->and(class_exists(TodoRecurrenceRulePolicy::class))->toBeTrue()
        ->and(class_exists(RecurrenceRule::class))->toBeTrue()
        ->and(class_exists(OwnedActiveTodo::class))->toBeTrue()
        ->and(enum_exists(RecurrenceFrequency::class))->toBeTrue()
        ->and(enum_exists(RecurrenceEndType::class))->toBeTrue()
        ->and(enum_exists(RecurrenceWeekday::class))->toBeTrue()
        ->and(class_exists(StartPomodoroSession::class))->toBeTrue()
        ->and(class_exists(PausePomodoroSession::class))->toBeTrue()
        ->and(class_exists(ResumePomodoroSession::class))->toBeTrue()
        ->and(class_exists(CompletePomodoroSession::class))->toBeTrue()
        ->and(class_exists(AbandonPomodoroSession::class))->toBeTrue()
        ->and(class_exists(CreateManualTimeEntry::class))->toBeTrue()
        ->and(class_exists(StartTimeEntryTimer::class))->toBeTrue()
        ->and(class_exists(StopTimeEntryTimer::class))->toBeTrue()
        ->and(class_exists(DiscardTimeEntryTimer::class))->toBeTrue()
        ->and(class_exists(CreatePomodoroTimeEntry::class))->toBeTrue()
        ->and(class_exists(DeleteTimeEntry::class))->toBeTrue()
        ->and(class_exists(CreateGoal::class))->toBeTrue()
        ->and(class_exists(CreateGoalMilestone::class))->toBeTrue()
        ->and(class_exists(CheckInGoalMilestone::class))->toBeTrue()
        ->and(class_exists(LinkTodoToGoal::class))->toBeTrue()
        ->and(class_exists(CreateHabit::class))->toBeTrue()
        ->and(class_exists(ToggleHabitCheckIn::class))->toBeTrue()
        ->and(class_exists(LinkTodoToHabit::class))->toBeTrue()
        ->and(class_exists(TodoLifecycleStateMachine::class))->toBeTrue()
        ->and(class_exists(CreateSavedTodoView::class))->toBeTrue()
        ->and(class_exists(DeleteSavedTodoView::class))->toBeTrue()
        ->and(class_exists(SavedTodoViewData::class))->toBeTrue()
        ->and(class_exists(TimeEntryData::class))->toBeTrue()
        ->and(class_exists(TodoCleanupFilters::class))->toBeTrue()
        ->and(class_exists(TodoTemplateData::class))->toBeTrue()
        ->and(class_exists(GoalData::class))->toBeTrue()
        ->and(class_exists(GoalMilestoneData::class))->toBeTrue()
        ->and(class_exists(GoalProgress::class))->toBeTrue()
        ->and(class_exists(HabitData::class))->toBeTrue()
        ->and(class_exists(HabitProgress::class))->toBeTrue()
        ->and(class_exists(ReminderData::class))->toBeTrue()
        ->and(class_exists(ReminderProcessingResult::class))->toBeTrue()
        ->and(class_exists(GoalListQuery::class))->toBeTrue()
        ->and(class_exists(HabitListQuery::class))->toBeTrue()
        ->and(class_exists(DailySummaryQuery::class))->toBeTrue()
        ->and(class_exists(DailyDashboardQuery::class))->toBeTrue()
        ->and(class_exists(NotificationInboxQuery::class))->toBeTrue()
        ->and(class_exists(ReminderListQuery::class))->toBeTrue()
        ->and(class_exists(PomodoroSessionQuery::class))->toBeTrue()
        ->and(class_exists(TimeEntryQuery::class))->toBeTrue()
        ->and(class_exists(SavedTodoViewListQuery::class))->toBeTrue()
        ->and(class_exists(TodoBoardQuery::class))->toBeTrue()
        ->and(class_exists(TodoCalendarQuery::class))->toBeTrue()
        ->and(class_exists(TodoChecklistItemListQuery::class))->toBeTrue()
        ->and(class_exists(TodoCleanupQuery::class))->toBeTrue()
        ->and(class_exists(TodoDependencyQuery::class))->toBeTrue()
        ->and(class_exists(TodoFocusQuery::class))->toBeTrue()
        ->and(class_exists(TodoTemplateListQuery::class))->toBeTrue()
        ->and(class_exists(TodoInboxQuery::class))->toBeTrue()
        ->and(class_exists(BoardStatus::class))->toBeTrue()
        ->and(class_exists(AcyclicTodoDependency::class))->toBeTrue()
        ->and(class_exists(CalendarMonth::class))->toBeTrue()
        ->and(class_exists(ChecklistItemTitle::class))->toBeTrue()
        ->and(class_exists(InboxCaptureTitle::class))->toBeTrue()
        ->and(class_exists(GoalTitle::class))->toBeTrue()
        ->and(class_exists(MilestoneTitle::class))->toBeTrue()
        ->and(class_exists(HabitTitle::class))->toBeTrue()
        ->and(class_exists(HabitTargetCount::class))->toBeTrue()
        ->and(class_exists(PomodoroDuration::class))->toBeTrue()
        ->and(class_exists(TimeEntryDuration::class))->toBeTrue()
        ->and(class_exists(TemplateChecklistItems::class))->toBeTrue()
        ->and(class_exists(TemplateName::class))->toBeTrue()
        ->and(class_exists(SavedViewName::class))->toBeTrue()
        ->and(class_exists(ReminderAt::class))->toBeTrue()
        ->and(class_exists(BulkActionResult::class))->toBeTrue()
        ->and(class_exists(SavedTodoViewPolicy::class))->toBeTrue()
        ->and(class_exists(TodoChecklistItemPolicy::class))->toBeTrue()
        ->and(class_exists(TodoDependencyPolicy::class))->toBeTrue()
        ->and(class_exists(TodoTemplatePolicy::class))->toBeTrue()
        ->and(class_exists(GoalPolicy::class))->toBeTrue()
        ->and(class_exists(GoalMilestonePolicy::class))->toBeTrue()
        ->and(class_exists(HabitPolicy::class))->toBeTrue()
        ->and(class_exists(HabitCheckInPolicy::class))->toBeTrue()
        ->and(class_exists(PomodoroSessionPolicy::class))->toBeTrue()
        ->and(class_exists(TimeEntryPolicy::class))->toBeTrue()
        ->and(class_exists(ReminderPolicy::class))->toBeTrue()
        ->and(class_exists(TodoBoard::class))->toBeTrue()
        ->and(class_exists(TodoCalendar::class))->toBeTrue()
        ->and(class_exists(TodoBlocked::class))->toBeTrue()
        ->and(class_exists(TodoCleanup::class))->toBeTrue()
        ->and(class_exists(TodoShow::class))->toBeTrue()
        ->and(class_exists(TodoFocus::class))->toBeTrue()
        ->and(class_exists(GoalsCreate::class))->toBeTrue()
        ->and(class_exists(GoalsCreateMilestone::class))->toBeTrue()
        ->and(class_exists(GoalsIndex::class))->toBeTrue()
        ->and(class_exists(HabitsCreate::class))->toBeTrue()
        ->and(class_exists(HabitsIndex::class))->toBeTrue()
        ->and(class_exists(TodoTemplates::class))->toBeTrue()
        ->and(class_exists(TodoRecurringRules::class))->toBeTrue()
        ->and(class_exists(TodoInbox::class))->toBeTrue()
        ->and(class_exists(TodoTime::class))->toBeTrue()
        ->and(class_exists(TodoReminders::class))->toBeTrue()
        ->and(class_exists(NotificationInbox::class))->toBeTrue()
        ->and(class_exists(ProjectShow::class))->toBeTrue()
        ->and(class_exists(TodoChecklistChanged::class))->toBeTrue()
        ->and(enum_exists(PomodoroSessionStatus::class))->toBeTrue()
        ->and(enum_exists(TimeEntrySource::class))->toBeTrue()
        ->and(enum_exists(TimeEntryStatus::class))->toBeTrue()
        ->and(enum_exists(TodoTransition::class))->toBeTrue()
        ->and(enum_exists(TaskTemplateKind::class))->toBeTrue()
        ->and(enum_exists(ReminderStatus::class))->toBeTrue()
        ->and(class_exists(ClearCompletedTodos::class))->toBeTrue()
        ->and(class_exists(CreateAutomationRule::class))->toBeTrue()
        ->and(class_exists(ToggleAutomationRule::class))->toBeTrue()
        ->and(class_exists(RunAutomationRule::class))->toBeTrue()
        ->and(class_exists(SyncTodoReminder::class))->toBeTrue()
        ->and(class_exists(ToggleReminderPreference::class))->toBeTrue()
        ->and(class_exists(ProcessDueReminders::class))->toBeTrue()
        ->and(class_exists(ProcessDueRemindersProcess::class))->toBeTrue()
        ->and(class_exists(RunManualWebProcess::class))->toBeTrue()
        ->and(interface_exists(ManualWebProcess::class))->toBeTrue()
        ->and(class_exists(ManualWebProcessResult::class))->toBeTrue()
        ->and(class_exists(PromoteOverdueTasksProcess::class))->toBeTrue()
        ->and(class_exists(ArchiveCompletedTasksProcess::class))->toBeTrue()
        ->and(class_exists(AutomationRuleQuery::class))->toBeTrue()
        ->and(class_exists(AutomationRuleName::class))->toBeTrue()
        ->and(class_exists(AutomationRulePolicy::class))->toBeTrue()
        ->and(class_exists(AutomationRuleRunPolicy::class))->toBeTrue()
        ->and(class_exists(TodoAutomations::class))->toBeTrue()
        ->and(enum_exists(AutomationRuleKind::class))->toBeTrue()
        ->and(enum_exists(AutomationRunStatus::class))->toBeTrue();
});

test('habits page delegates habit responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Habits/Index.php'));
    $createSource = file_get_contents(app_path('Livewire/Habits/Create.php'));

    expect($source)
        ->toContain('HabitListQuery')
        ->toContain('ToggleHabitCheckIn')
        ->toContain('LinkTodoToHabit')
        ->toContain('$this->authorize')
        ->not->toContain('Habit::query()')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()')
        ->and($createSource)
        ->toContain('CreateHabit')
        ->toContain('GoalListQuery')
        ->toContain('HabitTitle')
        ->toContain('HabitTargetCount')
        ->toContain('$this->authorize')
        ->not->toContain('Habit::query()')
        ->not->toContain('->save()');
});

test('goals page delegates goal responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Goals/Index.php'));
    $createSource = file_get_contents(app_path('Livewire/Goals/Create.php'));
    $milestoneSource = file_get_contents(app_path('Livewire/Goals/CreateMilestone.php'));

    expect($source)
        ->toContain('GoalListQuery')
        ->toContain('CheckInGoalMilestone')
        ->toContain('LinkTodoToGoal')
        ->toContain('$this->authorize')
        ->not->toContain('CreateGoal')
        ->not->toContain('CreateGoalMilestone')
        ->not->toContain('Goal::query()')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()')
        ->and($createSource)
        ->toContain('CreateGoal')
        ->toContain('GoalTitle')
        ->toContain('ProjectListQuery')
        ->toContain('$this->authorize')
        ->not->toContain('Goal::query()')
        ->not->toContain('->save()')
        ->and($milestoneSource)
        ->toContain('CreateGoalMilestone')
        ->toContain('MilestoneTitle')
        ->toContain('GoalListQuery')
        ->toContain('$this->authorize')
        ->not->toContain('Goal::query()')
        ->not->toContain('->save()');
});

test('todo livewire page delegates domain responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Index.php'));

    expect($source)
        ->toContain('TodoForm')
        ->toContain('CreateTodo')
        ->toContain('TodoListQuery')
        ->toContain('SavedTodoViewListQuery')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('->create([');
});

test('todo board page delegates movement responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Board.php'));

    expect($source)
        ->toContain('TodoBoardQuery')
        ->toContain('MoveTodoOnBoard')
        ->toContain('BoardStatus')
        ->toContain('OwnedActiveProject')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()');
});

test('todo calendar page delegates date responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Calendar.php'));

    expect($source)
        ->toContain('TodoCalendarQuery')
        ->toContain('CalendarMonth')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()');
});

test('todo detail page delegates checklist dependency and recurrence responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Show.php'));

    expect($source)
        ->toContain('TodoChecklistItemListQuery')
        ->toContain('CreateTodoChecklistItem')
        ->toContain('UpdateTodoChecklistItem')
        ->toContain('ToggleTodoChecklistItem')
        ->toContain('MoveTodoChecklistItem')
        ->toContain('DeleteTodoChecklistItem')
        ->toContain('TodoDependencyQuery')
        ->toContain('AddTodoDependency')
        ->toContain('RemoveTodoDependency')
        ->toContain('AcyclicTodoDependency')
        ->toContain('TodoRecurrenceRuleQuery')
        ->toContain('SaveTodoRecurrenceRule')
        ->toContain('DeleteTodoRecurrenceRule')
        ->toContain('RecurrenceRuleData')
        ->toContain('RecurrenceRule')
        ->toContain('ChecklistItemTitle')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('TodoDependency::query()')
        ->not->toContain('TodoChecklistItem::query()')
        ->not->toContain('TodoRecurrenceRule::query()')
        ->not->toContain('->save()');
});

test('todo recurring page delegates recurrence responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/RecurringRules.php'));
    $viewSource = file_get_contents(resource_path('views/livewire/todos/recurring-rules.blade.php'));

    expect($source)
        ->toContain('TodoRecurrenceRuleQuery')
        ->toContain('SaveTodoRecurrenceRule')
        ->toContain('DeleteTodoRecurrenceRule')
        ->toContain('ToggleTodoRecurrenceRule')
        ->toContain('GenerateRecurringOccurrences')
        ->toContain('RecurrenceRuleData')
        ->toContain('RecurrenceRule')
        ->toContain('OwnedActiveTodo')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('TodoRecurrenceRule::query()')
        ->not->toContain('->save()')
        ->and($viewSource)
        ->toContain('<x-ui.page-header')
        ->toContain('<flux:card')
        ->toContain('todos.pages.recurring.title')
        ->not->toContain('@php');
});

test('todo blocked page delegates dependency responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Blocked.php'));

    expect($source)
        ->toContain('TodoListQuery')
        ->toContain('blockedFor($this->currentUser()')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('TodoDependency::query()')
        ->not->toContain('->save()');
});

test('todo cleanup page delegates smart view responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Cleanup.php'));

    expect($source)
        ->toContain('TodoCleanupQuery')
        ->toContain('TodoCleanupFilters')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('TodoDependency::query()')
        ->not->toContain('->save()');
});

test('todo automations page delegates automation responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Automations.php'));

    expect($source)
        ->toContain('AutomationRuleQuery')
        ->toContain('CreateAutomationRule')
        ->toContain('ToggleAutomationRule')
        ->toContain('RunAutomationRule')
        ->toContain('AutomationRuleName')
        ->toContain('$this->authorize')
        ->not->toContain('AutomationRule::query()')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()');
});

test('automation runner delegates chunk processing to reusable web engine', function () {
    $source = file_get_contents(app_path('Actions/Automation/RunAutomationRule.php'));

    expect($source)
        ->toContain('RunManualWebProcess')
        ->toContain('ManualWebProcessResult')
        ->toContain('processFor')
        ->not->toContain('->limit($this->chunkSize())')
        ->not->toContain('config(\'hosting.web_processing.chunk_size\'');
});

test('todo reminders page delegates reminder responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Reminders.php'));
    $processorSource = file_get_contents(app_path('Actions/Reminders/ProcessDueReminders.php'));
    $processSource = file_get_contents(app_path('Actions/Reminders/Processes/ProcessDueRemindersProcess.php'));
    $viewSource = file_get_contents(resource_path('views/livewire/todos/reminders.blade.php'));

    expect($source)
        ->toContain('ReminderListQuery')
        ->toContain('SyncTodoReminder')
        ->toContain('ToggleReminderPreference')
        ->toContain('ProcessDueReminders')
        ->toContain('ReminderData')
        ->toContain('ReminderAt')
        ->toContain('OwnedTodo')
        ->toContain('$this->authorize')
        ->not->toContain('Reminder::query()')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()')
        ->and($processorSource)
        ->toContain('RunManualWebProcess')
        ->toContain('ProcessDueRemindersProcess')
        ->and($processSource)
        ->toContain('ManualWebProcess')
        ->toContain('TodoReminderDueNotification')
        ->toContain('reminders_enabled')
        ->toContain('$user->reminders()')
        ->and($viewSource)
        ->toContain('<x-ui.page-header')
        ->toContain('<flux:callout')
        ->toContain('reminders.pages.index.title')
        ->not->toContain('@php');
});

test('notification center delegates notification responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Notifications/Inbox.php'));
    $querySource = file_get_contents(app_path('Queries/Notifications/NotificationInboxQuery.php'));
    $viewSource = file_get_contents(resource_path('views/livewire/notifications/inbox.blade.php'));

    expect($source)
        ->toContain('NotificationInboxQuery')
        ->toContain('findFor($this->currentUser()')
        ->toContain('unreadNotifications()')
        ->not->toContain('DatabaseNotification::query()')
        ->and($querySource)
        ->toContain('DatabaseNotification::query()')
        ->toContain('notifiable_type')
        ->toContain('notifiable_id')
        ->and($viewSource)
        ->toContain('<x-ui.page-header')
        ->toContain('notifications.pages.inbox.title')
        ->not->toContain('@php');
});

test('dashboard page delegates daily summary responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Dashboard/Index.php'));
    $querySource = file_get_contents(app_path('Queries/Dashboard/DailyDashboardQuery.php'));
    $viewSource = file_get_contents(resource_path('views/livewire/dashboard/index.blade.php'));

    expect($source)
        ->toContain('DailyDashboardQuery')
        ->toContain('DailySummaryQuery')
        ->toContain('ProcessDueReminders')
        ->not->toContain('Todo::query()')
        ->not->toContain('Reminder::query()')
        ->not->toContain('DatabaseNotification::query()')
        ->and($querySource)
        ->toContain('Todo::query()')
        ->toContain('TimeEntry::query()')
        ->toContain('Reminder::query()')
        ->toContain('NotificationInboxQuery')
        ->toContain('->ownedBy($user)')
        ->toContain('selectRaw')
        ->toContain('unreadCountFor($user)')
        ->and($viewSource)
        ->toContain('<flux:card')
        ->toContain('<flux:progress')
        ->toContain('dashboard.daily.heading')
        ->toContain('dashboard.daily.stats.due_today')
        ->toContain('data-test="dashboard-daily-summary"')
        ->not->toContain('@php');
});

test('todo templates page delegates template responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Templates.php'));

    expect($source)
        ->toContain('TodoTemplateListQuery')
        ->toContain('CreateTodoTemplate')
        ->toContain('UpdateTodoTemplate')
        ->toContain('DeleteTodoTemplate')
        ->toContain('CreateTodoFromTemplate')
        ->toContain('TemplateChecklistItems')
        ->toContain('TemplateName')
        ->toContain('$this->authorize')
        ->not->toContain('TodoTemplate::query()')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()');
});

test('todo inbox page delegates capture and triage responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Inbox.php'));

    expect($source)
        ->toContain('TodoInboxQuery')
        ->toContain('CaptureInboxTodo')
        ->toContain('TriageInboxTodo')
        ->toContain('InboxCaptureTitle')
        ->toContain('TodoForm')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('->save()');
});

test('todo focus page delegates focus responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Focus.php'));

    expect($source)
        ->toContain('TodoFocusQuery')
        ->toContain('PomodoroSessionQuery')
        ->toContain('RescheduleFocusedTodo')
        ->toContain('CompleteTodo')
        ->toContain('StartPomodoroSession')
        ->toContain('PausePomodoroSession')
        ->toContain('ResumePomodoroSession')
        ->toContain('CompletePomodoroSession')
        ->toContain('AbandonPomodoroSession')
        ->toContain('PomodoroDuration')
        ->toContain('$this->authorize')
        ->not->toContain('Todo::query()')
        ->not->toContain('PomodoroSession::query()')
        ->not->toContain('->save()');
});

test('todo time page delegates time tracking responsibilities', function () {
    $source = file_get_contents(app_path('Livewire/Todos/Time.php'));

    expect($source)
        ->toContain('TimeEntryQuery')
        ->toContain('CreateManualTimeEntry')
        ->toContain('StartTimeEntryTimer')
        ->toContain('StopTimeEntryTimer')
        ->toContain('DiscardTimeEntryTimer')
        ->toContain('DeleteTimeEntry')
        ->toContain('TimeEntryData')
        ->toContain('TimeEntryDuration')
        ->toContain('$this->authorize')
        ->not->toContain('TimeEntry::query()')
        ->not->toContain('Todo::query()')
        ->not->toContain('Project::query()')
        ->not->toContain('->save()');
});

test('todo blade view uses translation keys and shared ui components', function () {
    $source = file_get_contents(resource_path('views/livewire/todos/index.blade.php'));

    expect($source)
        ->toContain('<x-ui.page-header')
        ->toContain('<x-ui.empty-state')
        ->toContain('todos.pages.index.title')
        ->not->toContain('Mini todos')
        ->not->toContain('No todos yet.')
        ->not->toContain('Todo added.');
});

test('todo documentation exists for future implementation steps', function () {
    expect(file_exists(base_path('docs/todo-foundation.md')))->toBeTrue()
        ->and(file_exists(base_path('docs/changelog.md')))->toBeTrue()
        ->and(file_exists(base_path('docs/authorization.md')))->toBeTrue()
        ->and(file_exists(base_path('docs/task-lifecycle.md')))->toBeTrue()
        ->and(file_exists(base_path('docs/task-organization.md')))->toBeTrue()
        ->and(file_exists(base_path('docs/automation-rules.md')))->toBeTrue()
        ->and(file_exists(base_path('docs/notifications.md')))->toBeTrue();
});

test('todo model routes ownership through the shared concern and explicit policy', function () {
    $source = file_get_contents(app_path('Models/Todo.php'));

    expect($source)
        ->toContain('use App\Models\Concerns\BelongsToUser;')
        ->toContain('BelongsToUser')
        ->toContain('#[UsePolicy(TodoPolicy::class)]')
        ->not->toContain("'user_id'");
});

test('todo read queries flow through the owner scope', function () {
    $source = file_get_contents(app_path('Queries/Todos/TodoListQuery.php'));

    expect($source)->toContain('->ownedBy(');
});

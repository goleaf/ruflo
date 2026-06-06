<?php

namespace Database\Seeders;

use App\Data\Todos\SavedTodoViewData;
use App\Enums\HabitFrequency;
use App\Enums\PomodoroSessionStatus;
use App\Enums\Priority;
use App\Enums\TimeEntrySource;
use App\Enums\TimeEntryStatus;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\PomodoroSession;
use App\Models\Project;
use App\Models\SavedTodoView;
use App\Models\Tag;
use App\Models\TimeEntry;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\TodoDependency;
use App\Models\TodoTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic, isolated todo workspaces.
 *
 * Every user gets projects, tags, and tasks across the full range of states
 * (active, due today, overdue, upcoming, high priority, completed, archived,
 * trashed) so that ownership, filters, due-date buckets, and bulk actions can
 * be exercised by hand and so permission bugs cannot hide behind thin
 * single-user data.
 */
class TodoSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->each(function (User $user): void {
            $this->seedWorkspace($user);
        });
    }

    private function seedWorkspace(User $user): void
    {
        $work = $this->upsertProject($user, 'Work', 'blue');
        $home = $this->upsertProject($user, 'Home', 'green');
        $this->upsertProject($user, 'Old plans', 'zinc', archived: true);

        $urgent = $this->upsertTag($user, 'urgent', 'red');
        $waiting = $this->upsertTag($user, 'waiting', 'amber');

        $reviewFlow = $this->upsertTodo($user, 'Review the current flow', [
            'project_id' => $work->id,
            'priority' => Priority::High,
            'due_date' => today()->toDateString(),
        ], $urgent);
        $this->upsertChecklist($reviewFlow, [
            ['title' => 'Confirm the owner-scoped route', 'completed' => true],
            ['title' => 'Check the empty state copy'],
            ['title' => 'Record the next UI note'],
        ]);

        $overdueReport = $this->upsertTodo($user, 'Send the overdue report', [
            'project_id' => $work->id,
            'priority' => Priority::Urgent,
            'due_date' => today()->subDay()->toDateString(),
        ], $urgent, $waiting);
        $this->upsertChecklist($overdueReport, [
            ['title' => 'Pull the latest metrics'],
            ['title' => 'Send the short summary'],
        ]);

        $weekend = $this->upsertTodo($user, 'Plan the weekend', [
            'project_id' => $home->id,
            'priority' => Priority::Normal,
            'due_date' => today()->addDays(3)->toDateString(),
        ]);
        $this->upsertChecklist($weekend, [
            ['title' => 'Pick one outdoor plan'],
            ['title' => 'Reserve a quiet block'],
        ]);

        $this->upsertTodo($user, 'Capture a loose idea', [
            'priority' => Priority::Low,
            'inbox_captured_at' => now()->subMinutes(45),
        ]);

        $this->upsertTodo($user, 'Review the quick note', [
            'priority' => Priority::Normal,
            'inbox_captured_at' => now()->subMinutes(15),
        ]);

        $smallImprovement = $this->upsertTodo($user, 'Ship one small improvement', [
            'project_id' => $work->id,
            'is_completed' => true,
            'priority' => Priority::Normal,
        ]);

        $archivedChecklist = $this->upsertTodo($user, 'Last month\'s checklist', [
            'project_id' => $home->id,
            'priority' => Priority::Normal,
            'archived_at' => now(),
        ]);
        $this->upsertChecklist($archivedChecklist, [
            ['title' => 'Review completed notes', 'completed' => true],
            ['title' => 'Keep archived context visible', 'completed' => true],
        ]);

        $this->upsertTodo($user, 'Archived completed launch notes', [
            'project_id' => $work->id,
            'is_completed' => true,
            'priority' => Priority::High,
            'archived_at' => now(),
        ], $waiting);

        $this->upsertTodo($user, 'Removed duplicate task', [
            'project_id' => $home->id,
            'priority' => Priority::Low,
            'deleted_at' => now()->subDays(2),
        ]);

        $this->upsertSavedView($user, 'Today focus', [
            'due' => 'today',
            'sort' => 'due',
            'direction' => 'asc',
        ]);

        $this->upsertSavedView($user, 'Urgent work', [
            'project' => (string) $work->id,
            'priorityFilter' => Priority::Urgent->value,
            'sort' => 'priority',
            'direction' => 'desc',
        ]);

        $this->upsertSavedView($user, 'Waiting on others', [
            'tag' => (string) $waiting->id,
            'sort' => 'updated',
            'direction' => 'desc',
        ]);

        $this->upsertTemplate($user, 'Daily planning routine', [
            'kind' => 'routine',
            'visibility' => 'private',
            'title' => 'Plan today',
            'description' => 'A short routine for picking the next useful tasks.',
            'priority' => Priority::High,
            'due_offset_days' => 0,
            'project_name' => 'Work',
            'checklist_items' => [
                'Review overdue tasks',
                'Pick three priorities',
                'Block one focused session',
            ],
        ]);

        $this->upsertTemplate($user, 'Project kickoff', [
            'kind' => 'project',
            'visibility' => 'shared',
            'title' => 'Start the project kickoff',
            'description' => 'Creates a project-backed kickoff task with starter checklist items.',
            'priority' => Priority::Normal,
            'due_offset_days' => 3,
            'project_name' => 'Project kickoff',
            'checklist_items' => [
                'Confirm the goal',
                'List the first milestones',
                'Choose the next owner action',
            ],
        ]);

        $this->upsertTemplate($user, 'Bug triage checklist', [
            'kind' => 'checklist',
            'visibility' => 'private',
            'title' => 'Triage a bug report',
            'description' => 'A repeatable checklist for turning an issue into clear next work.',
            'priority' => Priority::Urgent,
            'due_offset_days' => null,
            'project_name' => 'Work',
            'checklist_items' => [
                'Reproduce the report',
                'Capture expected behavior',
                'Decide fix or backlog',
            ],
        ]);

        $commandCenterGoal = $this->upsertGoal($user, 'Launch the personal command center', [
            'project_id' => $work->id,
            'description' => 'Tie the daily workspace to clear, measurable outcomes.',
            'target_date' => today()->addWeeks(2)->toDateString(),
        ]);

        $foundationMilestone = $this->upsertMilestone($commandCenterGoal, 'Confirm the foundation', [
            'position' => 1,
            'completed' => true,
            'target_date' => today()->toDateString(),
        ]);

        $focusMilestone = $this->upsertMilestone($commandCenterGoal, 'Review the focus flow', [
            'position' => 2,
            'completed' => false,
            'target_date' => today()->addDays(5)->toDateString(),
        ]);

        $this->linkTodoToGoal($smallImprovement, $commandCenterGoal, $foundationMilestone);
        $this->linkTodoToGoal($reviewFlow, $commandCenterGoal, $focusMilestone);
        $this->linkTodoToGoal($overdueReport, $commandCenterGoal);
        $this->upsertPomodoroSession($reviewFlow);
        $this->upsertDependency($overdueReport, $reviewFlow);
        $this->upsertTimeEntry($reviewFlow, 35, today()->toDateString(), 'Reviewed task flow and captured the next improvement.');
        $this->upsertProjectTimeEntry($work, 25, today()->subDays(2)->toDateString(), 'Planned the next quiet work block.');

        $weekendGoal = $this->upsertGoal($user, 'Plan a calmer weekend', [
            'project_id' => $home->id,
            'description' => 'Turn loose weekend planning into one visible outcome.',
            'target_date' => today()->addDays(10)->toDateString(),
        ]);

        $this->linkTodoToGoal($weekend, $weekendGoal, $this->upsertMilestone($weekendGoal, 'Pick the first plan', [
            'position' => 1,
            'completed' => false,
            'target_date' => today()->addDays(3)->toDateString(),
        ]));

        $dailyPlanning = $this->upsertHabit($user, 'Plan the day', [
            'goal_id' => $commandCenterGoal->id,
            'description' => 'Choose the first useful action before the day gets noisy.',
            'frequency' => HabitFrequency::Daily,
            'target_count' => 1,
        ]);

        foreach ([today(), today()->subDay(), today()->subDays(2)] as $date) {
            $this->upsertHabitCheckIn($dailyPlanning, $date->toDateString());
        }

        $weeklyReview = $this->upsertHabit($user, 'Run the weekly review', [
            'goal_id' => $commandCenterGoal->id,
            'description' => 'Review active goals, waiting work, and next-week commitments.',
            'frequency' => HabitFrequency::Weekly,
            'target_count' => 1,
        ]);

        foreach ([today(), today()->subWeek(), today()->subWeeks(2)] as $date) {
            $this->upsertHabitCheckIn($weeklyReview, $date->toDateString());
        }

        $this->linkTodoToHabit($reviewFlow, $dailyPlanning);
        $this->linkTodoToHabit($overdueReport, $weeklyReview);
    }

    private function upsertProject(User $user, string $name, string $color, bool $archived = false): Project
    {
        $project = Project::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first() ?? new Project;

        $project->forceFill([
            'user_id' => $user->id,
            'name' => $name,
            'color' => $color,
            'archived_at' => $archived ? ($project->archived_at ?? now()) : null,
        ])->save();

        return $project;
    }

    private function upsertTag(User $user, string $name, string $color): Tag
    {
        $tag = Tag::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first() ?? new Tag;

        $tag->forceFill([
            'user_id' => $user->id,
            'name' => $name,
            'color' => $color,
        ])->save();

        return $tag;
    }

    /**
     * @param  array{project_id?: int, priority?: Priority, due_date?: string|null, is_completed?: bool, archived_at?: mixed, deleted_at?: mixed, inbox_captured_at?: mixed}  $attributes
     */
    private function upsertTodo(User $user, string $title, array $attributes, Tag ...$tags): Todo
    {
        $todo = Todo::query()
            ->withTrashed()
            ->where('user_id', $user->id)
            ->where('title', $title)
            ->first() ?? new Todo;

        $todo->forceFill([
            'user_id' => $user->id,
            'title' => $title,
            'project_id' => $attributes['project_id'] ?? null,
            'priority' => $attributes['priority'] ?? Priority::Normal,
            'due_date' => $attributes['due_date'] ?? null,
            'is_completed' => $attributes['is_completed'] ?? false,
            'archived_at' => $attributes['archived_at'] ?? null,
            'deleted_at' => $attributes['deleted_at'] ?? null,
            'inbox_captured_at' => $attributes['inbox_captured_at'] ?? null,
        ])->save();

        $todo->tags()->sync(collect($tags)->pluck('id')->all());

        return $todo;
    }

    /**
     * @param  list<array{title: string, completed?: bool}>  $items
     */
    private function upsertChecklist(Todo $todo, array $items): void
    {
        foreach ($items as $index => $itemData) {
            $completed = $itemData['completed'] ?? false;

            $item = TodoChecklistItem::query()
                ->where('todo_id', $todo->id)
                ->where('title', $itemData['title'])
                ->first() ?? new TodoChecklistItem;

            $item->forceFill([
                'user_id' => $todo->user_id,
                'todo_id' => $todo->id,
                'title' => $itemData['title'],
                'is_completed' => $completed,
                'completed_at' => $completed ? ($item->completed_at ?? now()) : null,
                'position' => $index + 1,
            ])->save();
        }
    }

    /**
     * @param  array{kind: string, visibility: string, title: string, description: string, priority: Priority, due_offset_days?: int|null, project_name?: string|null, checklist_items: list<string>}  $attributes
     */
    private function upsertTemplate(User $user, string $name, array $attributes): TodoTemplate
    {
        $template = TodoTemplate::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first() ?? new TodoTemplate;

        $template->forceFill([
            'user_id' => $user->id,
            'name' => $name,
            'kind' => $attributes['kind'],
            'visibility' => $attributes['visibility'],
            'title' => $attributes['title'],
            'description' => $attributes['description'],
            'priority' => $attributes['priority'],
            'due_offset_days' => $attributes['due_offset_days'] ?? null,
            'project_name' => $attributes['project_name'] ?? null,
            'checklist_items' => $attributes['checklist_items'],
        ])->save();

        return $template;
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function upsertSavedView(User $user, string $name, array $criteria): SavedTodoView
    {
        $savedView = SavedTodoView::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first() ?? new SavedTodoView;

        $savedView->forceFill([
            'user_id' => $user->id,
            'name' => $name,
            'criteria' => SavedTodoViewData::normalizeCriteria($criteria),
        ])->save();

        return $savedView;
    }

    /**
     * @param  array{project_id?: int|null, description?: string|null, target_date?: string|null, completed?: bool, archived?: bool}  $attributes
     */
    private function upsertGoal(User $user, string $title, array $attributes): Goal
    {
        $goal = Goal::query()
            ->where('user_id', $user->id)
            ->where('title', $title)
            ->first() ?? new Goal;

        $goal->forceFill([
            'user_id' => $user->id,
            'project_id' => $attributes['project_id'] ?? null,
            'title' => $title,
            'description' => $attributes['description'] ?? null,
            'target_date' => $attributes['target_date'] ?? null,
            'completed_at' => ($attributes['completed'] ?? false) ? ($goal->completed_at ?? now()) : null,
            'archived_at' => ($attributes['archived'] ?? false) ? ($goal->archived_at ?? now()) : null,
        ])->save();

        return $goal;
    }

    /**
     * @param  array{position: int, completed?: bool, target_date?: string|null}  $attributes
     */
    private function upsertMilestone(Goal $goal, string $title, array $attributes): GoalMilestone
    {
        $milestone = GoalMilestone::query()
            ->where('goal_id', $goal->id)
            ->where('title', $title)
            ->first() ?? new GoalMilestone;

        $completed = $attributes['completed'] ?? false;

        $milestone->forceFill([
            'user_id' => $goal->user_id,
            'goal_id' => $goal->id,
            'title' => $title,
            'target_date' => $attributes['target_date'] ?? null,
            'position' => $attributes['position'],
            'completed_at' => $completed ? ($milestone->completed_at ?? now()) : null,
        ])->save();

        return $milestone;
    }

    private function linkTodoToGoal(Todo $todo, Goal $goal, ?GoalMilestone $milestone = null): void
    {
        $todo->forceFill([
            'goal_id' => $goal->id,
            'goal_milestone_id' => $milestone?->id,
        ])->save();
    }

    /**
     * @param  array{goal_id?: int|null, description?: string|null, frequency: HabitFrequency, target_count: int, archived?: bool}  $attributes
     */
    private function upsertHabit(User $user, string $title, array $attributes): Habit
    {
        $habit = Habit::query()
            ->where('user_id', $user->id)
            ->where('title', $title)
            ->first() ?? new Habit;

        $habit->forceFill([
            'user_id' => $user->id,
            'goal_id' => $attributes['goal_id'] ?? null,
            'title' => $title,
            'description' => $attributes['description'] ?? null,
            'frequency' => $attributes['frequency'],
            'target_count' => $attributes['target_count'],
            'starts_on' => $habit->starts_on ?? today()->toDateString(),
            'archived_at' => ($attributes['archived'] ?? false) ? ($habit->archived_at ?? now()) : null,
        ])->save();

        return $habit;
    }

    private function upsertHabitCheckIn(Habit $habit, string $occurredOn): HabitCheckIn
    {
        $checkIn = HabitCheckIn::query()
            ->where('habit_id', $habit->id)
            ->whereDate('occurred_on', $occurredOn)
            ->first() ?? new HabitCheckIn;

        $checkIn->forceFill([
            'user_id' => $habit->user_id,
            'habit_id' => $habit->id,
            'occurred_on' => $occurredOn,
            'checked_at' => $checkIn->checked_at ?? now(),
        ])->save();

        return $checkIn;
    }

    private function linkTodoToHabit(Todo $todo, Habit $habit): void
    {
        $todo->forceFill([
            'habit_id' => $habit->id,
        ])->save();
    }

    private function upsertPomodoroSession(Todo $todo): PomodoroSession
    {
        $session = PomodoroSession::query()
            ->where('user_id', $todo->user_id)
            ->where('todo_id', $todo->id)
            ->whereIn('status', PomodoroSessionStatus::activeValues())
            ->first() ?? new PomodoroSession;

        $session->forceFill([
            'user_id' => $todo->user_id,
            'todo_id' => $todo->id,
            'duration_minutes' => 25,
            'elapsed_seconds' => 480,
            'status' => PomodoroSessionStatus::Paused,
            'started_at' => now()->subMinutes(8),
            'last_started_at' => null,
            'paused_at' => now(),
            'completed_at' => null,
            'abandoned_at' => null,
        ])->save();

        return $session;
    }

    private function upsertDependency(Todo $todo, Todo $dependsOn): TodoDependency
    {
        $dependency = TodoDependency::query()
            ->where('user_id', $todo->user_id)
            ->where('todo_id', $todo->id)
            ->where('depends_on_todo_id', $dependsOn->id)
            ->first() ?? new TodoDependency;

        $dependency->forceFill([
            'user_id' => $todo->user_id,
            'todo_id' => $todo->id,
            'depends_on_todo_id' => $dependsOn->id,
        ])->save();

        return $dependency;
    }

    private function upsertTimeEntry(Todo $todo, int $minutes, string $entryDate, string $notes): TimeEntry
    {
        $entry = TimeEntry::query()
            ->where('user_id', $todo->user_id)
            ->where('todo_id', $todo->id)
            ->whereDate('entry_date', $entryDate)
            ->where('source', TimeEntrySource::Manual->value)
            ->first() ?? new TimeEntry;

        $entry->forceFill([
            'user_id' => $todo->user_id,
            'todo_id' => $todo->id,
            'project_id' => $todo->project_id,
            'pomodoro_session_id' => null,
            'duration_seconds' => $minutes * 60,
            'source' => TimeEntrySource::Manual,
            'status' => TimeEntryStatus::Completed,
            'entry_date' => $entryDate,
            'started_at' => null,
            'stopped_at' => null,
            'notes' => $notes,
        ])->save();

        return $entry;
    }

    private function upsertProjectTimeEntry(Project $project, int $minutes, string $entryDate, string $notes): TimeEntry
    {
        $entry = TimeEntry::query()
            ->where('user_id', $project->user_id)
            ->whereNull('todo_id')
            ->where('project_id', $project->id)
            ->whereDate('entry_date', $entryDate)
            ->where('source', TimeEntrySource::Manual->value)
            ->first() ?? new TimeEntry;

        $entry->forceFill([
            'user_id' => $project->user_id,
            'todo_id' => null,
            'project_id' => $project->id,
            'pomodoro_session_id' => null,
            'duration_seconds' => $minutes * 60,
            'source' => TimeEntrySource::Manual,
            'status' => TimeEntryStatus::Completed,
            'entry_date' => $entryDate,
            'started_at' => null,
            'stopped_at' => null,
            'notes' => $notes,
        ])->save();

        return $entry;
    }
}

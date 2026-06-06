<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TodoStatus;
use App\Models\Concerns\BelongsToUser;
use App\Policies\TodoPolicy;
use Database\Factories\TodoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A private task owned by a single user (their workspace).
 *
 * Mass assignment is restricted to user-controllable fields only. Ownership
 * (`user_id`) is never fillable and must be assigned through the owning
 * relationship in an action, never from request input. `project_id` is also
 * excluded — it is assigned through actions that verify the project belongs to
 * the same user.
 */
#[Fillable(['title', 'priority', 'due_date'])]
#[UsePolicy(TodoPolicy::class)]
class Todo extends Model
{
    /** @use HasFactory<TodoFactory> */
    use BelongsToUser, HasFactory, SoftDeletes;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_completed' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'archived_at' => 'immutable_datetime',
            'inbox_captured_at' => 'immutable_datetime',
            'priority' => Priority::class,
            'due_date' => 'immutable_date',
        ];
    }

    /**
     * The project this task belongs to, if any.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The goal this task contributes to, if any.
     *
     * @return BelongsTo<Goal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    /**
     * The milestone this task contributes to, if any.
     *
     * @return BelongsTo<GoalMilestone, $this>
     */
    public function goalMilestone(): BelongsTo
    {
        return $this->belongsTo(GoalMilestone::class);
    }

    /**
     * The habit this task supports, if any.
     *
     * @return BelongsTo<Habit, $this>
     */
    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    /**
     * The tags attached to this task.
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Contained checklist rows for this task, ordered for display.
     *
     * @return HasMany<TodoChecklistItem, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(TodoChecklistItem::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * Browser-triggered focus sessions attached to this task.
     *
     * @return HasMany<PomodoroSession, $this>
     */
    public function pomodoroSessions(): HasMany
    {
        return $this->hasMany(PomodoroSession::class);
    }

    /**
     * Time entries logged against this task.
     *
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Tasks this task is waiting on.
     *
     * @return HasMany<TodoDependency, $this>
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(TodoDependency::class);
    }

    /**
     * Tasks currently waiting on this task.
     *
     * @return HasMany<TodoDependency, $this>
     */
    public function blockingDependencies(): HasMany
    {
        return $this->hasMany(TodoDependency::class, 'depends_on_todo_id');
    }

    /**
     * Whether the task is archived (hidden from active/completed views).
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Whether the task is currently active (not completed, not archived).
     */
    public function isActive(): bool
    {
        return ! $this->is_completed && ! $this->isArchived();
    }

    /**
     * Whether the task is waiting in the quick-capture inbox.
     */
    public function isInInbox(): bool
    {
        return $this->inbox_captured_at !== null;
    }

    public function openBlockerCount(): int
    {
        if (array_key_exists('open_dependencies_count', $this->attributes)) {
            return (int) $this->attributes['open_dependencies_count'];
        }

        if ($this->relationLoaded('dependencies')) {
            return $this->dependencies
                ->filter(fn (TodoDependency $dependency): bool => $dependency->isOpen())
                ->count();
        }

        return $this->dependencies()
            ->whereHas('blocker', fn (Builder $blocker): Builder => $blocker
                ->where('todos.user_id', $this->user_id)
                ->where('todos.is_completed', false))
            ->count();
    }

    public function isBlocked(): bool
    {
        return $this->isActive() && $this->openBlockerCount() > 0;
    }

    /**
     * The derived lifecycle bucket for display. Archived wins over completed.
     */
    public function status(): TodoStatus
    {
        return match (true) {
            $this->trashed() => TodoStatus::Trash,
            $this->isArchived() => TodoStatus::Archived,
            $this->is_completed => TodoStatus::Completed,
            default => TodoStatus::Active,
        };
    }

    /**
     * Scope to active tasks: not completed and not archived.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at')->where('is_completed', false);
    }

    /**
     * Scope to captured tasks that still need triage.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeInInbox(Builder $query): Builder
    {
        return $query->whereNotNull('inbox_captured_at');
    }

    /**
     * Scope to completed tasks that are not archived.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNull('archived_at')->where('is_completed', true);
    }

    /**
     * Scope to archived tasks (regardless of completion).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Whether the task is past its due date and still actionable.
     *
     * Completed and archived tasks are never overdue — finishing or shelving a
     * task stops it from nagging.
     */
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->isActive()
            && $this->due_date->lessThan(today());
    }

    /**
     * Whether the task is actionable and due today.
     */
    public function isDueToday(): bool
    {
        return $this->due_date !== null
            && $this->isActive()
            && $this->due_date->isSameDay(today());
    }

    /**
     * Whether the task is actionable and due after today.
     */
    public function isUpcoming(): bool
    {
        return $this->due_date !== null
            && $this->isActive()
            && $this->due_date->greaterThan(today());
    }

    /**
     * Scope to actionable tasks due today (app timezone).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeDueToday(Builder $query): Builder
    {
        return $query->active()->whereDate('due_date', today());
    }

    /**
     * Scope to actionable tasks past their due date.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->active()->whereNotNull('due_date')->whereDate('due_date', '<', today());
    }

    /**
     * Scope to actionable tasks with a future due date.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->active()->whereDate('due_date', '>', today());
    }

    /**
     * Scope to tasks of a given priority.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithPriority(Builder $query, Priority $priority): Builder
    {
        return $query->where('priority', $priority->value);
    }

    /**
     * Scope to tasks within a given project.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to tasks without any project.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithoutProject(Builder $query): Builder
    {
        return $query->whereNull('project_id');
    }

    /**
     * Scope to tasks carrying a given tag.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithTag(Builder $query, int $tagId): Builder
    {
        return $query->whereHas('tags', fn (Builder $tags) => $tags->whereKey($tagId));
    }

    /**
     * Scope to tasks whose title matches a (already trimmed) search term.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeMatching(Builder $query, string $term): Builder
    {
        // Escape LIKE wildcards so a user searching "50%" matches the literal
        // text, not "everything". The ESCAPE clause makes the backslash the
        // escape character in both SQLite and MySQL.
        $escaped = addcslashes($term, '%_\\');

        return $query->whereRaw('title LIKE ? ESCAPE ?', ['%'.$escaped.'%', '\\']);
    }
}

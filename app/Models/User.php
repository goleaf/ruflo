<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the user's todos.
     *
     * @return HasMany<Todo, $this>
     */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    /**
     * Get the user's projects.
     *
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get project memberships granted to the user.
     *
     * @return HasMany<ProjectMembership, $this>
     */
    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMembership::class);
    }

    /**
     * Get project invitations created by the user.
     *
     * @return HasMany<ProjectInvitation, $this>
     */
    public function projectInvitations(): HasMany
    {
        return $this->hasMany(ProjectInvitation::class, 'invited_by_user_id');
    }

    /**
     * Get the user's tags.
     *
     * @return HasMany<Tag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Get the user's saved todo views.
     *
     * @return HasMany<SavedTodoView, $this>
     */
    public function savedTodoViews(): HasMany
    {
        return $this->hasMany(SavedTodoView::class);
    }

    /**
     * Get the user's browser-triggered automation rules.
     *
     * @return HasMany<AutomationRule, $this>
     */
    public function automationRules(): HasMany
    {
        return $this->hasMany(AutomationRule::class);
    }

    /**
     * Get the user's automation rule run logs.
     *
     * @return HasMany<AutomationRuleRun, $this>
     */
    public function automationRuleRuns(): HasMany
    {
        return $this->hasMany(AutomationRuleRun::class);
    }

    /**
     * Get the user's goals.
     *
     * @return HasMany<Goal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * Get the user's goal milestones.
     *
     * @return HasMany<GoalMilestone, $this>
     */
    public function goalMilestones(): HasMany
    {
        return $this->hasMany(GoalMilestone::class);
    }

    /**
     * Get the user's habits.
     *
     * @return HasMany<Habit, $this>
     */
    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class);
    }

    /**
     * Get the user's habit check-ins.
     *
     * @return HasMany<HabitCheckIn, $this>
     */
    public function habitCheckIns(): HasMany
    {
        return $this->hasMany(HabitCheckIn::class);
    }

    /**
     * Get the user's browser-triggered Pomodoro focus sessions.
     *
     * @return HasMany<PomodoroSession, $this>
     */
    public function pomodoroSessions(): HasMany
    {
        return $this->hasMany(PomodoroSession::class);
    }

    /**
     * Get the user's manual and timer-based time entries.
     *
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Task dependency rows owned by the private workspace.
     *
     * @return HasMany<TodoDependency, $this>
     */
    public function todoDependencies(): HasMany
    {
        return $this->hasMany(TodoDependency::class);
    }

    /**
     * Get the user's contained task checklist rows.
     *
     * @return HasMany<TodoChecklistItem, $this>
     */
    public function todoChecklistItems(): HasMany
    {
        return $this->hasMany(TodoChecklistItem::class);
    }

    /**
     * Get the task comments owned by the user's private workspace.
     *
     * @return HasMany<TodoComment, $this>
     */
    public function todoComments(): HasMany
    {
        return $this->hasMany(TodoComment::class);
    }

    /**
     * Get comments written by this user, including comments on shared tasks.
     *
     * @return HasMany<TodoComment, $this>
     */
    public function authoredTodoComments(): HasMany
    {
        return $this->hasMany(TodoComment::class, 'author_id');
    }

    /**
     * Get the user's reusable task templates.
     *
     * @return HasMany<TodoTemplate, $this>
     */
    public function todoTemplates(): HasMany
    {
        return $this->hasMany(TodoTemplate::class);
    }

    /**
     * Get the user's web-triggered task reminders.
     *
     * @return HasMany<Reminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Get the user's task recurrence rules.
     *
     * @return HasMany<TodoRecurrenceRule, $this>
     */
    public function todoRecurrenceRules(): HasMany
    {
        return $this->hasMany(TodoRecurrenceRule::class);
    }

    /**
     * Get the user's skipped, moved, and edited recurrence exceptions.
     *
     * @return HasMany<TodoRecurrenceException, $this>
     */
    public function todoRecurrenceExceptions(): HasMany
    {
        return $this->hasMany(TodoRecurrenceException::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'password' => 'hashed',
            'reminders_enabled' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}

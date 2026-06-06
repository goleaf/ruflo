<?php

namespace App\Livewire\Todos;

use App\Actions\Reminders\ProcessDueReminders;
use App\Actions\Reminders\SyncTodoReminder;
use App\Actions\Reminders\ToggleReminderPreference;
use App\Data\Reminders\ReminderData;
use App\Data\Reminders\ReminderProcessingResult;
use App\Models\Reminder;
use App\Models\Todo;
use App\Models\User;
use App\Queries\Reminders\ReminderListQuery;
use App\Queries\Todos\TodoListQuery;
use App\Rules\Reminders\ReminderAt;
use App\Rules\Todos\OwnedTodo;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('reminders.pages.index.title')]
class Reminders extends Component
{
    use AuthorizesRequests;

    public string $todoId = '';

    public string $remindAt = '';

    public bool $remindersEnabled = true;

    /**
     * @var array{matched: int, processed: int, skipped: int, failed: int, remaining: int}|null
     */
    #[Locked]
    public ?array $lastRunReport = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Reminder::class);

        $this->remindersEnabled = $this->currentUser()->reminders_enabled;
        $this->remindAt = now()->addHour()->startOfMinute()->format('Y-m-d\TH:i');

        $result = app(ProcessDueReminders::class)->handle($this->currentUser());

        if ($result->changedCount() > 0 || $result->failedCount > 0 || $result->remainingCount > 0) {
            $this->lastRunReport = $this->reportFrom($result);
        }
    }

    public function render(): View
    {
        return view('livewire.todos.reminders');
    }

    public function scheduleReminder(SyncTodoReminder $syncReminder, TodoListQuery $todos): void
    {
        $this->authorize('create', Reminder::class);
        $user = $this->currentUser();

        $validated = $this->validate(
            [
                'todoId' => ['required', 'integer', new OwnedTodo($user)],
                'remindAt' => ['required', new ReminderAt],
            ],
            messages: [
                'todoId.required' => __('reminders.validation.todo_required'),
                'todoId.integer' => __('reminders.validation.todo_required'),
            ],
            attributes: [
                'todoId' => __('reminders.fields.task'),
                'remindAt' => __('reminders.fields.remind_at'),
            ],
        );

        $data = ReminderData::fromArray([
            'todo_id' => $validated['todoId'],
            'remind_at' => $validated['remindAt'],
        ]);

        $todo = $todos->findVisibleFor($user, $data->todoId);
        $reminder = $syncReminder->handle($user, $todo, $data->remindAt);

        $this->todoId = '';
        $this->remindAt = now()->addHour()->startOfMinute()->format('Y-m-d\TH:i');
        $this->refreshReminderState();

        Flux::toast(variant: 'success', text: __('reminders.messages.scheduled', ['task' => $reminder->todo?->title ?? __('reminders.processing.unknown_task')]));
    }

    public function clearReminder(int $todoId, SyncTodoReminder $syncReminder, TodoListQuery $todos): void
    {
        $todo = $todos->findVisibleFor($this->currentUser(), $todoId);
        $syncReminder->clear($this->currentUser(), $todo);
        $this->refreshReminderState();

        Flux::toast(variant: 'success', text: __('reminders.messages.cleared'));
    }

    public function processDueReminders(ProcessDueReminders $processDueReminders): void
    {
        $result = $processDueReminders->handle($this->currentUser());
        $this->lastRunReport = $this->reportFrom($result);
        $this->refreshReminderState();

        Flux::toast(variant: 'success', text: __('reminders.messages.processed'));
    }

    public function toggleReminderPreference(ToggleReminderPreference $toggleReminderPreference): void
    {
        $enabled = $toggleReminderPreference->handle($this->currentUser());
        $this->remindersEnabled = $enabled;
        $this->refreshReminderState();

        Flux::toast(variant: 'success', text: $enabled ? __('reminders.messages.enabled') : __('reminders.messages.disabled'));
    }

    /**
     * @return array{pending: int, due: int, processed: int, skipped: int}
     */
    #[Computed]
    public function summary(): array
    {
        return app(ReminderListQuery::class)->summaryFor($this->currentUser());
    }

    /**
     * @return Collection<int, Reminder>
     */
    #[Computed]
    public function reminders(): Collection
    {
        return app(ReminderListQuery::class)->visibleRecentFor($this->currentUser());
    }

    /**
     * @return Collection<int, Todo>
     */
    #[Computed]
    public function taskOptions(): Collection
    {
        return app(ReminderListQuery::class)->taskOptionsFor($this->currentUser());
    }

    /**
     * @return list<array{id: int, title: string, body: string, url: string, remindAt: string, tag: string}>
     */
    #[Computed]
    public function localNotificationReminders(): array
    {
        return app(ReminderListQuery::class)
            ->pendingForLocalNotifications($this->currentUser())
            ->map(function (Reminder $reminder): array {
                $todo = $reminder->todo;
                $task = $todo instanceof Todo
                    ? $todo->title
                    : __('reminders.processing.unknown_task');

                return [
                    'id' => $reminder->id,
                    'title' => __('reminders.notifications.todo_due.title'),
                    'body' => __('reminders.notifications.todo_due.message', ['task' => $task]),
                    'url' => $todo instanceof Todo ? route('todos.show', $todo) : route('todos.index'),
                    'remindAt' => $reminder->remind_at?->toIso8601String() ?? now()->toIso8601String(),
                    'tag' => 'ruflo-reminder-'.$reminder->id,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function localNotificationLabels(): array
    {
        /** @var array<string, string> $labels */
        $labels = __('reminders.local');

        return $labels;
    }

    public function localNotificationFingerprint(): string
    {
        return md5((string) json_encode($this->localNotificationReminders()));
    }

    private function refreshReminderState(): void
    {
        unset($this->summary, $this->reminders, $this->taskOptions, $this->localNotificationReminders, $this->localNotificationLabels);
    }

    /**
     * @return array{matched: int, processed: int, skipped: int, failed: int, remaining: int}
     */
    private function reportFrom(ReminderProcessingResult $result): array
    {
        return [
            'matched' => $result->matchedCount,
            'processed' => $result->processedCount,
            'skipped' => $result->skippedCount,
            'failed' => $result->failedCount,
            'remaining' => $result->remainingCount,
        ];
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

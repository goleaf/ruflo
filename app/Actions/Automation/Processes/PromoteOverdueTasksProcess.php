<?php

namespace App\Actions\Automation\Processes;

use App\Actions\Todos\UpdateTodo;
use App\Contracts\Processing\ManualWebProcess;
use App\Data\Todos\TodoData;
use App\Enums\Priority;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class PromoteOverdueTasksProcess implements ManualWebProcess
{
    public function __construct(
        private readonly UpdateTodo $updateTodo,
    ) {}

    public function key(): string
    {
        return 'automation.promote_overdue_tasks';
    }

    /**
     * @return Builder<Model>
     */
    public function query(User $user): Builder
    {
        /** @var Builder<Model> $query */
        $query = $user->todos()
            ->getQuery()
            ->with('tags:id')
            ->active()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->whereIn('priority', [Priority::Low->value, Priority::Normal->value])
            ->orderBy('due_date')
            ->orderBy('id');

        return $query;
    }

    public function process(User $user, Model $record): bool
    {
        if (! $record instanceof Todo) {
            return false;
        }

        $this->updateTodo->handle($user, $record, new TodoData(
            title: $record->title,
            priority: Priority::High,
            dueDate: $record->due_date?->toDateString(),
            projectId: $record->project_id,
            tagIds: $record->tags->pluck('id')->map(fn (int|string $id): int => (int) $id)->all(),
        ));

        return true;
    }

    /**
     * @return array{id: int, title: string}
     */
    public function detail(Model $record): array
    {
        return [
            'id' => (int) $record->getKey(),
            'title' => $record instanceof Todo ? $record->title : __('automation.unavailable'),
        ];
    }
}

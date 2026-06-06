<?php

namespace App\Actions\Automation\Processes;

use App\Actions\Todos\ArchiveTodo;
use App\Contracts\Processing\ManualWebProcess;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class ArchiveCompletedTasksProcess implements ManualWebProcess
{
    public function __construct(
        private readonly ArchiveTodo $archiveTodo,
        private readonly int $days = 7,
    ) {}

    public function forDays(int $days): self
    {
        return new self($this->archiveTodo, max(1, min(365, $days)));
    }

    public function key(): string
    {
        return 'automation.archive_completed_tasks';
    }

    /**
     * @return Builder<Model>
     */
    public function query(User $user): Builder
    {
        /** @var Builder<Model> $query */
        $query = $user->todos()
            ->completed()
            ->where('updated_at', '<=', now()->subDays($this->days))
            ->orderBy('updated_at')
            ->orderBy('id');

        return $query;
    }

    public function process(User $user, Model $record): bool
    {
        if (! $record instanceof Todo) {
            return false;
        }

        $this->archiveTodo->handle($record);

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

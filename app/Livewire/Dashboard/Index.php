<?php

namespace App\Livewire\Dashboard;

use App\Actions\Reminders\ProcessDueReminders;
use App\Models\User;
use App\Queries\Dashboard\DailySummaryQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('dashboard.title')]
class Index extends Component
{
    /**
     * @var array{matched: int, processed: int, skipped: int, failed: int, remaining: int}|null
     */
    public ?array $reminderRunReport = null;

    public function mount(ProcessDueReminders $processDueReminders): void
    {
        $result = $processDueReminders->handle($this->currentUser());

        if ($result->changedCount() > 0 || $result->failedCount > 0 || $result->remainingCount > 0) {
            $this->reminderRunReport = [
                'matched' => $result->matchedCount,
                'processed' => $result->processedCount,
                'skipped' => $result->skippedCount,
                'failed' => $result->failedCount,
                'remaining' => $result->remainingCount,
            ];
        }
    }

    public function render(): View
    {
        return view('livewire.dashboard.index');
    }

    /**
     * @return array{active: int, overdue: int, completed: int, archived: int, trash: int, projects: int, tags: int, goals: int, milestones: int, habits: int, habit_check_ins: int}
     */
    #[Computed]
    public function summary(): array
    {
        return app(DailySummaryQuery::class)->for($this->currentUser());
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

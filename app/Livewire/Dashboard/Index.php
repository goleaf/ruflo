<?php

namespace App\Livewire\Dashboard;

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
    public function render(): View
    {
        return view('livewire.dashboard.index');
    }

    /**
     * @return array{active: int, overdue: int, completed: int, archived: int, trash: int, projects: int, tags: int, goals: int, milestones: int}
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

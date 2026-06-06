<?php

namespace App\Livewire\Todos;

use App\Models\Todo;
use App\Models\User;
use App\Queries\Todos\TodoCalendarQuery;
use App\Rules\Todos\CalendarMonth;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('todos.pages.calendar.title')]
class Calendar extends Component
{
    use AuthorizesRequests;

    #[Url(as: 'month')]
    public string $month = '';

    public string $monthInput = '';

    public bool $monthWasInvalid = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Todo::class);

        $normalized = CalendarMonth::normalize($this->month);
        $this->monthWasInvalid = $this->month !== '' && $normalized === null;
        $this->setMonth($normalized ?? $this->currentMonthValue());
    }

    public function render(): View
    {
        return view('livewire.todos.calendar');
    }

    public function changeMonth(): void
    {
        Validator::make(
            ['monthInput' => $this->monthInput],
            ['monthInput' => ['required', new CalendarMonth]],
            attributes: ['monthInput' => __('todos.calendar.month')],
        )->validate();

        $normalized = CalendarMonth::normalize($this->monthInput);

        abort_if($normalized === null, 422);

        $this->monthWasInvalid = false;
        $this->setMonth($normalized);
    }

    public function previousMonth(): void
    {
        $this->monthWasInvalid = false;
        $this->setMonth($this->selectedMonth()->subMonthNoOverflow()->format('Y-m'));
    }

    public function nextMonth(): void
    {
        $this->monthWasInvalid = false;
        $this->setMonth($this->selectedMonth()->addMonthNoOverflow()->format('Y-m'));
    }

    public function currentMonth(): void
    {
        $this->monthWasInvalid = false;
        $this->setMonth($this->currentMonthValue());
    }

    /**
     * @return array{
     *     month: CarbonImmutable,
     *     starts_at: CarbonImmutable,
     *     ends_at: CarbonImmutable,
     *     weeks: list<list<array{
     *         key: string,
     *         date: CarbonImmutable,
     *         day_number: string,
     *         weekday: string,
     *         in_month: bool,
     *         is_today: bool,
     *         todos: Collection<int, Todo>
     *     }>>,
     *     summary: array{month: int, overdue: int, today: int, upcoming: int, no_due_date: int},
     *     no_due_tasks: Collection<int, Todo>
     * }
     */
    #[Computed]
    public function calendar(): array
    {
        return app(TodoCalendarQuery::class)->monthFor($this->currentUser(), $this->selectedMonth());
    }

    public function monthLabel(): string
    {
        return $this->selectedMonth()->isoFormat('MMMM YYYY');
    }

    public function rangeLabel(): string
    {
        return __('todos.calendar.range', [
            'start' => $this->calendar['starts_at']->isoFormat('MMM D'),
            'end' => $this->calendar['ends_at']->isoFormat('MMM D, YYYY'),
        ]);
    }

    public function dateTone(Todo $todo): string
    {
        if ($todo->isOverdue()) {
            return 'red';
        }

        if ($todo->isDueToday()) {
            return 'amber';
        }

        return 'zinc';
    }

    private function setMonth(string $month): void
    {
        $this->month = $month;
        $this->monthInput = $month;
        $this->resetErrorBag('monthInput');
        unset($this->calendar);
    }

    private function selectedMonth(): CarbonImmutable
    {
        return CalendarMonth::toMonth($this->month)
            ?? CalendarMonth::toMonth($this->currentMonthValue())
            ?? CarbonImmutable::now(config('app.timezone'))->startOfMonth();
    }

    private function currentMonthValue(): string
    {
        return today()->format('Y-m');
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

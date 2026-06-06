<?php

namespace App\Livewire\Habits;

use App\Actions\Habits\CreateHabit;
use App\Data\Habits\HabitData;
use App\Enums\HabitFrequency;
use App\Models\Goal;
use App\Models\Habit;
use App\Models\User;
use App\Queries\Goals\GoalListQuery;
use App\Rules\Habits\HabitTargetCount;
use App\Rules\Habits\HabitTitle;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('habits.pages.create.title')]
class Create extends Component
{
    use AuthorizesRequests;

    public string $title = '';

    public string $description = '';

    public string $frequency = 'daily';

    public string $targetCount = '1';

    public ?string $goalId = null;

    public function mount(): void
    {
        $this->authorize('create', Habit::class);
    }

    public function render(): View
    {
        return view('livewire.habits.create');
    }

    public function updatedFrequency(string $frequency): void
    {
        if ($frequency === HabitFrequency::Daily->value) {
            $this->targetCount = '1';
        }
    }

    public function createHabit(CreateHabit $createHabit): void
    {
        $habit = $createHabit->handle($this->currentUser(), HabitData::fromArray($this->validatedHabitInput()));

        Flux::toast(variant: 'success', text: __('habits.messages.created', ['title' => $habit->title]));

        $this->redirect(route('habits.index'), navigate: true);
    }

    /**
     * @return Collection<int, Goal>
     */
    #[Computed]
    public function goals(): Collection
    {
        return app(GoalListQuery::class)->for($this->currentUser());
    }

    /**
     * @return array{title: string, description?: string|null, frequency: string, target_count: string, goal_id?: string|null}
     */
    private function validatedHabitInput(): array
    {
        $frequency = HabitFrequency::tryFrom($this->frequency);

        return Validator::make(
            [
                'title' => $this->title,
                'description' => $this->description,
                'frequency' => $this->frequency,
                'target_count' => $this->targetCount,
                'goal_id' => $this->goalId,
            ],
            [
                'title' => ['required', 'string', 'max:'.HabitTitle::MaxLength, new HabitTitle],
                'description' => ['nullable', 'string', 'max:2000'],
                'frequency' => ['required', Rule::enum(HabitFrequency::class)],
                'target_count' => ['required', 'integer', new HabitTargetCount($frequency)],
                'goal_id' => ['nullable', 'integer'],
            ],
            [
                'title.required' => __('habits.validation.title'),
                'title.string' => __('habits.validation.title'),
                'title.max' => __('habits.validation.title'),
                'description.max' => __('habits.validation.description'),
                'frequency.required' => __('habits.validation.frequency'),
                'frequency.enum' => __('habits.validation.frequency'),
                'target_count.required' => __('habits.validation.target_count'),
                'target_count.integer' => __('habits.validation.target_count'),
                'goal_id.integer' => __('habits.validation.goal_required'),
            ],
            [
                'title' => __('habits.fields.title'),
                'description' => __('habits.fields.description'),
                'frequency' => __('habits.fields.frequency'),
                'target_count' => __('habits.fields.target_count'),
                'goal_id' => __('habits.fields.goal'),
            ],
        )->validate();
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

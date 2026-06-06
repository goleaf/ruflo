<?php

namespace App\Livewire\Goals;

use App\Actions\Goals\CreateGoalMilestone;
use App\Data\Goals\GoalMilestoneData;
use App\Models\Goal;
use App\Models\GoalMilestone;
use App\Models\User;
use App\Queries\Goals\GoalListQuery;
use App\Rules\Goals\MilestoneTitle;
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

#[Title('goals.pages.create_milestone.title')]
class CreateMilestone extends Component
{
    use AuthorizesRequests;

    public string $milestoneGoalId = '';

    public string $milestoneTitle = '';

    public ?string $milestoneTargetDate = null;

    public function mount(): void
    {
        $this->authorize('create', GoalMilestone::class);
    }

    public function render(): View
    {
        return view('livewire.goals.create-milestone');
    }

    public function addMilestone(CreateGoalMilestone $createMilestone): void
    {
        $milestone = $createMilestone->handle($this->currentUser(), GoalMilestoneData::fromArray($this->validatedMilestoneInput()));

        Flux::toast(variant: 'success', text: __('goals.messages.milestone_created', ['title' => $milestone->title]));

        $this->redirect(route('goals.index'), navigate: true);
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
     * @return array{goal_id: string, title: string, target_date?: string|null}
     */
    private function validatedMilestoneInput(): array
    {
        return Validator::make(
            [
                'goal_id' => $this->milestoneGoalId,
                'title' => $this->milestoneTitle,
                'target_date' => $this->milestoneTargetDate,
            ],
            [
                'goal_id' => [
                    'required',
                    'integer',
                    Rule::exists('goals', 'id')
                        ->where('user_id', $this->currentUser()->id)
                        ->whereNull('archived_at'),
                ],
                'title' => ['required', 'string', 'max:'.MilestoneTitle::MaxLength, new MilestoneTitle],
                'target_date' => ['nullable', 'date_format:Y-m-d'],
            ],
            [
                'goal_id.required' => __('goals.validation.goal_required'),
                'goal_id.integer' => __('goals.validation.goal_required'),
                'goal_id.exists' => __('goals.validation.goal_required'),
                'title.required' => __('goals.validation.milestone_title'),
                'title.string' => __('goals.validation.milestone_title'),
                'title.max' => __('goals.validation.milestone_title'),
                'target_date.date_format' => __('goals.validation.target_date'),
            ],
            [
                'goal_id' => __('goals.fields.goal'),
                'title' => __('goals.fields.milestone_title'),
                'target_date' => __('goals.fields.target_date'),
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

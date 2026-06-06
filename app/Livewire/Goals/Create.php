<?php

namespace App\Livewire\Goals;

use App\Actions\Goals\CreateGoal;
use App\Data\Goals\GoalData;
use App\Models\Goal;
use App\Models\Project;
use App\Models\User;
use App\Queries\Projects\ProjectListQuery;
use App\Rules\Goals\GoalTitle;
use App\Rules\Todos\OwnedActiveProject;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('goals.pages.create.title')]
class Create extends Component
{
    use AuthorizesRequests;

    public string $title = '';

    public string $description = '';

    public ?string $projectId = null;

    public ?string $targetDate = null;

    public function mount(): void
    {
        $this->authorize('create', Goal::class);
    }

    public function render(): View
    {
        return view('livewire.goals.create');
    }

    public function createGoal(CreateGoal $createGoal): void
    {
        $goal = $createGoal->handle($this->currentUser(), GoalData::fromArray($this->validatedGoalInput()));

        Flux::toast(variant: 'success', text: __('goals.messages.created', ['title' => $goal->title]));

        $this->redirect(route('goals.index'), navigate: true);
    }

    /**
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return app(ProjectListQuery::class)->activeFor($this->currentUser());
    }

    /**
     * @return array{title: string, description?: string|null, project_id?: string|null, target_date?: string|null}
     */
    private function validatedGoalInput(): array
    {
        return Validator::make(
            [
                'title' => $this->title,
                'description' => $this->description,
                'project_id' => $this->projectId,
                'target_date' => $this->targetDate,
            ],
            [
                'title' => ['required', 'string', 'max:'.GoalTitle::MaxLength, new GoalTitle],
                'description' => ['nullable', 'string', 'max:2000'],
                'project_id' => ['nullable', 'integer', new OwnedActiveProject($this->currentUser())],
                'target_date' => ['nullable', 'date_format:Y-m-d'],
            ],
            [
                'title.required' => __('goals.validation.title'),
                'title.string' => __('goals.validation.title'),
                'title.max' => __('goals.validation.title'),
                'description.max' => __('goals.validation.description'),
                'target_date.date_format' => __('goals.validation.target_date'),
            ],
            [
                'title' => __('goals.fields.title'),
                'description' => __('goals.fields.description'),
                'project_id' => __('goals.fields.project'),
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

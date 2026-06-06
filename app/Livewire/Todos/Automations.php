<?php

namespace App\Livewire\Todos;

use App\Actions\Automation\CreateAutomationRule;
use App\Actions\Automation\RunAutomationRule;
use App\Actions\Automation\ToggleAutomationRule;
use App\Enums\AutomationRuleKind;
use App\Enums\AutomationRunStatus;
use App\Models\AutomationRule;
use App\Models\User;
use App\Queries\Automation\AutomationRuleQuery;
use App\Rules\Automation\AutomationRuleName;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('automation.pages.index.title')]
class Automations extends Component
{
    use AuthorizesRequests;

    public string $name = '';

    public string $kind = AutomationRuleKind::PromoteOverdueTasks->value;

    /**
     * @var array{rule: string, status: string, dry_run: bool, matched: int, changed: int, skipped: int, message: string}|null
     */
    #[Locked]
    public ?array $lastRunReport = null;

    public function mount(): void
    {
        $this->authorize('viewAny', AutomationRule::class);
    }

    public function render(): View
    {
        return view('livewire.todos.automations');
    }

    public function createRule(CreateAutomationRule $createAutomationRule): void
    {
        $this->authorize('create', AutomationRule::class);

        $user = $this->currentUser();

        $validated = $this->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:80',
                    new AutomationRuleName,
                    Rule::unique('automation_rules', 'name')->where(fn ($query) => $query->where('user_id', $user->id)),
                ],
                'kind' => ['required', Rule::in(AutomationRuleKind::values())],
            ],
            messages: [
                'name.required' => __('automation.validation.rule_name'),
                'name.max' => __('automation.validation.rule_name'),
                'name.unique' => __('automation.validation.rule_name_unique'),
            ],
            attributes: [
                'name' => __('automation.fields.name'),
                'kind' => __('automation.fields.kind'),
            ],
        );

        $kind = AutomationRuleKind::from($validated['kind']);
        $automationRule = $createAutomationRule->handle($user, $validated['name'], $kind);

        $this->reset(['name']);
        $this->kind = AutomationRuleKind::PromoteOverdueTasks->value;
        unset($this->rules);

        Flux::toast(variant: 'success', text: __('automation.messages.created', ['name' => $automationRule->name]));
    }

    public function toggleRule(int $automationRuleId, AutomationRuleQuery $query, ToggleAutomationRule $toggleAutomationRule): void
    {
        $automationRule = $query->findFor($this->currentUser(), $automationRuleId);
        $this->authorize('update', $automationRule);

        $toggleAutomationRule->handle($automationRule, ! $automationRule->is_enabled);

        unset($this->rules);

        Flux::toast(variant: 'success', text: __('automation.messages.toggled'));
    }

    public function testRule(int $automationRuleId, AutomationRuleQuery $query, RunAutomationRule $runAutomationRule): void
    {
        $this->executeRule($automationRuleId, dryRun: true, query: $query, runAutomationRule: $runAutomationRule);
    }

    public function runRule(int $automationRuleId, AutomationRuleQuery $query, RunAutomationRule $runAutomationRule): void
    {
        $this->executeRule($automationRuleId, dryRun: false, query: $query, runAutomationRule: $runAutomationRule);
    }

    /**
     * @return Collection<int, AutomationRule>
     */
    #[Computed]
    public function rules(): Collection
    {
        return app(AutomationRuleQuery::class)->for($this->currentUser())->get();
    }

    /**
     * @return list<AutomationRuleKind>
     */
    public function kindOptions(): array
    {
        return AutomationRuleKind::cases();
    }

    public function kindLabel(string $kind): string
    {
        return AutomationRuleKind::tryFrom($kind)?->label() ?? __('automation.unavailable');
    }

    public function kindDescription(string $kind): string
    {
        return AutomationRuleKind::tryFrom($kind)?->description() ?? __('automation.unavailable');
    }

    public function kindIcon(string $kind): string
    {
        return AutomationRuleKind::tryFrom($kind)?->icon() ?? 'bolt';
    }

    public function kindColor(string $kind): string
    {
        return AutomationRuleKind::tryFrom($kind)?->color() ?? 'zinc';
    }

    public function statusLabel(?AutomationRunStatus $status): string
    {
        return $status?->label() ?? __('automation.rules.never_run');
    }

    public function statusIcon(?AutomationRunStatus $status): string
    {
        return $status?->icon() ?? 'minus-circle';
    }

    public function statusColor(?AutomationRunStatus $status): string
    {
        return $status?->color() ?? 'zinc';
    }

    private function executeRule(
        int $automationRuleId,
        bool $dryRun,
        AutomationRuleQuery $query,
        RunAutomationRule $runAutomationRule,
    ): void {
        $automationRule = $query->findFor($this->currentUser(), $automationRuleId);
        $this->authorize('run', $automationRule);

        $run = $runAutomationRule->handle($this->currentUser(), $automationRule, $dryRun);

        $this->lastRunReport = [
            'rule' => $automationRule->name,
            'status' => $run->status->label(),
            'dry_run' => $run->dry_run,
            'matched' => $run->matched_count,
            'changed' => $run->changed_count,
            'skipped' => $run->skipped_count,
            'message' => $run->message,
        ];

        unset($this->rules);

        Flux::toast(
            variant: $run->status === AutomationRunStatus::Failed ? 'warning' : 'success',
            text: $run->dry_run ? __('automation.messages.tested') : __('automation.messages.ran'),
        );
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

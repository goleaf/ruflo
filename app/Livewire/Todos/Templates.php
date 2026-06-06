<?php

namespace App\Livewire\Todos;

use App\Actions\Todos\CreateTodoFromTemplate;
use App\Actions\Todos\CreateTodoTemplate;
use App\Actions\Todos\DeleteTodoTemplate;
use App\Actions\Todos\UpdateTodoTemplate;
use App\Data\Todos\TodoTemplateData;
use App\Enums\Priority;
use App\Enums\TaskTemplateKind;
use App\Models\TodoTemplate;
use App\Models\User;
use App\Queries\Todos\TodoTemplateListQuery;
use App\Rules\Todos\TemplateChecklistItems;
use App\Rules\Todos\TemplateName;
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

#[Title('todos.pages.templates.title')]
class Templates extends Component
{
    use AuthorizesRequests;

    public string $name = '';

    public string $kind = 'task';

    public string $visibility = 'private';

    public string $title = '';

    public string $description = '';

    public string $priority = 'normal';

    public ?string $dueOffsetDays = null;

    public string $projectName = '';

    /** @var array<int, string> */
    public array $checklistItems = [''];

    #[Locked]
    public ?int $editingTemplateId = null;

    public bool $showEditModal = false;

    public string $editName = '';

    public string $editKind = 'task';

    public string $editVisibility = 'private';

    public string $editTitle = '';

    public string $editDescription = '';

    public string $editPriority = 'normal';

    public ?string $editDueOffsetDays = null;

    public string $editProjectName = '';

    /** @var array<int, string> */
    public array $editChecklistItems = [''];

    public function mount(): void
    {
        $this->authorize('viewAny', TodoTemplate::class);
    }

    public function render(): View
    {
        return view('livewire.todos.templates');
    }

    public function createTemplate(CreateTodoTemplate $createTemplate): void
    {
        $template = $createTemplate->handle($this->currentUser(), $this->createData());

        $this->resetCreateForm();
        $this->refreshTemplates();

        Flux::toast(variant: 'success', text: __('todos.messages.template_created', ['name' => $template->name]));
    }

    public function startEditTemplate(int $templateId, TodoTemplateListQuery $query): void
    {
        $template = $query->findFor($this->currentUser(), $templateId);
        $this->authorize('update', $template);

        $this->editingTemplateId = $template->id;
        $this->editName = $template->name;
        $this->editKind = $template->kind->value;
        $this->editVisibility = $template->visibility;
        $this->editTitle = $template->title;
        $this->editDescription = $template->description ?? '';
        $this->editPriority = $template->priority->value;
        $this->editDueOffsetDays = $template->due_offset_days === null ? null : (string) $template->due_offset_days;
        $this->editProjectName = $template->project_name ?? '';
        $this->editChecklistItems = $template->checklist_items === [] ? [''] : $template->checklist_items;
        $this->showEditModal = true;
    }

    public function saveTemplate(TodoTemplateListQuery $query, UpdateTodoTemplate $updateTemplate): void
    {
        $template = $query->findFor($this->currentUser(), (int) $this->editingTemplateId);

        $updateTemplate->handle($this->currentUser(), $template, $this->editData());

        $this->closeEdit();
        $this->refreshTemplates();

        Flux::toast(variant: 'success', text: __('todos.messages.template_updated'));
    }

    public function closeEdit(): void
    {
        $this->editingTemplateId = null;
        $this->showEditModal = false;
        $this->reset([
            'editName',
            'editDescription',
            'editTitle',
            'editProjectName',
        ]);
        $this->editKind = 'task';
        $this->editVisibility = 'private';
        $this->editPriority = 'normal';
        $this->editDueOffsetDays = null;
        $this->editChecklistItems = [''];
    }

    public function deleteTemplate(int $templateId, TodoTemplateListQuery $query, DeleteTodoTemplate $deleteTemplate): void
    {
        $template = $query->findFor($this->currentUser(), $templateId);

        $deleteTemplate->handle($this->currentUser(), $template);

        $this->refreshTemplates();

        Flux::toast(variant: 'success', text: __('todos.messages.template_deleted'));
    }

    public function createTodoFromTemplate(int $templateId, TodoTemplateListQuery $query, CreateTodoFromTemplate $createTodoFromTemplate): void
    {
        $template = $query->findFor($this->currentUser(), $templateId);

        $todo = $createTodoFromTemplate->handle($this->currentUser(), $template);

        Flux::toast(variant: 'success', text: __('todos.messages.template_instantiated', [
            'title' => $todo->title,
        ]));
    }

    public function addChecklistItem(): void
    {
        if (count($this->checklistItems) >= 10) {
            return;
        }

        $this->checklistItems[] = '';
    }

    public function removeChecklistItem(int $index): void
    {
        unset($this->checklistItems[$index]);

        $this->checklistItems = array_values($this->checklistItems);

        if ($this->checklistItems === []) {
            $this->checklistItems = [''];
        }
    }

    public function addEditChecklistItem(): void
    {
        if (count($this->editChecklistItems) >= 10) {
            return;
        }

        $this->editChecklistItems[] = '';
    }

    public function removeEditChecklistItem(int $index): void
    {
        unset($this->editChecklistItems[$index]);

        $this->editChecklistItems = array_values($this->editChecklistItems);

        if ($this->editChecklistItems === []) {
            $this->editChecklistItems = [''];
        }
    }

    /**
     * @return Collection<int, TodoTemplate>
     */
    #[Computed]
    public function templates(): Collection
    {
        return app(TodoTemplateListQuery::class)->for($this->currentUser());
    }

    /**
     * @return list<Priority>
     */
    public function priorityOptions(): array
    {
        return Priority::cases();
    }

    /**
     * @return list<TaskTemplateKind>
     */
    public function kindOptions(): array
    {
        return TaskTemplateKind::cases();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function visibilityOptions(): array
    {
        return [
            ['value' => 'private', 'label' => (string) __('todos.templates.visibility.private')],
            ['value' => 'shared', 'label' => (string) __('todos.templates.visibility.shared')],
        ];
    }

    private function createData(): TodoTemplateData
    {
        $validated = $this->validate($this->createRules(), messages: $this->validationMessages(), attributes: $this->createAttributes());

        return TodoTemplateData::fromArray([
            'name' => $validated['name'],
            'kind' => $validated['kind'],
            'visibility' => $validated['visibility'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'due_offset_days' => $validated['dueOffsetDays'] ?? null,
            'project_name' => $validated['projectName'] ?? null,
            'checklist_items' => $validated['checklistItems'] ?? [],
        ]);
    }

    private function editData(): TodoTemplateData
    {
        $validated = $this->validate($this->editRules(), messages: $this->validationMessages(), attributes: $this->editAttributes());

        return TodoTemplateData::fromArray([
            'name' => $validated['editName'],
            'kind' => $validated['editKind'],
            'visibility' => $validated['editVisibility'],
            'title' => $validated['editTitle'],
            'description' => $validated['editDescription'] ?? null,
            'priority' => $validated['editPriority'],
            'due_offset_days' => $validated['editDueOffsetDays'] ?? null,
            'project_name' => $validated['editProjectName'] ?? null,
            'checklist_items' => $validated['editChecklistItems'] ?? [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createRules(): array
    {
        $user = $this->currentUser();

        return [
            'name' => [
                'required',
                'string',
                'max:80',
                new TemplateName,
                Rule::unique('todo_templates', 'name')->where(fn ($query) => $query->where('user_id', $user->id)),
            ],
            'kind' => ['required', Rule::enum(TaskTemplateKind::class)],
            'visibility' => ['required', Rule::in(['private', 'shared'])],
            'title' => ['required', 'string', 'max:120', new TemplateName],
            'description' => ['nullable', 'string', 'max:500'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'dueOffsetDays' => ['nullable', 'integer', 'min:0', 'max:365'],
            'projectName' => $this->projectNameRules($this->kind),
            'checklistItems' => ['array', new TemplateChecklistItems($this->kind)],
            'checklistItems.*' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function editRules(): array
    {
        $user = $this->currentUser();

        return [
            'editName' => [
                'required',
                'string',
                'max:80',
                new TemplateName,
                Rule::unique('todo_templates', 'name')
                    ->where(fn ($query) => $query->where('user_id', $user->id))
                    ->ignore($this->editingTemplateId),
            ],
            'editKind' => ['required', Rule::enum(TaskTemplateKind::class)],
            'editVisibility' => ['required', Rule::in(['private', 'shared'])],
            'editTitle' => ['required', 'string', 'max:120', new TemplateName],
            'editDescription' => ['nullable', 'string', 'max:500'],
            'editPriority' => ['required', Rule::enum(Priority::class)],
            'editDueOffsetDays' => ['nullable', 'integer', 'min:0', 'max:365'],
            'editProjectName' => $this->projectNameRules($this->editKind),
            'editChecklistItems' => ['array', new TemplateChecklistItems($this->editKind)],
            'editChecklistItems.*' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return list<mixed>
     */
    private function projectNameRules(string $kind): array
    {
        if ($kind === TaskTemplateKind::Project->value) {
            return ['required', 'string', 'max:120', new TemplateName];
        }

        return ['nullable', 'string', 'max:120'];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'name.unique' => __('todos.validation.template_name_unique'),
            'editName.unique' => __('todos.validation.template_name_unique'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function createAttributes(): array
    {
        return [
            'name' => __('todos.templates.fields.name'),
            'kind' => __('todos.templates.fields.kind'),
            'visibility' => __('todos.templates.fields.visibility'),
            'title' => __('todos.fields.title'),
            'description' => __('todos.templates.fields.description'),
            'priority' => __('todos.fields.priority'),
            'dueOffsetDays' => __('todos.templates.fields.due_offset_days'),
            'projectName' => __('todos.templates.fields.project_name'),
            'checklistItems' => __('todos.templates.fields.checklist_items'),
            'checklistItems.*' => __('todos.checklist.fields.item_title'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function editAttributes(): array
    {
        return [
            'editName' => __('todos.templates.fields.name'),
            'editKind' => __('todos.templates.fields.kind'),
            'editVisibility' => __('todos.templates.fields.visibility'),
            'editTitle' => __('todos.fields.title'),
            'editDescription' => __('todos.templates.fields.description'),
            'editPriority' => __('todos.fields.priority'),
            'editDueOffsetDays' => __('todos.templates.fields.due_offset_days'),
            'editProjectName' => __('todos.templates.fields.project_name'),
            'editChecklistItems' => __('todos.templates.fields.checklist_items'),
            'editChecklistItems.*' => __('todos.checklist.fields.item_title'),
        ];
    }

    private function resetCreateForm(): void
    {
        $this->reset(['name', 'description', 'title', 'projectName']);
        $this->kind = 'task';
        $this->visibility = 'private';
        $this->priority = 'normal';
        $this->dueOffsetDays = null;
        $this->checklistItems = [''];
    }

    private function refreshTemplates(): void
    {
        unset($this->templates);
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

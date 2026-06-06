<?php

use App\Enums\Priority;
use App\Livewire\Projects\Show;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

function projectDetailMiddleware(string $routeName): array
{
    return Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
}

test('project detail route redirects guests to login', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.show', $project))
        ->assertRedirect(route('login'));
});

test('project detail route redirects unverified users to verification', function () {
    $user = User::factory()->unverified()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertRedirect(route('verification.notice'));
});

test('users can view their own private project detail page', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'Client launch', 'color' => 'blue']);
    $tag = Tag::factory()->for($user)->create(['name' => 'review']);
    $active = Todo::factory()
        ->forProject($project)
        ->withTags($tag)
        ->priority(Priority::Urgent)
        ->dueOn(today())
        ->create(['title' => 'Write launch checklist']);
    Todo::factory()->forProject($project)->completed()->create(['title' => 'Prepare summary']);
    Todo::factory()->forProject($project)->archived()->create(['title' => 'Park old notes']);
    Todo::factory()->forProject($project)->deleted()->create(['title' => 'Removed launch draft']);
    Todo::factory()->for($user)->create(['title' => 'No project task']);
    Todo::factory()->for($other)->create(['title' => 'Other workspace task']);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('Client launch')
        ->assertSee(__('todos.projects.show.tasks_heading'))
        ->assertSee('Write launch checklist')
        ->assertSee('Prepare summary')
        ->assertSee('Park old notes')
        ->assertSee('#review')
        ->assertSee(Priority::Urgent->label())
        ->assertSee(route('todos.show', $active), false)
        ->assertDontSee('Removed launch draft')
        ->assertDontSee('No project task')
        ->assertDontSee('Other workspace task');
});

test('archived project detail remains readable but hides the active task filter action', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->archived()->create(['name' => 'Old plans']);
    Todo::factory()->forProject($project)->create(['title' => 'Archived project task']);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('Old plans')
        ->assertSee('Archived project task')
        ->assertSee(__('todos.status.archived'))
        ->assertDontSee(__('todos.projects.actions.filter_tasks'));
});

test('foreign project detail pages return not found without leaking content', function () {
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    $foreignProject = Project::factory()->for($owner)->create(['name' => 'Foreign project']);
    Todo::factory()->forProject($foreignProject)->create(['title' => 'Foreign project task']);

    $this->actingAs($viewer)
        ->get(route('projects.show', $foreignProject))
        ->assertNotFound()
        ->assertDontSee('Foreign project')
        ->assertDontSee('Foreign project task');
});

test('empty projects render a translated empty state', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'Empty project']);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee(__('todos.empty.project_detail.title'))
        ->assertSee(__('todos.empty.project_detail.description'));
});

test('task list and task detail render project detail links only for current user data', function () {
    $viewer = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($viewer)->create(['name' => 'Visible project']);
    $foreignProject = Project::factory()->for($other)->create(['name' => 'Hidden project']);
    $todo = Todo::factory()->forProject($project)->create(['title' => 'Visible task']);
    Todo::factory()->forProject($foreignProject)->create(['title' => 'Hidden task']);

    $this->actingAs($viewer)
        ->get(route('todos.index'))
        ->assertOk()
        ->assertSee('Visible project')
        ->assertSee(route('projects.show', $project), false)
        ->assertDontSee('Hidden project')
        ->assertDontSee(route('projects.show', $foreignProject), false);

    $this->actingAs($viewer)
        ->get(route('todos.show', $todo))
        ->assertOk()
        ->assertSee(route('projects.show', $project), false)
        ->assertDontSee(route('projects.show', $foreignProject), false);
});

test('project detail Livewire component denies direct foreign ids', function () {
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    $foreignProject = Project::factory()->for($owner)->create();

    expect(fn () => Livewire::actingAs($viewer)->test(Show::class, ['project' => $foreignProject->id]))
        ->toThrow(ModelNotFoundException::class);
});

test('project detail route and component keep private view guardrails', function () {
    $source = file_get_contents(app_path('Livewire/Projects/Show.php'));

    expect(projectDetailMiddleware('projects.show'))
        ->toContain('auth', 'verified')
        ->and($source)
        ->toContain('#[Locked]')
        ->toContain('public int $projectId')
        ->toContain('findVisibleFor($this->currentUser()')
        ->not->toContain('Project::find')
        ->not->toContain('Project::query()');
});

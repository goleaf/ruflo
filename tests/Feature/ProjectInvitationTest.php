<?php

use App\Actions\Projects\AcceptProjectInvitation;
use App\Enums\ProjectInvitationStatus;
use App\Enums\ProjectRole;
use App\Livewire\Projects\AcceptInvitation;
use App\Livewire\Projects\Show as ProjectShow;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectMembership;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\ProjectInvitationSeeder;
use Database\Seeders\ProjectMembershipSeeder;
use Database\Seeders\TodoSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('manager creates a signed copyable invite link without sending email', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $manager = User::factory()->create();
    $project = Project::factory()->for($owner)->create(['name' => 'Manual launch']);

    ProjectMembership::factory()->forProject($project)->forMember($manager)->manager()->create();

    Livewire::actingAs($manager)
        ->test(ProjectShow::class, ['project' => $project->id])
        ->assertSee(__('todos.collaboration.invites.heading'))
        ->set('inviteRole', ProjectRole::Editor->value)
        ->set('inviteExpiresInDays', '14')
        ->call('createInvitation')
        ->assertHasNoErrors()
        ->assertSee('https://ruflo.test/project-invitations', false);

    $invitation = ProjectInvitation::query()->firstOrFail();

    expect($invitation->project->is($project))->toBeTrue()
        ->and($invitation->role)->toBe(ProjectRole::Editor)
        ->and($invitation->status())->toBe(ProjectInvitationStatus::Pending)
        ->and($invitation->shareUrl())->toStartWith('https://ruflo.test/project-invitations/');

    Mail::assertNothingSent();
});

test('pending invite link does not grant project access until accepted', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $project = Project::factory()->for($owner)->create(['name' => 'Private roadmap']);
    $token = 'ValidProjectInviteToken000000000000000000000001';
    $invitation = ProjectInvitation::factory()
        ->forProject($project)
        ->invitedBy($owner)
        ->editor()
        ->withToken($token)
        ->create();

    $this->actingAs($invitee)
        ->get(route('projects.show', $project))
        ->assertNotFound()
        ->assertDontSee('Private roadmap');

    $this->actingAs($invitee)
        ->get($invitation->shareUrl())
        ->assertOk()
        ->assertSee(__('todos.collaboration.invites.accept.title'))
        ->assertDontSee('Private roadmap');

    Livewire::actingAs($invitee)
        ->test(AcceptInvitation::class, ['token' => $token])
        ->call('accept')
        ->assertHasNoErrors()
        ->assertRedirect(route('projects.show', $project));

    expect(ProjectMembership::query()
        ->where('project_id', $project->id)
        ->where('user_id', $invitee->id)
        ->where('role', ProjectRole::Editor->value)
        ->whereNull('removed_at')
        ->exists())->toBeTrue()
        ->and($invitation->refresh()->accepted_by_user_id)->toBe($invitee->id)
        ->and($invitation->status())->toBe(ProjectInvitationStatus::Accepted);

    $this->actingAs($invitee)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('Private roadmap');
});

test('cancelled expired and tampered role invites cannot grant access', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $project = Project::factory()->for($owner)->create();

    $cancelled = ProjectInvitation::factory()
        ->forProject($project)
        ->invitedBy($owner)
        ->withToken('CancelledInviteToken00000000000000000000001')
        ->cancelled()
        ->create();
    $expired = ProjectInvitation::factory()
        ->forProject($project)
        ->invitedBy($owner)
        ->withToken('ExpiredInviteToken0000000000000000000000001')
        ->expired()
        ->create();
    $tampered = ProjectInvitation::factory()
        ->forProject($project)
        ->invitedBy($owner)
        ->withToken('TamperedInviteToken000000000000000000000001')
        ->state(['role' => ProjectRole::Owner])
        ->create();

    foreach ([$cancelled, $expired, $tampered] as $invitation) {
        expect(fn () => app(AcceptProjectInvitation::class)->handle($invitee, $invitation->token))
            ->toThrow(ValidationException::class);
    }

    expect(ProjectMembership::query()
        ->where('project_id', $project->id)
        ->where('user_id', $invitee->id)
        ->exists())->toBeFalse()
        ->and($expired->shareUrl())->toStartWith('https://ruflo.test/project-invitations/');
});

test('project page renders invite lifecycle states and can cancel pending links', function () {
    $owner = User::factory()->create();
    $acceptedUser = User::factory()->create(['name' => 'Accepted User']);
    $project = Project::factory()->for($owner)->create(['name' => 'Shared review']);

    $pending = ProjectInvitation::factory()->forProject($project)->invitedBy($owner)->withToken('PendingInviteToken0000000000000000000000001')->create();
    ProjectInvitation::factory()->forProject($project)->invitedBy($owner)->accepted($acceptedUser)->create();
    ProjectInvitation::factory()->forProject($project)->invitedBy($owner)->cancelled()->create();
    ProjectInvitation::factory()->forProject($project)->invitedBy($owner)->expired()->create();

    Livewire::actingAs($owner)
        ->test(ProjectShow::class, ['project' => $project->id])
        ->assertSee('data-test="project-invite-pending"', false)
        ->assertSee('data-test="project-invite-accepted"', false)
        ->assertSee('data-test="project-invite-cancelled"', false)
        ->assertSee('data-test="project-invite-expired"', false)
        ->assertSee('Accepted User')
        ->call('cancelInvitation', $pending->id)
        ->assertHasNoErrors();

    expect($pending->refresh()->status())->toBe(ProjectInvitationStatus::Cancelled);
});

test('invite create form rejects unsafe role and expiration input', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner)->create();

    Livewire::actingAs($owner)
        ->test(ProjectShow::class, ['project' => $project->id])
        ->set('inviteRole', ProjectRole::Owner->value)
        ->set('inviteExpiresInDays', '0')
        ->call('createInvitation')
        ->assertHasErrors(['inviteRole', 'inviteExpiresInDays']);

    expect(ProjectInvitation::query()->count())->toBe(0);
});

test('project invitation seeder creates idempotent local demo states', function () {
    $this->seed([DemoUserSeeder::class, TodoSeeder::class, ProjectMembershipSeeder::class, ProjectInvitationSeeder::class]);

    expect(ProjectInvitation::query()->count())->toBe(4)
        ->and(ProjectInvitation::query()->get()->map(fn (ProjectInvitation $invitation) => $invitation->status()->value)->sort()->values()->all())
        ->toBe([
            ProjectInvitationStatus::Accepted->value,
            ProjectInvitationStatus::Cancelled->value,
            ProjectInvitationStatus::Expired->value,
            ProjectInvitationStatus::Pending->value,
        ]);

    $this->seed(ProjectInvitationSeeder::class);

    expect(ProjectInvitation::query()->count())->toBe(4);
});

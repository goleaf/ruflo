<?php

namespace App\Livewire\Projects;

use App\Actions\Projects\AcceptProjectInvitation;
use App\Enums\ProjectInvitationStatus;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Queries\Projects\ProjectInvitationQuery;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('todos.collaboration.invites.accept.title')]
final class AcceptInvitation extends Component
{
    #[Locked]
    public string $token;

    public ?string $errorMessage = null;

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function render(): View
    {
        return view('livewire.projects.accept-invitation');
    }

    public function accept(AcceptProjectInvitation $acceptInvitation): void
    {
        try {
            $invitation = $acceptInvitation->handle($this->currentUser(), $this->token);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->errorMessage = is_string($message)
                ? $message
                : (string) __('todos.collaboration.invites.validation.accept_unavailable');
            $this->addError('invitation', $this->errorMessage);

            return;
        }

        Flux::toast(variant: 'success', text: __('todos.collaboration.invites.messages.accepted'));

        $this->redirectRoute('projects.show', ['project' => $invitation->project_id], navigate: true);
    }

    #[Computed]
    public function invitation(): ProjectInvitation
    {
        return app(ProjectInvitationQuery::class)->findByToken($this->token);
    }

    #[Computed]
    public function status(): ProjectInvitationStatus
    {
        return $this->invitation->status();
    }

    #[Computed]
    public function canAccept(): bool
    {
        return $this->status === ProjectInvitationStatus::Pending;
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

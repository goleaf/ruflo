<?php

namespace App\Models;

use App\Enums\ProjectInvitationStatus;
use App\Enums\ProjectRole;
use App\Policies\ProjectInvitationPolicy;
use Database\Factories\ProjectInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

#[Fillable(['project_id', 'invited_by_user_id', 'accepted_by_user_id', 'role', 'token', 'token_hash', 'expires_at', 'cancelled_at', 'accepted_at'])]
#[UsePolicy(ProjectInvitationPolicy::class)]
class ProjectInvitation extends Model
{
    /** @use HasFactory<ProjectInvitationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public function status(): ProjectInvitationStatus
    {
        if ($this->accepted_at !== null) {
            return ProjectInvitationStatus::Accepted;
        }

        if ($this->cancelled_at !== null) {
            return ProjectInvitationStatus::Cancelled;
        }

        if ($this->expires_at->isPast()) {
            return ProjectInvitationStatus::Expired;
        }

        return ProjectInvitationStatus::Pending;
    }

    public function isPending(): bool
    {
        return $this->status() === ProjectInvitationStatus::Pending;
    }

    public function shareUrl(): string
    {
        return URL::temporarySignedRoute(
            'projects.invitations.accept',
            $this->expires_at,
            ['token' => $this->token],
        );
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => ProjectRole::class,
            'token' => 'encrypted',
            'expires_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
        ];
    }
}

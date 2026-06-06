<?php

namespace App\Enums;

enum ProjectInvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return __('todos.collaboration.invites.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Accepted => 'green',
            self::Cancelled => 'zinc',
            self::Expired => 'red',
        };
    }
}

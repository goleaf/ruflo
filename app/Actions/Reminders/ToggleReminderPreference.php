<?php

namespace App\Actions\Reminders;

use App\Models\User;

final class ToggleReminderPreference
{
    public function handle(User $user): bool
    {
        $enabled = ! $user->reminders_enabled;

        $user->forceFill([
            'reminders_enabled' => $enabled,
        ])->save();

        return $enabled;
    }
}

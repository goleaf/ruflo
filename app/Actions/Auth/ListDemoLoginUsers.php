<?php

namespace App\Actions\Auth;

use App\Data\Auth\DemoLoginUser;
use App\Models\User;
use Throwable;

class ListDemoLoginUsers
{
    /**
     * @return list<DemoLoginUser>
     */
    public function __invoke(): array
    {
        if (! $this->isAllowed()) {
            return [];
        }

        $configuredUsers = collect(config('demo.login_panel.users', []))
            ->filter(fn (array $user): bool => filled($user['email'] ?? null));

        if ($configuredUsers->isEmpty()) {
            return [];
        }

        try {
            $seededEmails = User::query()
                ->whereIn('email', $configuredUsers->pluck('email')->all())
                ->pluck('email')
                ->all();
        } catch (Throwable) {
            return [];
        }

        $seededEmailLookup = array_flip($seededEmails);
        $password = (string) config('demo.login_panel.password', 'password');

        return $configuredUsers
            ->filter(fn (array $user): bool => isset($seededEmailLookup[$user['email']]))
            ->map(fn (array $user): DemoLoginUser => new DemoLoginUser(
                name: (string) $user['name'],
                email: (string) $user['email'],
                role: __((string) $user['role_key']),
                description: __((string) $user['description_key']),
                password: $password,
            ))
            ->values()
            ->all();
    }

    public function isAllowed(): bool
    {
        if (! (bool) config('demo.login_panel.enabled', true)) {
            return false;
        }

        return in_array((string) config('app.env'), config('demo.login_panel.environments', []), true);
    }
}

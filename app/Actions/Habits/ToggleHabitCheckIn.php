<?php

namespace App\Actions\Habits;

use App\Models\Habit;
use App\Models\HabitCheckIn;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ToggleHabitCheckIn
{
    public function handle(User $user, Habit $habit): bool
    {
        Gate::forUser($user)->authorize('update', $habit);
        $this->assertHabitCanBeCheckedIn($habit);

        $today = today()->toDateString();
        $existing = HabitCheckIn::query()
            ->ownedBy($user)
            ->where('habit_id', $habit->id)
            ->whereDate('occurred_on', $today)
            ->first();

        if ($existing !== null) {
            Gate::forUser($user)->authorize('delete', $existing);
            $existing->delete();

            return false;
        }

        Gate::forUser($user)->authorize('create', HabitCheckIn::class);

        $checkIn = $habit->checkIns()->make([
            'occurred_on' => $today,
            'checked_at' => now(),
        ]);

        $checkIn->user()->associate($user);
        $checkIn->save();

        return true;
    }

    private function assertHabitCanBeCheckedIn(Habit $habit): void
    {
        if (! $habit->isArchived()) {
            return;
        }

        throw ValidationException::withMessages([
            'habit_id' => __('habits.validation.active_habit'),
        ]);
    }
}

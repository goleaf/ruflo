<?php

namespace App\Rules\Todos;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Translation\PotentiallyTranslatedString;

class OwnedActiveProject implements ValidationRule
{
    public function __construct(
        private readonly User $user,
        private readonly bool $allowSharedEditable = false,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! ctype_digit((string) $value)) {
            return;
        }

        $query = Project::query()
            ->whereKey((int) $value)
            ->active();

        if ($this->allowSharedEditable) {
            $query->where(function (Builder $query): void {
                $query
                    ->whereBelongsTo($this->user)
                    ->orWhereHas('memberships', fn (Builder $memberships): Builder => $memberships
                        ->active()
                        ->where('user_id', $this->user->id)
                        ->whereIn('role', [ProjectRole::Manager->value, ProjectRole::Editor->value]));
            });
        } else {
            $query->whereBelongsTo($this->user);
        }

        if (! $query->exists()) {
            $fail('todos.validation.owned_active_project')->translate();
        }
    }
}

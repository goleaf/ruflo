<?php

namespace App\Rules\Todos;

use App\Enums\TaskTemplateKind;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

final class TemplateChecklistItems implements ValidationRule
{
    public function __construct(
        private readonly TaskTemplateKind|string|null $kind = null,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('todos.validation.template_checklist_items')->translate();

            return;
        }

        $items = self::normalize($value);

        if (count($items) > 10) {
            $fail('todos.validation.template_checklist_items')->translate();

            return;
        }

        if ($this->requiresChecklist() && $items === []) {
            $fail('todos.validation.template_checklist_items_required')->translate();
        }
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<string>
     */
    public static function normalize(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if ($item === null || $item === '') {
                continue;
            }

            if (! is_string($item)) {
                return array_fill(0, 11, '');
            }

            $title = Str::of($item)->squish()->value();

            if ($title === '' || mb_strlen($title) > 120) {
                return array_fill(0, 11, '');
            }

            $normalized[] = $title;
        }

        return array_values($normalized);
    }

    private function requiresChecklist(): bool
    {
        $kind = $this->kind instanceof TaskTemplateKind
            ? $this->kind
            : TaskTemplateKind::tryFrom((string) $this->kind);

        return in_array($kind, [TaskTemplateKind::Checklist, TaskTemplateKind::Routine], true);
    }
}

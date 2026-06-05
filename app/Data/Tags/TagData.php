<?php

namespace App\Data\Tags;

use Illuminate\Support\Str;

/**
 * Validated, normalized input for creating a tag.
 *
 * Names are lower-cased and squished so "Work", "work ", and " work" collapse
 * to one tag instead of fragmenting the user's label set.
 */
final readonly class TagData
{
    public function __construct(
        public string $name,
        public string $color = 'zinc',
    ) {
        //
    }

    /**
     * @param  array{name: string, color?: string|null}  $validated
     */
    public static function fromArray(array $validated): self
    {
        return new self(
            name: Str::of($validated['name'])->squish()->lower()->value(),
            color: $validated['color'] ?? 'zinc',
        );
    }
}

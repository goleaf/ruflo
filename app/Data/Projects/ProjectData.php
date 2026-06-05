<?php

namespace App\Data\Projects;

/**
 * Validated, normalized input for creating or renaming a project.
 */
final readonly class ProjectData
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
            name: trim($validated['name']),
            color: $validated['color'] ?? 'zinc',
        );
    }
}

<?php

namespace App\Data\Todos;

final readonly class TodoData
{
    public function __construct(
        public string $title,
    ) {
        //
    }

    /**
     * @param  array{title: string}  $validated
     */
    public static function fromArray(array $validated): self
    {
        return new self(
            title: trim($validated['title']),
        );
    }
}

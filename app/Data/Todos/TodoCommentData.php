<?php

namespace App\Data\Todos;

final class TodoCommentData
{
    public function __construct(
        public readonly string $body,
    ) {}

    /**
     * @param  array{body: string}  $validated
     */
    public static function fromArray(array $validated): self
    {
        return new self(self::normalizeBody($validated['body']));
    }

    public static function fromBody(string $body): self
    {
        return new self(self::normalizeBody($body));
    }

    public static function normalizeBody(string $body): string
    {
        return str($body)
            ->replace(["\r\n", "\r"], "\n")
            ->replace("\0", '')
            ->trim()
            ->toString();
    }
}

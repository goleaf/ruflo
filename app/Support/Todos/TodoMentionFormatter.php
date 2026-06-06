<?php

namespace App\Support\Todos;

use App\Models\User;
use Illuminate\Support\Str;

final class TodoMentionFormatter
{
    public function baseHandleFor(User $user): string
    {
        $handle = Str::of($user->name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->limit(48, '')
            ->toString();

        return $handle === '' ? 'user-'.$user->id : $handle;
    }

    public function token(string $handle): string
    {
        return '@'.$handle;
    }

    public function appendToken(string $body, string $token): string
    {
        if ($this->containsToken($body, $token)) {
            return $body;
        }

        $matches = [];

        if (preg_match('/(^|\s)@([A-Za-z0-9._-]{0,64})$/u', $body, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $prefix = substr($body, 0, $matches[0][1]);
            $leadingWhitespace = $matches[1][0];

            return $prefix.$leadingWhitespace.$token;
        }

        $trimmedBody = trim($body);

        return $trimmedBody === '' ? $token : $trimmedBody.' '.$token;
    }

    public function removeToken(string $body, string $token): string
    {
        $bodyWithoutToken = (string) preg_replace($this->tokenPattern(ltrim($token, '@')), '', $body);

        return Str::of($bodyWithoutToken)
            ->replaceMatches('/[ \t]{2,}/', ' ')
            ->replaceMatches('/\n{3,}/', "\n\n")
            ->trim()
            ->toString();
    }

    public function containsHandle(string $body, string $handle): bool
    {
        return preg_match($this->tokenPattern($handle), $body) === 1;
    }

    public function containsToken(string $body, string $token): bool
    {
        return $this->containsHandle($body, ltrim($token, '@'));
    }

    private function tokenPattern(string $handle): string
    {
        return '/(?<![A-Za-z0-9_-])@'.preg_quote($handle, '/').'(?![A-Za-z0-9_-])/i';
    }
}

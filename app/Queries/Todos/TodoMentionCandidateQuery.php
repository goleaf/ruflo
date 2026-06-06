<?php

namespace App\Queries\Todos;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Todo;
use App\Models\TodoComment;
use App\Models\User;
use App\Support\Todos\TodoMentionFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class TodoMentionCandidateQuery
{
    public function __construct(
        private readonly TodoMentionFormatter $formatter,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function eligibleUsersFor(User $actor, Todo $todo): Collection
    {
        Gate::forUser($actor)->authorize('view', $todo);

        if (Gate::forUser($actor)->denies('create', [TodoComment::class, $todo])) {
            return collect();
        }

        $users = collect();
        $owner = User::query()
            ->select(['id', 'name', 'email'])
            ->whereKey($todo->user_id)
            ->first();

        if ($owner instanceof User) {
            $users->push($owner);
        }

        $project = $todo->project()
            ->select(['id', 'user_id'])
            ->first();

        if ($project instanceof Project && (int) $project->user_id === (int) $todo->user_id) {
            $members = ProjectMembership::query()
                ->with(['user:id,name,email'])
                ->active()
                ->where('project_id', $project->id)
                ->get()
                ->pluck('user')
                ->filter(fn (mixed $user): bool => $user instanceof User);

            $users = $users->merge($members);
        }

        return $this->sortUsers(
            $users
                ->unique('id')
                ->reject(fn (User $user): bool => (int) $user->id === (int) $actor->id)
                ->values(),
            $todo,
        );
    }

    /**
     * @return Collection<int, array{id: int, name: string, handle: string, token: string, role: string, role_color: string}>
     */
    public function candidatesFor(User $actor, Todo $todo, ?string $search = null, int $limit = 8): Collection
    {
        $candidates = $this->decoratedCandidatesFor($actor, $todo);
        $searchTerm = Str::of($search ?? '')->lower()->trim()->toString();

        if ($searchTerm !== '') {
            $candidates = $candidates->filter(
                fn (array $candidate): bool => str_contains(Str::lower($candidate['name']), $searchTerm)
                    || str_contains(Str::lower($candidate['handle']), ltrim($searchTerm, '@')),
            );
        }

        return $candidates
            ->take(max(1, min($limit, 12)))
            ->values();
    }

    /**
     * @param  list<int|string>  $selectedUserIds
     * @return Collection<int, array{id: int, name: string, handle: string, token: string, role: string, role_color: string}>
     */
    public function selectedCandidatesFor(User $actor, Todo $todo, array $selectedUserIds): Collection
    {
        $selectedIds = $this->normalizeIds($selectedUserIds);

        return $this->decoratedCandidatesFor($actor, $todo)
            ->filter(fn (array $candidate): bool => in_array($candidate['id'], $selectedIds, true))
            ->values();
    }

    /**
     * @return array{id: int, name: string, handle: string, token: string, role: string, role_color: string}|null
     */
    public function findCandidateForUser(User $actor, Todo $todo, int $userId): ?array
    {
        /** @var array{id: int, name: string, handle: string, token: string, role: string, role_color: string}|null $candidate */
        $candidate = $this->decoratedCandidatesFor($actor, $todo)->firstWhere('id', $userId);

        return $candidate;
    }

    /**
     * @param  list<int|string>  $selectedUserIds
     * @return Collection<int, array{user: User, handle: string}>
     */
    public function mentionedUsersFor(User $actor, Todo $todo, string $body, array $selectedUserIds = []): Collection
    {
        $candidates = $this->decoratedCandidatesFor($actor, $todo);
        $selectedIds = collect($this->normalizeIds($selectedUserIds));
        $bodyIds = $candidates
            ->filter(fn (array $candidate): bool => $this->formatter->containsHandle($body, $candidate['handle']))
            ->pluck('id');

        $allowedMentionIds = $candidates->pluck('id');
        $mentionIds = $selectedIds
            ->merge($bodyIds)
            ->unique()
            ->intersect($allowedMentionIds)
            ->values();

        if ($mentionIds->isEmpty()) {
            return collect();
        }

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->whereIn('id', $mentionIds->all())
            ->get()
            ->keyBy('id');

        $handlesById = $candidates->pluck('handle', 'id');

        return $mentionIds
            ->map(fn (int $userId): ?array => $users->has($userId) ? [
                'user' => $users->get($userId),
                'handle' => (string) $handlesById->get($userId),
            ] : null)
            ->filter(fn (?array $mention): bool => $mention !== null && $mention['user'] instanceof User)
            ->values();
    }

    /**
     * @return Collection<int, array{id: int, name: string, handle: string, token: string, role: string, role_color: string}>
     */
    private function decoratedCandidatesFor(User $actor, Todo $todo): Collection
    {
        $users = $this->eligibleUsersFor($actor, $todo);
        $baseHandleCounts = $users
            ->map(fn (User $user): string => $this->formatter->baseHandleFor($user))
            ->countBy();

        return $users
            ->map(function (User $user) use ($baseHandleCounts, $todo): array {
                $baseHandle = $this->formatter->baseHandleFor($user);
                $handle = ((int) $baseHandleCounts->get($baseHandle, 0)) > 1
                    ? $baseHandle.'-'.$user->id
                    : $baseHandle;
                $role = $this->roleFor($todo, $user);

                return [
                    'id' => (int) $user->id,
                    'name' => $this->displayName($user),
                    'handle' => $handle,
                    'token' => $this->formatter->token($handle),
                    'role' => $role->label(),
                    'role_color' => $role->color(),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, User>  $users
     * @return Collection<int, User>
     */
    private function sortUsers(Collection $users, Todo $todo): Collection
    {
        return $users
            ->sortBy(fn (User $user): array => [
                (int) $user->id === (int) $todo->user_id ? 0 : 1,
                Str::lower($this->displayName($user)),
                (int) $user->id,
            ])
            ->values();
    }

    private function roleFor(Todo $todo, User $user): ProjectRole
    {
        if ((int) $user->id === (int) $todo->user_id) {
            return ProjectRole::Owner;
        }

        if ($todo->project_id === null) {
            return ProjectRole::Viewer;
        }

        $role = ProjectMembership::query()
            ->active()
            ->where('project_id', $todo->project_id)
            ->where('user_id', $user->id)
            ->value('role');

        if ($role instanceof ProjectRole) {
            return $role;
        }

        return is_string($role) ? (ProjectRole::tryFrom($role) ?? ProjectRole::Viewer) : ProjectRole::Viewer;
    }

    private function displayName(User $user): string
    {
        return trim($user->name) !== '' ? $user->name : $user->email;
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->map(fn (int|string $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}

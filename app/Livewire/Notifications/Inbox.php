<?php

namespace App\Livewire\Notifications;

use App\Models\User;
use App\Queries\Notifications\NotificationInboxQuery;
use App\Queries\Todos\TodoListQuery;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('notifications.pages.inbox.title')]
final class Inbox extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'all';

    public function mount(): void
    {
        $this->normalizeFilter();
    }

    public function render(): View
    {
        return view('livewire.notifications.inbox');
    }

    public function updatedFilter(): void
    {
        $this->normalizeFilter();
        $this->resetPage();
        unset($this->notifications, $this->summary);
    }

    public function showAll(): void
    {
        $this->filter = 'all';
        $this->updatedFilter();
    }

    public function showUnread(): void
    {
        $this->filter = 'unread';
        $this->updatedFilter();
    }

    public function showRead(): void
    {
        $this->filter = 'read';
        $this->updatedFilter();
    }

    public function markRead(string $notificationId): void
    {
        $notification = $this->notification($notificationId);
        $notification->markAsRead();
        $this->clearComputedState();

        Flux::toast(variant: 'success', text: __('notifications.messages.marked_read'));
    }

    public function markUnread(string $notificationId): void
    {
        $notification = $this->notification($notificationId);
        $notification->forceFill(['read_at' => null])->save();
        $this->clearComputedState();

        Flux::toast(variant: 'success', text: __('notifications.messages.marked_unread'));
    }

    public function markAllRead(): void
    {
        $this->currentUser()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        $this->clearComputedState();

        Flux::toast(variant: 'success', text: __('notifications.messages.all_marked_read'));
    }

    /**
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    #[Computed]
    public function notifications(): LengthAwarePaginator
    {
        return app(NotificationInboxQuery::class)
            ->for($this->currentUser(), $this->normalizedFilter())
            ->paginate(10);
    }

    /**
     * @return array{all: int, unread: int, read: int}
     */
    #[Computed]
    public function summary(): array
    {
        return app(NotificationInboxQuery::class)->summaryFor($this->currentUser());
    }

    public function notificationTitle(DatabaseNotification $notification): string
    {
        $title = $notification->data['title'] ?? null;

        return is_string($title) && trim($title) !== ''
            ? $title
            : (string) __('notifications.fallback.title');
    }

    public function notificationMessage(DatabaseNotification $notification): string
    {
        $message = $notification->data['message'] ?? $notification->data['body'] ?? null;

        return is_string($message) && trim($message) !== ''
            ? $message
            : (string) __('notifications.fallback.message');
    }

    public function notificationKind(DatabaseNotification $notification): string
    {
        $kind = $notification->data['kind'] ?? $notification->type;

        return is_string($kind) && trim($kind) !== ''
            ? Str::headline($kind)
            : (string) __('notifications.fallback.kind');
    }

    public function actionUrl(DatabaseNotification $notification): ?string
    {
        $url = $notification->data['action_url'] ?? null;

        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);

        if ($url === '' || Str::startsWith($url, '//')) {
            return null;
        }

        if (Str::startsWith($url, '/')) {
            return $this->authorizedInternalUrl($url);
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $notificationHost = parse_url($url, PHP_URL_HOST);

        if (! is_string($appHost) || ! is_string($notificationHost) || $notificationHost !== $appHost) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        $internalUrl = is_string($path) && $path !== '' ? $path : '/';

        if (is_string($query) && $query !== '') {
            $internalUrl .= '?'.$query;
        }

        if (is_string($fragment) && $fragment !== '') {
            $internalUrl .= '#'.$fragment;
        }

        return $this->authorizedInternalUrl($internalUrl) === null ? null : $url;
    }

    public function filterLabel(): string
    {
        return (string) __('notifications.filters.'.$this->normalizedFilter());
    }

    private function notification(string $notificationId): DatabaseNotification
    {
        return app(NotificationInboxQuery::class)->findFor($this->currentUser(), $notificationId);
    }

    private function normalizedFilter(): string
    {
        return in_array($this->filter, ['all', 'unread', 'read'], true) ? $this->filter : 'all';
    }

    private function normalizeFilter(): void
    {
        $this->filter = $this->normalizedFilter();
    }

    private function clearComputedState(): void
    {
        unset($this->notifications, $this->summary);
    }

    private function authorizedInternalUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return $url;
        }

        $matches = [];

        if (preg_match('#^/todos/([0-9]+)$#', $path, $matches) !== 1) {
            return $url;
        }

        try {
            app(TodoListQuery::class)->findVisibleFor($this->currentUser(), (int) $matches[1]);
        } catch (ModelNotFoundException) {
            return null;
        }

        return $url;
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

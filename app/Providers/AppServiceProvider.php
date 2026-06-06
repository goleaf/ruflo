<?php

namespace App\Providers;

use App\Events\CompletedTodosCleared;
use App\Events\TodoArchived;
use App\Events\TodoChecklistChanged;
use App\Events\TodoCompleted;
use App\Events\TodoCreated;
use App\Events\TodoDeleted;
use App\Events\TodoReopened;
use App\Events\TodoRestoredFromTrash;
use App\Events\TodoUnarchived;
use App\Events\TodoUpdated;
use App\Listeners\RecordTodoActivity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        $this->configureUrlDefaults();
        $this->configureAuthorization();
        $this->configureActivityListeners();

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Keep generated links stable for web-triggered flows and signed URLs.
     */
    protected function configureUrlDefaults(): void
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl === '') {
            return;
        }

        URL::forceRootUrl($appUrl);

        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Register application-wide abilities that do not map to a model policy.
     */
    protected function configureAuthorization(): void
    {
        Gate::define('access-maintenance-center', fn (User $user): bool => $user->is_admin);
    }

    /**
     * Record meaningful domain events synchronously for restricted hosting.
     */
    protected function configureActivityListeners(): void
    {
        Event::listen(TodoCreated::class, RecordTodoActivity::class);
        Event::listen(TodoUpdated::class, RecordTodoActivity::class);
        Event::listen(TodoCompleted::class, RecordTodoActivity::class);
        Event::listen(TodoReopened::class, RecordTodoActivity::class);
        Event::listen(TodoArchived::class, RecordTodoActivity::class);
        Event::listen(TodoUnarchived::class, RecordTodoActivity::class);
        Event::listen(TodoDeleted::class, RecordTodoActivity::class);
        Event::listen(TodoRestoredFromTrash::class, RecordTodoActivity::class);
        Event::listen(TodoChecklistChanged::class, RecordTodoActivity::class);
        Event::listen(CompletedTodosCleared::class, RecordTodoActivity::class);
    }
}

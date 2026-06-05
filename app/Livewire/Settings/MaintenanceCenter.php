<?php

namespace App\Livewire\Settings;

use App\Actions\Maintenance\BuildMaintenanceSnapshot;
use App\Actions\Maintenance\ClearCompiledViews;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('maintenance.pages.center.title')]
class MaintenanceCenter extends Component
{
    use AuthorizesRequests;

    /**
     * @var array<string, mixed>
     */
    public array $snapshot = [];

    public ?string $lastAction = null;

    public function mount(): void
    {
        $this->authorize('access-maintenance-center');

        $this->refresh();
    }

    public function refresh(): void
    {
        $this->authorize('access-maintenance-center');

        $this->snapshot = app(BuildMaintenanceSnapshot::class)();
    }

    public function flushApplicationCache(): void
    {
        $this->authorize('access-maintenance-center');

        Cache::flush();

        $this->lastAction = __('maintenance.messages.cache_flushed');

        Flux::toast(variant: 'success', text: $this->lastAction);

        $this->refresh();
    }

    public function clearCompiledViews(): void
    {
        $this->authorize('access-maintenance-center');

        $deleted = app(ClearCompiledViews::class)();

        $this->lastAction = trans_choice('maintenance.messages.compiled_views_cleared', $deleted, ['count' => $deleted]);

        Flux::toast(variant: 'success', text: $this->lastAction);

        $this->refresh();
    }

    public function render(): View
    {
        return view('livewire.settings.maintenance-center');
    }
}

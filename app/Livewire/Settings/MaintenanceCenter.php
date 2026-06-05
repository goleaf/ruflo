<?php

namespace App\Livewire\Settings;

use App\Actions\Maintenance\BuildMaintenanceSnapshot;
use App\Actions\Maintenance\ClearCompiledViews;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('maintenance.pages.center.title')]
class MaintenanceCenter extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $snapshot = [];

    public ?string $lastAction = null;

    public function mount(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        $this->snapshot = app(BuildMaintenanceSnapshot::class)();
    }

    public function flushApplicationCache(): void
    {
        Cache::flush();

        $this->lastAction = __('maintenance.messages.cache_flushed');

        Flux::toast(variant: 'success', text: $this->lastAction);

        $this->refresh();
    }

    public function clearCompiledViews(): void
    {
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

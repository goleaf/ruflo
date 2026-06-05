<?php

namespace App\Livewire\Settings;

use App\Actions\Setup\InspectSetupStatus;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Setup status')]
class SetupStatus extends Component
{
    /**
     * @var array{ready: bool, checks: list<array{key: string, ok: bool, value: string, value_key?: string}>, pending_migrations: list<string>, database_error: string|null}
     */
    public array $status = [
        'ready' => false,
        'checks' => [],
        'pending_migrations' => [],
        'database_error' => null,
    ];

    public function mount(): void
    {
        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $this->status = app(InspectSetupStatus::class)()->toArray();
    }

    public function render(): View
    {
        return view('livewire.settings.setup-status');
    }
}

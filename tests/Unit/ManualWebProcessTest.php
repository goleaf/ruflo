<?php

use App\Actions\Processing\RunManualWebProcess;
use App\Contracts\Processing\ManualWebProcess;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('manual web processor supports dry run bounded chunks details and resume', function () {
    config([
        'hosting.web_processing.chunk_size' => 2,
        'hosting.web_processing.detail_limit' => 1,
    ]);

    $user = User::factory()->create();
    $other = User::factory()->create();
    $first = Todo::factory()->for($user)->create(['title' => 'Process first']);
    $second = Todo::factory()->for($user)->create(['title' => 'Process second']);
    $third = Todo::factory()->for($user)->create(['title' => 'Process third']);
    $foreign = Todo::factory()->for($other)->create(['title' => 'Process foreign']);

    $process = new class implements ManualWebProcess
    {
        public function key(): string
        {
            return 'tests.complete_todos';
        }

        /**
         * @return Builder<Model>
         */
        public function query(User $user): Builder
        {
            /** @var Builder<Model> $query */
            $query = $user->todos()
                ->getQuery()
                ->active()
                ->where('title', 'like', 'Process%')
                ->orderBy('id');

            return $query;
        }

        public function process(User $user, Model $record): bool
        {
            if (! $record instanceof Todo) {
                return false;
            }

            $record->forceFill([
                'is_completed' => true,
            ])->save();

            return true;
        }

        /**
         * @return array{id: int, title: string}
         */
        public function detail(Model $record): array
        {
            return [
                'id' => (int) $record->getKey(),
                'title' => $record instanceof Todo ? $record->title : 'Unavailable',
            ];
        }
    };

    $dryRun = app(RunManualWebProcess::class)->handle($user, $process, dryRun: true);

    expect($dryRun->processKey)->toBe('tests.complete_todos')
        ->and($dryRun->matchedCount)->toBe(3)
        ->and($dryRun->processedCount)->toBe(2)
        ->and($dryRun->changedCount)->toBe(0)
        ->and($dryRun->skippedCount)->toBe(1)
        ->and($dryRun->details)->toHaveCount(1)
        ->and($dryRun->hasRemaining())->toBeTrue()
        ->and($first->refresh()->is_completed)->toBeFalse()
        ->and($second->refresh()->is_completed)->toBeFalse()
        ->and($third->refresh()->is_completed)->toBeFalse();

    $firstRun = app(RunManualWebProcess::class)->handle($user, $process);

    expect($firstRun->matchedCount)->toBe(3)
        ->and($firstRun->processedCount)->toBe(2)
        ->and($firstRun->changedCount)->toBe(2)
        ->and($firstRun->skippedCount)->toBe(1)
        ->and($first->refresh()->is_completed)->toBeTrue()
        ->and($second->refresh()->is_completed)->toBeTrue()
        ->and($third->refresh()->is_completed)->toBeFalse()
        ->and($foreign->refresh()->is_completed)->toBeFalse();

    $resume = app(RunManualWebProcess::class)->handle($user, $process);

    expect($resume->matchedCount)->toBe(1)
        ->and($resume->processedCount)->toBe(1)
        ->and($resume->changedCount)->toBe(1)
        ->and($resume->skippedCount)->toBe(0)
        ->and($resume->hasRemaining())->toBeFalse()
        ->and($third->refresh()->is_completed)->toBeTrue()
        ->and($foreign->refresh()->is_completed)->toBeFalse();
});

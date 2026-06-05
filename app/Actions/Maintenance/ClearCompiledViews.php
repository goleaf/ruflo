<?php

namespace App\Actions\Maintenance;

use Illuminate\Support\Facades\File;

class ClearCompiledViews
{
    public function __invoke(): int
    {
        $files = File::glob(storage_path('framework/views/*.php')) ?: [];
        $deleted = 0;

        foreach ($files as $file) {
            if (File::delete($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}

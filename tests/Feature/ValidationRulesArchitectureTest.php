<?php

use Illuminate\Support\Facades\File;

test('custom validation rules are implemented and use translated failure messages', function () {
    $ruleFiles = collect(File::allFiles(app_path('Rules')))
        ->map(fn (SplFileInfo $file): string => $file->getPathname())
        ->values();

    expect($ruleFiles->map(fn (string $path): string => str_replace(app_path('Rules').'/', '', $path))->all())
        ->toBe([
            'Goals/GoalTitle.php',
            'Goals/MilestoneTitle.php',
            'Tags/TagName.php',
            'Todos/BoardStatus.php',
            'Todos/CalendarMonth.php',
            'Todos/ChecklistItemTitle.php',
            'Todos/DueDate.php',
            'Todos/InboxCaptureTitle.php',
            'Todos/OwnedActiveProject.php',
            'Todos/OwnedTag.php',
            'Todos/OwnedTodo.php',
            'Todos/SavedViewName.php',
            'Todos/TemplateChecklistItems.php',
            'Todos/TemplateName.php',
        ]);

    $ruleFiles->each(function (string $path): void {
        $source = file_get_contents($path);

        expect($source)
            ->toContain('implements ValidationRule')
            ->toContain('function validate(')
            ->not->toContain('        //')
            ->toContain('->translate();');
    });
});

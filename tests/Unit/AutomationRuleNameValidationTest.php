<?php

use App\Rules\Automation\AutomationRuleName;

test('automation rule name normalizer accepts visible names and squishes spacing', function () {
    expect(AutomationRuleName::normalize('  Promote   overdue work  '))->toBe('Promote overdue work')
        ->and(AutomationRuleName::normalize(str_repeat('x', 80)))->toBe(str_repeat('x', 80));
});

test('automation rule name normalizer rejects empty non string and too long values', function () {
    expect(AutomationRuleName::normalize(''))->toBeNull()
        ->and(AutomationRuleName::normalize('   '))->toBeNull()
        ->and(AutomationRuleName::normalize(['name']))->toBeNull()
        ->and(AutomationRuleName::normalize(str_repeat('x', 81)))->toBeNull();
});

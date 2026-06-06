<?php

namespace App\Enums;

enum HabitFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';

    public function periodTranslationKey(): string
    {
        return 'habits.period.'.$this->value;
    }
}

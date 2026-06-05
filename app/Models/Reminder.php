<?php

namespace App\Models;

use App\Policies\ReminderPolicy;
use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UsePolicy(ReminderPolicy::class)]
class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
    use HasFactory;
}

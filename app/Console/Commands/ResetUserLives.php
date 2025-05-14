<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserLives;
use Carbon\Carbon;

class ResetUserLives extends Command
{
    protected $signature = 'lives:reset';
    protected $description = 'Reset user lives according to plan once per day';

    public function handle()
    {
        $today = Carbon::now('UTC')->toDateString();

        UserLives::where(function ($query) use ($today) {
            $query->whereNull('last_reset')
                  ->orWhere('last_reset', '<', $today);
        })->each(function ($userLife) use ($today) {
            if ($userLife->is_unlimited) return;

            $defaultLives = match ($userLife->user->plan ?? 'free') {
                'premium' => null,
                'basic' => 5,
                default => 2
            };

            if ($defaultLives !== null) {
                $userLife->lives_remaining = $defaultLives;
                $userLife->last_reset = $today;
                $userLife->save();
            }
        });

        $this->info('User lives reset completed.');
    }
}

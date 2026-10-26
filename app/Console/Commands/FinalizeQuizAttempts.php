<?php

namespace App\Console\Commands;

use App\Models\QuizAttempt;
use App\Services\QuizGrader;
use Illuminate\Console\Command;

class FinalizeQuizAttempts extends Command
{
    protected $signature = 'quiz:finalize';

    protected $description = 'Kumpulkan otomatis percobaan quiz yang waktunya habis.';

    public function handle(): int
    {
        $n = 0;
        QuizAttempt::where('status', 'in_progress')->where('deadline_at', '<', now())->limit(500)->get()
            ->each(function ($a) use (&$n) {
                QuizGrader::finalize($a);
                $n++;
            });
        $this->info("Dikumpulkan: $n");

        return self::SUCCESS;
    }
}

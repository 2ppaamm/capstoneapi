<?php

namespace App\Jobs;

use App\Question;
use App\Test;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessQuestionAssignment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $questionIds;
    protected $testId;
    protected $userId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($questionIds, $testId, $userId)
    {
        $this->questionIds = $questionIds;
        $this->testId = $testId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = User::find($this->userId);
        $test = Test::find($this->testId);
        $questions = Question::findMany($this->questionIds);

        foreach ($questions as $question) {
            $question->assigned($user, $test);
        }
    }
}
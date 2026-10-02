<?php

namespace App\Jobs;

use App\Enums\HeadlineVerdict;
use App\Lib\HeadlineEvaluator;
use App\Models\Headline;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProcessHeadline implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public Headline $headline) {}

    public function uniqueId(): string
    {
        return (string) $this->headline->id;
    }

    public function handle(HeadlineEvaluator $evaluator): void
    {
        Cache::lock("process-headline-{$this->headline->id}", 120)->block(60, function () use ($evaluator): void {
            $headline = $this->headline->fresh();

            if ($headline->verdict !== null) {
                return;
            }

            $evaluation = $evaluator->evaluate($headline);

            if ($evaluation['verdict'] !== HeadlineVerdict::BLOCKED) {
                $headline->fetchArticleImage();
            }

            DB::transaction(function () use ($headline, $evaluation): void {
                $headline->verdict = $evaluation['verdict'];
                $headline->verdict_reason = $evaluation['reason'];

                if ($evaluation['verdict'] !== HeadlineVerdict::BLOCKED) {
                    $headline->topic_id = $headline->createTopic($evaluation['question'])->id;
                }

                $headline->save();
            });
        });
    }
}

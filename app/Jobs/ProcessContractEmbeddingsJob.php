<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Contracts\Actions\ProcessContractEmbeddingsAction;
use App\Domain\Contracts\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessContractEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Contract $contract
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ProcessContractEmbeddingsAction $action): void
    {
        $action->handle($this->contract);
    }
}

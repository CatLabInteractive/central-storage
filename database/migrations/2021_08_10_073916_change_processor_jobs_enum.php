<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeProcessorJobsEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $newStates = [
            \App\Models\ProcessorJob::STATE_PREPARED,
            \App\Models\ProcessorJob::STATE_PENDING,
            \App\Models\ProcessorJob::STATE_FINISHED,
            \App\Models\ProcessorJob::STATE_FAILED,
            \App\Models\ProcessorJob::STATE_RESCHEDULED
        ];

        \DB::statement("ALTER TABLE processor_jobs MODIFY COLUMN state ENUM('" . implode('\',\'', $newStates). "')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $newStates = [
            \App\Models\ProcessorJob::STATE_PREPARED,
            \App\Models\ProcessorJob::STATE_PENDING,
            \App\Models\ProcessorJob::STATE_FINISHED,
            \App\Models\ProcessorJob::STATE_FAILED
        ];

        \DB::statement("ALTER TABLE processor_jobs MODIFY COLUMN state ENUM('" . implode('\',\'', $newStates) . "')");
    }
}

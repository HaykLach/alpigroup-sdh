<?php

namespace App\Console\Commands;

use App\Contracts\Processor\ProcessorInterface;
use App\Models\Pim\Job\PimJob;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

abstract class BaseCommand extends Command
{
    protected Logger $logger;

    protected string $jobName = '';

    public function __construct()
    {
        parent::__construct();

        $loggerFileName = str_replace(':', '-', $this->signature);
        $logger = new Logger($loggerFileName);
        $logger->pushHandler(new StreamHandler(storage_path('/logs/'.Carbon::now()->format('Y-m-d').'-'.$loggerFileName.'.log'), Level::Debug));

        $this->logger = $logger;
    }

    /**
     * handle command logic
     */
    final public function handle(): void
    {
        $this->logger->info($this->description.' command started! '.Carbon::now()->format('Y-m-d H:i:s'));
        $this->info($this->description.' command started!');
        $lastExecutionDate = Carbon::now()->format('Y-m-d H:i:s');
        $startTime = time();
        try {
            $processor = $this->getProcessor();
            $result = $processor->process($this->getJobName());
            $endTime = time();

            $this->writeJobExecutionInfo($lastExecutionDate, $endTime - $startTime, $result);
        } catch (Exception $exception) {
            $this->logger->error('Error in '.$this->description.' command: '.$exception->getMessage().$exception->getTraceAsString());
            $this->error('Error in '.$this->description.' command: '.$exception->getMessage().PHP_EOL.$exception->getTraceAsString());
            $endTime = time();

            $this->writeJobExecutionInfo($lastExecutionDate, $endTime - $startTime, ['success' => false, 'message' => $exception->getMessage()]);

            return;
        }

        $this->logger->info($this->description.' command finished with result: ', $result);

        $this->info($this->description.' command finished!');
    }

    /**
     * returns the command processor instance
     */
    abstract protected function getProcessor(): ProcessorInterface;

    protected function getJobName(): string
    {
        return $this->jobName;
    }

    /**
     * save in db job execution information
     */
    private function writeJobExecutionInfo(string $lastExecutionDate, int $lastExecutionDuration, array $lastExecutionResult): void
    {
        $pimJob = PimJob::where('job_name', $this->getJobName())->first();

        $dataToSave = [
            'job_name' => $this->getJobName(),
            'last_execution_date' => $lastExecutionDate,
            'last_execution_duration' => $lastExecutionDuration,
            'last_execution_result' => json_encode($lastExecutionResult),
        ];
        if ($pimJob) {
            $pimJob->update($dataToSave);

            return;
        }

        PimJob::create($dataToSave);
    }
}

<?php

namespace App\Processor;

use App\Contracts\Job\JobLogRepositoryInterface;
use App\Contracts\Job\JobRepositoryInterface;
use App\Contracts\Processor\MailSendProcessorInterface;
use App\Mail\BaseMail;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Mail;

abstract class BaseMailProcessor extends BaseProcessor implements MailSendProcessorInterface
{
    private JobRepositoryInterface $jobRepository;

    private JobLogRepositoryInterface $jobLogRepository;

    public function __construct(JobRepositoryInterface $jobRepository, JobLogRepositoryInterface $jobLogRepository)
    {
        parent::__construct();

        $this->jobRepository = $jobRepository;
        $this->jobLogRepository = $jobLogRepository;
    }

    /**
     * @throws Exception
     */
    final public function process(string $commandName): array
    {
        $reportJobName = $this->getReportCommandName();

        $reportJob = $this->jobRepository->findByName($reportJobName);
        if (! $reportJob) {
            $this->logger->error('Job with name '.$reportJobName.' not found!');
            throw new Exception('Job with name '.$reportJobName.' not found!');
        }

        $jobLogs = $this->jobLogRepository->getJobLogsByDate($reportJob->id, Carbon::now()->format('Y-m-d'));

        $result = $this->getMailData($jobLogs);

        try {
            $mailStruct = new ($this->getMailStruct())($result);
            if (! $mailStruct instanceof BaseMail) {
                $this->logger->error('Mail struct must be an instance of '.BaseMail::class);

                throw new Exception('Mail struct must be an instance of '.BaseMail::class);
            }

            Mail::to('test@test.com')->send($mailStruct);
        } catch (Exception $exception) {
            $this->logger->error('Exception while sending '.$this->getReportCommandName().' report mail: '.$exception->getMessage());

            throw $exception;
        }

        $resultCount = count($result);
        $this->logger->info($resultCount.' data sent in mail for '.$reportJobName.' job');

        return [self::IMPORT_STATUS_CREATED => $resultCount];
    }
}

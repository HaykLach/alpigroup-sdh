<?php

namespace App\Contracts\Processor;

use Illuminate\Database\Eloquent\Collection;

interface MailSendProcessorInterface extends ProcessorInterface
{
    public function process(string $commandName): array;

    public function getMailStruct(): string;

    public function getReportCommandName(): string;

    public function getMailData(Collection $jobLogs): array;
}

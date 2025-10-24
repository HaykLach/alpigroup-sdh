<?php

namespace App\Utils\Log;

use Carbon\Carbon;

class LogUtil
{
    public static function writeRequestLog(string $requestCommandName, string $logData): void
    {
        $logFileName = self::getLogFileName($requestCommandName);
        $currentTimestamp = Carbon::now()->format('Y-m-d H:i:s.u');

        $logFilePath = base_path().'/command_logs/'.$requestCommandName;

        if (! file_exists(base_path().'/command_logs/'.$requestCommandName)) {
            mkdir(base_path().'/command_logs/'.$requestCommandName, 0777, true);
        }

        $logRow = "[$currentTimestamp] ".$logData."\n";
        file_put_contents($logFilePath.'/'.$logFileName, $logRow, FILE_APPEND | LOCK_EX);
    }

    private static function getLogFileName(string $requestCommandName): string
    {
        return Carbon::now()->format('Y-m-d').'-'.$requestCommandName.'.log';
    }
}

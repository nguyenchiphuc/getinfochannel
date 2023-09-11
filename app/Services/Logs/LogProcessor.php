<?php
namespace App\Services\Logs;
use Route;

class LogProcessor
{
    public function __invoke(array $record)
    {
        $record['extra'] = [
            'origin' => request()->headers->get('origin'),
            'ip' => request()->server('REMOTE_ADDR'),
            'user_agent' => request()->server('HTTP_USER_AGENT'),
        ];
        return $record;
    }
}
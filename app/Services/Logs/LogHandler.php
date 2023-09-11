<?php
namespace App\Services\Logs;
use App\Events\Logs\LogMonologEvent;
use Monolog\Logger;
use App\Models\Log;
use Carbon\Carbon;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Formatter\FormatterInterface;

class LogHandler extends AbstractProcessingHandler
{
    public function __construct($level = Logger::DEBUG)
    {
        parent::__construct($level);
    }
    protected function write(array $record): void
    {
        // Simple store implementation
        // $log = new Log();
        // $log->fill($record['formatted']);
        // $log->save();
        $array = $record['formatted'];
        $array['updated_at'] = \Carbon\Carbon::now();

        Log::updateOrCreate([
            'description' => $record['formatted']['description'],
            'level' => $record['formatted']['level'],
            'user_agent' => $record['formatted']['user_agent'],
        ], $array);
        // Queue implementation
        // event(new LogMonologEvent($record));
    }
    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter() : FormatterInterface
    {
        return new LogFormatter();
    }
}
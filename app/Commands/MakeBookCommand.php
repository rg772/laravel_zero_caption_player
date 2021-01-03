<?php

namespace App\Commands;

use App\Workers;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class MakeBookCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'book {file}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Makes a stand alone text file to be imported into a multilingual reader app';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $in_file = $this->argument('file');
        $captions=Workers::extractCaptions($in_file);

        // unset the "waiting for notice"
        unset($captions[1]);

        foreach ($captions as $seconds=>$text) {

            $out_line = sprintf(
                "%s  %s",
                Workers::convertSecondsToMinutes($seconds),
                strip_tags(Workers::filterIndividualLine($text))
            );

            $this->line("");
            $this->line($out_line);

        }




    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}

<?php

namespace App\Commands;

use App\Workers;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use League\CLImate\CLImate;

class ParseCaptionsCommand extends Command
{

    // climate CLI output
    protected $climate;


    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'do {file} {second?} {--start=0} {--media=""} {--caption-delay=0}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * Just to keep track if we have a second file
     * @var bool
     */
    private $second_srt_flag;


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {

        // load climate
        $this->climate = new CLImate();

        // source of the captions file
        $file = $this->argument('file');

        // optional second file
        $second_file = $this->argument('second') ?? null;

        // get second:text key value pair from SRT file
        $this->line("Parsing $file");
        $first_captions = Workers::extractCaptions($file);

        // second captions
        if ($second_file != null) {
            $this->line("Parsing $second_file");
            $second_captions = Workers::extractCaptions($second_file);
            $this->second_srt_flag = true;
        } else {
            $second_captions = [];
            $this->second_srt_flag = false;
        }


        // Get start point. Use ?? in case start option is null
        $start = Workers::convertTimeToSeconds($this->option('start') ?? "0");


        // get sending second to know when to end the for loop
        $length_in_seconds = max(
            array_key_last($first_captions),
            array_key_last($second_captions)
        );

        // Any media to enque?
        $this->enqueMedia();

        // do we need any caption delay?
        $this->setCaptionDelay();

        // go with the loop
        $this->theLoop($start, $length_in_seconds, $first_captions, $second_captions);

    }

    /**
     *  The Main loop
     *
     * @param int $start
     * @param int $length_in_seconds
     * @param array $captions
     * @param array $second_captions
     */
    protected function theLoop(int $start, int $length_in_seconds, array $captions, array $second_captions): void
    {
        $current_line = "";
        $current_key = 0;
        $prev_key = 0;
        $prev_key_2 = 0;
        $previous_line = "";
        $previous_line_2 = "";

        $secondary_current = "";
        $secondary_prev = "";
        $secondary_prev_2 = "";




        for ($i = $start; $i < $length_in_seconds; $i++) {

            // If there is a new line start change out the current text.
            if (isset($captions[$i])) {
                $previous_line_2 = $previous_line ?? "";
                $previous_line = $current_line ?? "";
                $current_line = Workers::filterIndividualLine($captions[$i]);

                $prev_key_2 = $prev_key;
                $prev_key = $current_key;
                $current_key = $i;
            }

            if (isset($second_captions[$i])) {
                $secondary_current = sprintf(
                    "(%s)",
                    Workers::filterIndividualLine($second_captions[$i])
                ) ;
                $secondary_prev_2 = $secondary_prev;
                $secondary_prev = $secondary_current;
            }

            // clear screen first
            Workers::clearScreen();

            // then, print out new line with second marker...
            $this->printLine($prev_key_2 , $previous_line_2);

            // last line
            $this->printLine($prev_key, $previous_line);
            if ($this->second_srt_flag) {
                $this->printLine($prev_key, $secondary_prev);
            }
            // current line
            $this->printLine($i, $current_line, "current");
            if ($this->second_srt_flag) {
                $this->printLine($i, $secondary_current, "secondary");
            }

            // sleep
            sleep(1);
        }
    }

    /**
     * @param int $i
     * @param string $current_line
     * @param bool $current
     */
    protected function printLine(int $i, string $current_line, string $mode = "normal"): void
    {
        $text = sprintf(
            "%s - %s",
            Workers::convertSecondsToMinutes($i),
            $current_line
        );

        if ($mode == 'current') {
            $this->climate->bold()->out($text);
        }
        elseif ($mode == 'secondary') {
            $this->climate->out($text);
        }
        else {
            $this->climate->whisper()->out($text);
        }

    }

    private function enqueMedia()
    {

        $media = $this->option('media') ?? null;

        // no media, no worry
        if (is_null($media)) return;

        // curb for MacOS at the moment
        if (PHP_OS === "Darwin") {

            if (Str::endsWith($media, 'm4a')) {
                $this->info("Starting player in new thread. " );
                $this->info('note: kill player via "pkill afplayer" ');
                system('afplay ' . $media . ' > /dev/null 2>&1 &');

            }
        } else {
            $this->info("TODO: Support other media OS media play.");
        }
    }

    private function setCaptionDelay()
    {
        $caption_delay = $this->option('caption-delay') ?? 0;
        if($caption_delay === 0 ) return;
        sleep($caption_delay);
    }


}

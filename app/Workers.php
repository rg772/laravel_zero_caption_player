<?php


namespace App;


use App\Commands\ParseCaptionsCommand;
use Benlipp\SrtParser\Parser;
use Illuminate\Support\Str;
use League\CLImate\CLImate;

class Workers
{

    /**
     * Converts mm:ss into seconds
     *
     * @param string $mmss
     * @return int
     */
    public static function convertTimeToSeconds(string $mmss): int
    {
        // if no colon just return zero
        if (!Str::contains($mmss, ":")) return 0;

        list($min, $sec) = explode(':', $mmss);

        return 1 * ($min * 60) + $sec;
    }

    /**
     * converts back to minute:second format for easier reading
     * @param int $seconds
     * @return false|string
     */
    public static function convertSecondsToMinutes(int $seconds)
    {
        return gmdate("i:s", $seconds);
    }

    /**
     * Extracted out to perhaps later take into account different OS's
     */
    public static function clearScreen(): void
    {

        // Climate
        (new CLImate())->clear();

    }

    /**
     *
     * @param $caption_line
     * @return string|string[]
     *
     * Replaces new line with new line and tab to keep
     */
    public static function filterIndividualLine($caption_line)
    {
        return str_replace(PHP_EOL, PHP_EOL . "\t", $caption_line);
    }

    /**
     * @param string $file
     * @return array
     * @throws \Benlipp\SrtParser\Exceptions\FileNotFoundException
     */
    public static function extractCaptions(string $file): array
    {

        // get subtitle format
        $parser = new Parser();
        $parser->loadFile($file);
        $captions = $parser->parse();

        // define key value pair of start time as int
        $intCaptions = [];

        // put in a place holder early
        $intCaptions[1] = "Starting soon..";

        foreach ($captions as $key => $caption) {
            $startAsInteger = (int) $caption->startTime;
            $intCaptions[$startAsInteger] = trim($caption->text);
        }



        return $intCaptions;

    }

    /**
     *  Merge second set of captions in.
     *
     * @param array $captions
     * @param array $second_captions
     * @param ParseCaptionsCommand $instance
     * @return array
     */
    public static function mergeCaptions(array $captions, array $second_captions)
    {
        // if there is no second caption, just return
        if (is_null($second_captions)) return $captions;


        // find the absolute last index
        $last_second = max(array_key_last($captions), array_key_last($second_captions));

        // loop though to carefully merge the two.
        $combined_captions = [];
        for ($i = 0; $i < $last_second; $i++) {

            // If either have an index at this point, move over to new array
            if (isset($captions[$i]) && isset($second_captions[$i])) {
                $combined_captions[$i] = $captions[$i] . " " . $second_captions[$i];
            } elseif (isset($captions[$i])) {
                $combined_captions[$i] = $captions[$i];
            } elseif (isset($second_captions[$i])) {
                $combined_captions[$i] = $second_captions[$i];
            } else {
                // nothing happens
            }


        }

        // filter out, make sure no blank lines
        $combined_captions = array_filter($combined_captions, function ($item) {
            return (!trim($item) == "");
        });


        // return new captions combined.
        return $combined_captions;
    }
}

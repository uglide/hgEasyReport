<?php
/**
 * Created by Igor Malinovskiy <u.glide@gmail.com>
 * glide.name
 * Date: 27.07.12
 */

require_once 'Report.php';

try {

    $pathToRepo = '';
    $mode = Report::ALL_MODE;

    /*
     * Load parameters from args
     */
    if ($argc > 0) {
        $pathToRepo = $argv[1];

        if (isset($argv[2])) {
            $mode = $argv[2];
        }
    } else {
        throw new Exception('No valid arguments passed! You must provide args: %path_to_repo% [%mode%]');
    }

    //load config
    $config = parse_ini_file('config.ini', true);

    //change current dir to dir with repo
    chdir($pathToRepo);

    //array for command output
    $output = array();

    date_default_timezone_set($config['settings']['time_zone']);

    switch ($mode) {
        case Report::TODAY_MODE:
            $timeRange = array(
                'start' => date("Y-m-d 00:00:00 O"),
                'end'   => date("Y-m-d 23:59:59 O")
            );
            break;
        case Report::TWO_DAYS_MODE:
            $timeRange = array(
                'start' => date("Y-m-d 00:00:00 O", strtotime("yesterday")),
                'end'   => date("Y-m-d 23:59:59 O")
            );
            break;
        case Report::WEEK_MODE:
            $timeRange = array(
                'start' => date("Y-m-d 00:00:00 O", strtotime("this week")),
                'end'   => date("Y-m-d 23:59:59 O")
            );
            break;
        default:
            $timeRange = array();
            break;
    }


    //get log

    if (is_dir($pathToRepo . '/.hg')) {

        if (empty($timeRange)) {
            $timeRangeStr = '';
        } else {
            $timeRangeStr = ' -d "' . $timeRange['start'] . ' to ' . $timeRange['end'] . '" ';
        }

        exec(
            'hg log -u "' . $config['hg']['user'] . '" --no-merges '
                . $timeRangeStr . ' --template="{branch}<br/>{date|isodate}<br/>{desc}\n"',
            $output
        );
    } elseif (is_dir($pathToRepo . '/.git')) {

        if (empty($timeRange)) {
            $timeRangeStr = '';
        } else {
            $timeRangeStr = ' --since="' . $timeRange['start'] . '" --before="' . $timeRange['end'] . '" ';
        }

        exec(
            'git log --author="' . $config['git']['user'] . '" --no-merges '
                . $timeRangeStr . ' --format=format:" <br/>%ai<br/>%B %N" --decorate=short',
            $output
        );
    } else {
        throw new Exception("Git or mercurial repository not founded!");
    }

    $report = new Report($output, $mode, $config['settings']);

    echo $report->getReport();

} catch (Exception $ex) {
    echo 'Error occurred : ' . $ex->getMessage();
}
?>
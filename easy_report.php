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

        if (isset($argv[2])
            && ($argv[2] == Report::ALL_MODE || $argv[2] == Report::TODAY_MODE)
        ) {
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

    $dayRange = '';

    if ($mode == Report::TODAY_MODE) {
        date_default_timezone_set($config['time_settings']['time_zone']);
        $dayRange = ' -d "' . date("Y-m-d 00:00:00 O") . ' to ' . date("Y-m-d 23:59:59 O") . '" ';
    }

    //get log
    exec(
        'hg log -u "' . $config['hg']['user'] . '" --no-merges '
            . $dayRange . ' --template="{branch}<br/>{date|isodate}<br/>{desc}\n"',
        $output
    );

    $report = new Report($output, $mode, $config['time_settings']);

    echo $report->getReport();

} catch (Exception $ex) {
    echo 'Error occurred : ' . $ex->getMessage();
}
?>
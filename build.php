<?php
/**
 * Created by Igor Malinovskiy <u.glide@gmail.com>
 * glide.name
 * Date: 28.07.12
 */

/**
 * Note: before run check option phar.readonly = Off
 */

$phar = new Phar('hg-easy-report.phar');
$phar['easy_report.php'] = file_get_contents('easy_report.php');
$phar['Report.php'] = file_get_contents('Report.php');
$phar->setDefaultStub('easy_report.php', 'easy_report.php');

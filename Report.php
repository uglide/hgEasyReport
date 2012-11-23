<?php
/**
 * Created by Igor Malinovskiy <u.glide@gmail.com>
 * glide.name
 * Date: 28.07.12
 */
class Report
{
    /*
     * Currently available mods
     */
    const ALL_MODE = 'all';
    const TODAY_MODE = 'today';

    /*
     * Info constants
     */
    const version = 'v0.12';
    const DIVIDER = '=================================';

    /*
     * Input data format
     */
    const BRANCH = 0;
    const DATE = 1;
    const COMMENT = 2;

    /**
     * @var array
     */
    protected $_timeSettings = array(
        'day_start' => '10:00',
        'break_start' => '13:00',
        'break_end' => '14:00',
        'time_zone' => 'Europe/Kiev',
        'show_time_in_task_list' => false
    );

    /**
     * @var string
     */
    protected $_mode;

    /**
     * @var array
     */
    protected $_logData = array();

    /**
     * @var DateTimeZone|null
     */
    protected $_timeZone = null;

    /**
     * @param array $historyFromRepo
     * @param string $mode
     * @param array $timeSettings
     * @throws InvalidArgumentException
     */
    public function __construct(array $historyFromRepo, $mode = '', array $timeSettings = array())
    {
        if (!empty($historyFromRepo)) {
            $this->_logData = $historyFromRepo;
        } else {
            throw new InvalidArgumentException('History from repo is empty!');
        }

        if (!empty($timeSettings)) {
            $this->_timeSettings = array_merge($this->_timeSettings, $timeSettings);
        }

        if (!empty($mode)) {
            $this->_mode = $mode;
        }

        $this->_timeZone = new DateTimeZone($this->_timeSettings['time_zone']);
    }

    /**
     * @return mixed
     */
    public function getReport()
    {
        //change data direction in log
        $this->_logData = array_reverse($this->_logData);

        //tasks
        $tasks = array();

        $previousTime = null;

        foreach ($this->_logData as $line) {

            $logParts = explode('<br/>', $line);

            $currLineTime = new DateTime($logParts[self::DATE], $this->_timeZone);

            if (null == $previousTime) {
                $previousTime = new DateTime(
                    ($this->_mode == self::TODAY_MODE) ? $this->_timeSettings['day_start'] : $logParts[self::DATE],
                    $this->_timeZone
                );
            }

            $breakStartTime = new DateTime($this->_timeSettings['break_start']);
            $breakEndTime = new DateTime($this->_timeSettings['break_end']);

            if ($currLineTime > $breakEndTime && $previousTime < $breakStartTime) {
                $breakTime = $breakStartTime->diff($breakEndTime);
                $taskTime = $previousTime->diff($currLineTime->sub($breakTime));
            } else {
                $taskTime = $previousTime->diff($currLineTime);
            }

            $tasks[$logParts[self::BRANCH]]['items'][] = array(
                'date' => $logParts[self::DATE],
                'comment' => $logParts[self::COMMENT],
                'task_time' => $taskTime
            );

            $previousTime = $currLineTime;
        }

        return $this->_printFormat($tasks);
    }

    /**
     * @param $tasks
     * @return mixed
     */
    protected function _printFormat($tasks) {

        $formattedReport = self::DIVIDER . PHP_EOL
            . 'Hg Easy Report ' . self::version . PHP_EOL
            . '| ' . 'by Igor Malinovskiy' . PHP_EOL
            . '| ' .  'http://glide.name/hg-easy-report' . PHP_EOL
            . self::DIVIDER . PHP_EOL . PHP_EOL
            . 'Report:' . PHP_EOL . PHP_EOL;

        foreach ($tasks as $taskName => $taskLog) {
            $formattedReport .= $taskName . ':' . PHP_EOL;

            foreach ($taskLog['items'] as $taskLine) {
                $formattedReport .= $this->_formatTaskItem($taskLine);
            }

            $formattedReport .= PHP_EOL . PHP_EOL;
        }

        return $formattedReport;
    }

    /**
     * @param $taskItem
     * @return string
     */
    protected function _formatTaskItem($taskItem)
    {
        $taskLine = "\t" . ' - ' . $taskItem['comment'];

        if ($this->_isShowTimeInReport()) {
            $taskLine .= ' ('. $this->_formatDateInterval($taskItem['task_time']) . ')';
        }
        return $taskLine . PHP_EOL;
    }

    /**
     * @return bool
     */
    private function _isShowTimeInReport()
    {
        return strtolower($this->_timeSettings['show_time_in_task_list']) == 'on'
            || $this->_timeSettings['show_time_in_task_list'] == true;
    }

    /**
     * @param DateInterval $interval
     * @return string
     */
    protected function _formatDateInterval(DateInterval $interval)
    {
        return $interval->format('%r%ad %hh %im %ss');
    }
}
?>

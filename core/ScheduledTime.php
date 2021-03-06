<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik
 * @package Piwik
 */

namespace Piwik;

use Exception;
use Piwik\ScheduledTime\Daily;
use Piwik\ScheduledTime\Hourly;
use Piwik\ScheduledTime\Monthly;
use Piwik\ScheduledTime\Weekly;

/**
 * The ScheduledTime abstract class is used as a base class for different types of scheduling intervals.
 * ScheduledTime subclasses are used to schedule tasks within Piwik.
 *
 * @see \Piwik\ScheduledTask
 * @package Piwik
 * @subpackage ScheduledTime
 */
abstract class ScheduledTime
{
    const PERIOD_NEVER = 'never';
    const PERIOD_DAY = 'day';
    const PERIOD_WEEK = 'week';
    const PERIOD_MONTH = 'month';
    const PERIOD_YEAR = 'year';
    const PERIOD_RANGE = 'range';

    /**
     * @link http://php.net/manual/en/function.date.php, format string : 'G'
     * Defaults to midnight
     * @var integer
     */
    public $hour = 0;

    /**
     * For weekly scheduling : http://php.net/manual/en/function.date.php, format string : 'N', defaults to Monday
     * For monthly scheduling : day of the month (1 to 31) (note: will be capped at the latest day available the
     * month), defaults to first day of the month
     * @var integer
     */
    public $day = 1;

    /**
     * @param $period
     * @return Daily|Monthly|Weekly
     * @throws \Exception
     */
    static public function getScheduledTimeForPeriod($period)
    {
        switch ($period) {
            case self::PERIOD_MONTH:
                return new Monthly();
            case self::PERIOD_WEEK:
                return new Weekly();
            case self::PERIOD_DAY:
                return new Daily();

            default:
                throw new Exception('period ' . $period . 'is undefined.');
        }
    }

    /**
     * Returns the system time used by subclasses to compute schedulings.
     * This method has been introduced so unit tests can override the current system time.
     * @return int
     */
    protected function getTime()
    {
        return time();
    }

    /**
     * Computes the next scheduled time based on the system time at which the method has been called and
     * the underlying scheduling interval.
     *
     * @abstract
     * @return integer Returns the rescheduled time measured in the number of seconds since the Unix Epoch
     */
    abstract public function getRescheduledTime();

    /**
     * @abstract
     * @param  int $_day the day to set
     * @throws Exception if method not supported by subclass or parameter _day is invalid
     */
    abstract public function setDay($_day);

    /**
     * @param  int $_hour the hour to set, has to be >= 0 and < 24
     * @throws Exception if method not supported by subclass or parameter _hour is invalid
     */
    public function setHour($_hour)
    {
        if (!($_hour >= 0 && $_hour < 24)) {
            throw new Exception ("Invalid hour parameter, must be >=0 and < 24");
        }

        $this->hour = $_hour;
    }

    /**
     * Computes the delta in seconds needed to adjust the rescheduled time to the required hour.
     *
     * @param int $rescheduledTime The rescheduled time to be adjusted
     * @return int adjusted rescheduled time
     */
    protected function adjustHour($rescheduledTime)
    {
        if ($this->hour !== null) {
            // Reset the number of minutes and set the scheduled hour to the one specified with setHour()
            $rescheduledTime = mktime($this->hour,
                0,
                date('s', $rescheduledTime),
                date('n', $rescheduledTime),
                date('j', $rescheduledTime),
                date('Y', $rescheduledTime)
            );
        }
        return $rescheduledTime;
    }

    /**
     * Returns a new ScheduledTime instance using a string description of the scheduled period type
     * and a string description of the day within the period to execute the task on.
     * 
     * @param string $periodType The scheduled period type. Can be `'hourly'`, `'daily'`, `'weekly'`,
     *                           or `'monthly'`.
     * @param string|int|false $periodDay A string describing the day within the scheduled period to execute
     *                                    the task on. Only valid for week and month periods.
     *                               
     *                                    If `'weekly'` is supplied for `$periodType`, this should be a day
     *                                    of the week, for example, `'monday'` or `'tuesday'`.
     * 
     *                                    If `'monthly'` is supplied for `$periodType`, this can be a numeric
     *                                    day in the month or a day in one week of the month. For example,
     *                                    `12`, `23`, `'first sunday'` or `'fourth tuesday'`.
     */
    public static function factory($periodType, $periodDay = false)
    {
        switch ($periodType) {
            case 'hourly':
                return new Hourly();
            case 'daily':
                return new Daily();
            case 'weekly':
                $result = new Weekly();
                $result->setDay($periodDay);
                return $result;
            case 'monthly':
                $result = new Monthly($periodDay);
                if (is_int($periodDay)) {
                    $result->setDay($periodDay);
                } else {
                    $result->setDayOfWeekFromString($periodDay);
                }
                return $result;
            default:
                throw new Exception("Unsupported scheduled period type: '$periodType'. Supported values are"
                                  . " 'hourly', 'daily', 'weekly' or 'monthly'.");
        }
    }
}
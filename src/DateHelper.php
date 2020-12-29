<?php

namespace Technodelight\JiraRestApi;

use DateTime;
use DateTimeZone;

class DateHelper
{
    const FORMAT_FROM_JIRA = DateTime::ATOM;
    const FORMAT_TO_JIRA = 'Y-m-d\TH:i:s.000O';

    public static function dateTimeFromJira($dateString)
    {
        list(,$timeZone) = explode('+', $dateString, 2);
        $dateString = substr($dateString, 0, strpos($dateString, '.'))
            . substr($dateString, strpos($dateString, '+'));
        return DateTime::createFromFormat(self::FORMAT_FROM_JIRA, $dateString, new DateTimeZone('+' . $timeZone));
    }

    public static function dateTimeToJira($datetime)
    {
        $date = ($datetime instanceof DateTime) ? $datetime : new DateTime($datetime);
        if ($date->format('H:i:s') === '00:00:00') {
            $date->setTime(12, 0, 0);
        }
        return $date->format(self::FORMAT_TO_JIRA);
    }
}

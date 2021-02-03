<?php

namespace Technodelight\JiraRestApi;

use DateTime;
use DateTimeZone;

class DateHelper
{
    public const FORMAT_FROM_JIRA = DateTime::ATOM;
    public const FORMAT_TO_JIRA = 'Y-m-d\TH:i:s.000O';
    public const DATE_FIELDS = ['created', 'started', 'updated', 'createdAt', 'startedAt', 'updatedAt'];

    public static function dateTimeFromJira($dateString)
    {
        [,$timeZone] = explode('+', $dateString, 2);
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

    public static function normaliseDateFields(array $jiraItem): array
    {
        foreach (self::DATE_FIELDS as $field) {
            if (isset($jiraItem[$field])) {
                $jiraItem[$field] = self::dateTimeFromJira($jiraItem[$field])->format(self::FORMAT_FROM_JIRA);
            }
        }

        return $jiraItem;
    }
}

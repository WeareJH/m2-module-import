<?php

declare(strict_types=1);

namespace Jh\Import\Report\Handler\Email;

use Jh\Import\LogLevel;
use Jh\Import\Report\Message;
use Jh\Import\Report\ReportItem;

class Renderer
{
    /**
     * Translates log levels to html color priorities.
     */
    private static $logLevels = [
        LogLevel::DEBUG     => '#cccccc',
        LogLevel::INFO      => '#468847',
        LogLevel::NOTICE    => '#3a87ad',
        LogLevel::WARNING   => '#c09853',
        LogLevel::ERROR     => '#f0ad4e',
        LogLevel::CRITICAL  => '#FF7708',
        LogLevel::ALERT     => '#C12A19',
        LogLevel::EMERGENCY => '#000000',
    ];

    public static function title(string $title, $level = 1): string
    {
        $title = htmlspecialchars($title, ENT_NOQUOTES, 'UTF-8');
        return sprintf(
            '<h%d style="background: #000000;color: #ffffff;padding: 5px;" class="monolog-output">%s</h%d>',
            $level,
            $title,
            $level
        );
    }

    public static function itemLogEntry(ReportItem $reportItem, Message $message): string
    {
        $output  = self::logTitle($message->getLogLevel());
        $output .= '<table cellspacing="1" width="100%" class="monolog-output">';
        $output .= self::tableRow('Reference Line', $reportItem->getReferenceLine());
        $output .= self::tableRow('ID Field', $reportItem->getIdField());
        $output .= self::tableRow('ID Value', $reportItem->getIdValue());
        $output .= self::tableRow('Message', $message->getMessage());
        $output .= self::tableRow('Time', $message->getDateTime()->format('d-m-Y H:i:s'));

        return $output.'</table>';
    }

    public static function importLogEntry(Message $message): string
    {
        $output  = self::logTitle($message->getLogLevel());
        $output .= '<table cellspacing="1" width="100%" class="monolog-output">';
        $output .= self::tableRow('Message', $message->getMessage());
        $output .= self::tableRow('Time', $message->getDateTime()->format('d-m-Y H:i:s'));

        return $output.'</table>';
    }

    public static function logTitle(string $logLevel) : string
    {
        $title = htmlspecialchars($logLevel, ENT_NOQUOTES, 'UTF-8');
        return sprintf(
            '<h3 style="background: %s;color: #ffffff;padding: 5px;" class="monolog-output">%s</h3>',
            self::$logLevels[$logLevel],
            $title
        );
    }

    public static function tableRow(string $th, string $td): string
    {
        $th = htmlspecialchars($th, ENT_NOQUOTES, 'UTF-8');
        $td = htmlspecialchars($td, ENT_NOQUOTES, 'UTF-8');

        $format  = "<tr style=\"padding: 4px;spacing: 0;text-align: left;\">";
        $format .= "    <th style=\"background: #cccccc\"; width=\"150px\">%s:</th>";
        $format .= "    <td style=\"padding: 4px;spacing: 0;text-align:left;background: #eeeeee\">%s</td>";
        $format .= "</tr>";

        return sprintf($format, $th, $td);
    }
}

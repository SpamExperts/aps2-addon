<?php

/** 
 * Change the constant value to true to enable debug logging
 * 
 * WARNING: Debug logging means a lot more entries written into the log files
 *          These files can be really huge - up to tens of gigabytes in a day, depending on the server load
 *          Please only enable debug logging if this is requested by SpamExperts support staff
 */
define("APS_DEVELOPMENT_MODE", false);

class Logger extends Monolog\Logger
{
    public function __construct($name)
    {
        parent::__construct($name);

        $level = defined("APS_DEVELOPMENT_MODE") && APS_DEVELOPMENT_MODE
            ? Monolog\Logger::DEBUG
            : Monolog\Logger::INFO;
        $this->pushHandler(new Monolog\Handler\RotatingFileHandler(__DIR__ . "/logs/app-log", 30 /* days */, $level));
    }
}

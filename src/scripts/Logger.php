<?php

#/* <- Add a '#' before the slash (/) to enable development mode and advanced logging; remove it when done
define("APS_DEVELOPMENT_MODE", true); // Development & Testing Only
/**/

class Logger extends Monolog\Logger
{
    public function __construct($name)
    {
        parent::__construct($name);
        $level = defined("APS_DEVELOPMENT_MODE") ? Monolog\Logger::DEBUG : Monolog\Logger::INFO;
        $this->pushHandler(new Monolog\Handler\RotatingFileHandler("logs/app-log", 30 /* days */, $level));
    }
}

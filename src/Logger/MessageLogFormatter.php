<?php

namespace Hgabka\EmailBundle\Logger;

use Monolog\Formatter\LineFormatter;

class MessageLogFormatter extends LineFormatter
{
    public const SIMPLE_FORMAT = "[%datetime%] %message%\n";
}

<?php

namespace Hgabka\EmailBundle\Logger;

use Monolog\Formatter\LineFormatter;

class MessageLogFormatter extends LineFormatter
{
    const SIMPLE_FORMAT = "[%datetime%] %message%\n";
}

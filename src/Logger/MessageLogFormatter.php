<?php

namespace Hgabka\KunstmaanEmailBundle\Logger;

use Monolog\Formatter\LineFormatter;

class MessageLogFormatter extends LineFormatter
{
    const SIMPLE_FORMAT = "[%datetime%] %message%\n";
}

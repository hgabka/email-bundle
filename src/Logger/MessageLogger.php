<?php

namespace Hgabka\KunstmaanEmailBundle\Logger;

use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

class MessageLogger
{
    protected $logger;

    public function __construct(LoggerInterface $logger, $path)
    {
        $this->logger = $logger;
        $handler = new StreamHandler($path.'/'.date('Ymd').'.log');
        $handler->setFormatter(new MessageLogFormatter());
        $this->logger->setHandlers([$handler]);
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return MessageLogger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }
}

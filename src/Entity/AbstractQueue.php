<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Enum\QueueStatusEnum;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

class AbstractQueue
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="retries", type="integer")
     */
    protected $retries = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20)
     */
    protected $status = QueueStatusEnum::STATUS_INIT;

    /**
     * @return int
     */
    public function getRetries()
    {
        return $this->retries;
    }

    /**
     * @param int $retries
     *
     * @return AbstractQueue
     */
    public function setRetries($retries)
    {
        $this->retries = $retries;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return AbstractQueue
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }
}

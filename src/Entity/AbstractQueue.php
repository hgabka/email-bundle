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
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    /**
     * @var int
     *
     * @ORM\Column(name="retries", type="integer")
     */
    #[ORM\Column(name: 'retries')]
    protected ?int $retries = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20)
     */
    #[ORM\Column(name: 'status', type: 'string', length: 255)]
    protected ?string $status = QueueStatusEnum::STATUS_INIT;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return AbstractQueue
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getRetries(): ?int
    {
        return $this->retries;
    }

    /**
     * @param int $retries
     *
     * @return AbstractQueue
     */
    public function setRetries(?int $retries): self
    {
        $this->retries = $retries;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return AbstractQueue
     */
    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }
}

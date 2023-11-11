<?php

namespace Hgabka\EmailBundle\Event;

use Symfony\Component\Mime\Email;
use Symfony\Contracts\EventDispatcher\Event;
use Throwable;

class MailExceptionEvent extends Event
{
    private ?Throwable $exception = null;

    private ?string $class = null;

    private ?array $params = [];

    private ?array $sendParams = [];

    private ?Email $email = null;

    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    public function setException(?Throwable $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * @param null|string $class
     *
     * @return MailExceptionEvent
     */
    public function setClass(?string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @return null|array
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    public function setParams(?array $params): self
    {
        $this->params = $params;

        return $this;
    }

    public function getSendParams(): ?array
    {
        return $this->sendParams;
    }

    public function setSendParams(?array $sendParams): self
    {
        $this->sendParams = $sendParams;

        return $this;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function setEmail(?Email $email): self
    {
        $this->email = $email;

        return $this;
    }
}

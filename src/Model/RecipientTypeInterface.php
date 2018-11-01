<?php

namespace Hgabka\EmailBundle\Model;

use Hgabka\EmailBundle\Helper\RecipientManager;
use Symfony\Component\Form\FormBuilderInterface;

interface RecipientTypeInterface
{
    /**
     * @return mixed
     */
    public function getName();

    /**
     * @param FormBuilderInterface $formBuilder
     *
     * @return mixed
     */
    public function addFormFields(FormBuilderInterface $formBuilder);

    /**
     * @return mixed
     */
    public function getParams();

    /**
     * @return mixed
     */
    public function getStaticParams();

    /**
     * @param mixed $name
     *
     * @return mixed
     */
    public function getStaticParam($name);

    /**
     * @param mixed $staticParams
     *
     * @return mixed
     */
    public function setStaticParams($staticParams);

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function setParams(array $params);

    /**
     * @return mixed
     */
    public function getRecipients();

    /**
     * @return mixed
     */
    public function getTitle();

    /**
     * @return mixed
     */
    public function getRecipientCount();

    /**
     * @param RecipientManager $manager
     *
     * @return mixed
     */
    public function setManager(RecipientManager $manager);

    /**
     * @return mixed
     */
    public function isPublic();

    /**
     * @return mixed
     */
    public function getPriority();

    public function getParamDefaults();

    public function getFormTemplate();

    public function setPriority($priority);
}

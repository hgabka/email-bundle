<?php

namespace Hgabka\EmailBundle\Helper;

use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Model\EmailTemplateTypeInterface;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\TranslatorInterface;

class TemplateTypeManager
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /** @var array|EmailTemplateTypeInterface[] */
    protected $templateTypes = [];

    /**
     * TemplateTypeManager constructor.
     */
    public function __construct(ManagerRegistry $doctrine, TranslatorInterface $translator, HgabkaUtils $hgabkaUtils)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->hgabkaUtils = $hgabkaUtils;
    }

    public function addTemplateType(EmailTemplateTypeInterface $templateType, $priority = null)
    {
        $alias = \get_class($templateType);
        if (null !== $templateType->getPriority()) {
            $templateType->setPriority($priority);
        }

        $this->templateTypes[$alias] = $templateType;
        uasort($this->templateTypes, function ($type1, $type2) {
            $p1 = (null === $type1->getPriority() ? 0 : $type1->getPriority());
            $p2 = (null === $type2->getPriority() ? 0 : $type2->getPriority());

            return $p2 <=> $p1;
        });
    }

    public function getTemplateType($class)
    {
        return $this->templateTypes[$class] ?? null;
    }

    public function getTemplateTypeEntities($onlyPublic = false)
    {
        $res = [];
        foreach ($this->templateTypes as $class => $type) {
            $res[] = $this->getTemplateEntity($type);
        }

        return $res;
    }

    /**
     * @param mixed $onlyPublic
     *
     * @return array|EmailTemplateTypeInterface[]
     */
    public function getTemplateTypes($onlyPublic = false)
    {
        if (!$onlyPublic) {
            return $this->templateTypes;
        }

        $res = [];
        foreach ($this->templateTypes as $key => $type) {
            if ($type->isPublic()) {
                $res[$key] = $type;
            }
        }

        return $res;
    }

    /**
     * @param mixed $onlyPublic
     *
     * @return array
     */
    public function getTemplateTypeClasses($onlyPublic = false)
    {
        return array_keys($this->getTemplateTypes($onlyPublic));
    }

    public function getTitleByType($class)
    {
        $type = $this->getTemplateType($class);

        return $type ? $type->getTitle() : '';
    }

    public function getTemplateEntity(EmailTemplateTypeInterface $templateType)
    {
        if (empty($templateType->getEntity())) {
            $template = $this->doctrine->getRepository(EmailTemplate::class)->findOneBy(['type' => \get_class($templateType)]);
            if (!$template) {
                $template = new EmailTemplate();
                $template
                    ->setType(\get_class($templateType));

                foreach ($this->hgabkaUtils->getAvailableLocales() as $locale) {
                    $template->translate($locale)
                             ->setComment($this->translator->trans($templateType->getComment(), [], 'messages', $locale))
                             ->setSubject($this->translator->trans($templateType->getDefaultSubject(), [], 'messages', $locale))
                             ->setFromName($this->translator->trans($templateType->getDefaultFromName(), [], 'messages', $locale))
                             ->setFromEmail($this->translator->trans($templateType->getDefaultFromEmail(), [], 'messages', $locale))
                             ->setContentText($this->translator->trans($templateType->getDefaultTextContent(), [], 'messages', $locale))
                             ->setContentHtml($this->translator->trans($templateType->getDefaultHtmlContent(), [], 'messages', $locale))
                    ;
                }
                $template->setCurrentLocale($this->hgabkaUtils->getCurrentLocale());
                $this->doctrine->getManager()->persist($template);
                $this->doctrine->getManager()->flush();
            }

            $templateType->setEntity($template);
        }

        return $templateType->getEntity();
    }
}

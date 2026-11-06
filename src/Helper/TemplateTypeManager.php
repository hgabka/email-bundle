<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Model\EmailTemplateTypeInterface;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class TemplateTypeManager
{
    /** @var Registry */
    protected $doctrine;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /** @var array|EmailTemplateTypeInterface[] */
    protected $templateTypes = [];

    /** @var iterable<EmailTemplateTypeInterface>|null */
    private ?iterable $pendingTypes;

    /**
     * @param iterable<EmailTemplateTypeInterface> $templateTypes tagged (hg_email.email_template_type) services, ordered by tag priority
     */
    public function __construct(Registry $doctrine, TranslatorInterface $translator, HgabkaUtils $hgabkaUtils, iterable $templateTypes = [])
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->hgabkaUtils = $hgabkaUtils;
        $this->pendingTypes = $templateTypes;
    }

    public function addTemplateType(EmailTemplateTypeInterface $templateType, $priority = null)
    {
        $this->load();
        $this->register($templateType, $priority);
        $this->sort();
    }

    public function getTemplateType($class)
    {
        $this->load();

        return $this->templateTypes[$class] ?? null;
    }

    public function getTemplateTypeEntities($onlyPublic = false)
    {
        $this->load();
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
        $this->load();

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

    private function load(): void
    {
        if (null === $this->pendingTypes) {
            return;
        }

        $pending = $this->pendingTypes;
        $this->pendingTypes = null;

        foreach ($pending as $type) {
            $this->register($type);
        }
        $this->sort();
    }

    private function register(EmailTemplateTypeInterface $templateType, $priority = null): void
    {
        if (null === $templateType->getPriority() && null !== $priority) {
            $templateType->setPriority($priority);
        }

        $this->templateTypes[\get_class($templateType)] = $templateType;
    }

    private function sort(): void
    {
        // uasort is stable (PHP 8), so equal priorities keep the tag priority order
        uasort($this->templateTypes, static function ($type1, $type2) {
            return ($type2->getPriority() ?? 0) <=> ($type1->getPriority() ?? 0);
        });
    }
}

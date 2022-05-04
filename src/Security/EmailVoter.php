<?php

namespace Hgabka\EmailBundle\Security;

use Hgabka\EmailBundle\Entity\EmailLayout;
use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\EmailBundle\Helper\TemplateTypeManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EmailVoter extends Voter
{
    public const EDIT = 'edit';

    /** @var string */
    protected $editorRole;

    /** @var AccessDecisionManagerInterface */
    protected $decisionManager;

    /** @var TemplateTypeManager */
    protected $templateTypeManager;

    /**
     * BannerVoter constructor.
     *
     * @param string $editorRole
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager, TemplateTypeManager $templateTypeManager, $editorRole)
    {
        $this->decisionManager = $decisionManager;
        $this->templateTypeManager = $templateTypeManager;
        $this->editorRole = $editorRole;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, [self::EDIT], true)) {
            return false;
        }

        if (!$subject instanceof EmailLayout && !$subject instanceof EmailTemplate && !$subject instanceof Message && !$subject instanceof MessageSubscriber) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        switch ($attribute) {
            case self::EDIT:
                if ($subject instanceof EmailTemplate) {
                    $type = $this->templateTypeManager->getTemplateType($object->getType());
                    if ($type && !$type->isPublic()) {
                        return false;
                    }
                }

                return $this->canEdit($subject, $token);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canEdit(mixed $subject, TokenInterface $token): bool
    {
        return $this->decisionManager->decide($token, [$this->editorRole]);
    }
}

<?php

namespace Hgabka\EmailBundle\Security;

use Hgabka\EmailBundle\Entity\EmailLayout;
use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EmailVoter extends Voter
{
    const EDIT = 'edit';

    /** @var string */
    protected $editorRole;

    /** @var AccessDecisionManagerInterface */
    protected $decisionManager;

    /**
     * BannerVoter constructor.
     *
     * @param string $editorRole
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager, $editorRole)
    {
        $this->decisionManager = $decisionManager;
        $this->editorRole = $editorRole;
    }

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::EDIT], true)) {
            return false;
        }

        if (!$subject instanceof EmailLayout && !$subject instanceof EmailTemplate && !$subject instanceof Message && !$subject instanceof MessageSubscriber) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($subject, $token);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canEdit($subject, TokenInterface $token)
    {
        return $this->decisionManager->decide($token, [$this->editorRole]);
    }
}

<?php

namespace Hgabka\EmailBundle\Helper;

use Hgabka\EmailBundle\Entity\EmailLayout;
use Hgabka\EmailBundle\Model\LayoutVarInterface;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\Config\FileLocator;

class LayoutManager
{
    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /** @var ParamSubstituter */
    protected $paramSubstituter;

    /** @var string */
    protected $layoutFile;

    /** @var LayoutVarInterface[] */
    protected $layoutVars = [];

    /**
     * LayoutManager constructor.
     *
     * @param string $layoutFile
     */
    public function __construct(HgabkaUtils $hgabkaUtils, ParamSubstituter $paramSubstituter, $layoutFile)
    {
        $this->hgabkaUtils = $hgabkaUtils;
        $this->paramSubstituter = $paramSubstituter;
        $this->layoutFile = $layoutFile;
    }

    public function addLayoutVar(LayoutVarInterface $layoutVar, $priority = null)
    {
        $alias = \get_class($layoutVar);

        if (null !== $priority) {
            $layoutVar->setPriority($priority);
        }

        $this->layoutVars[$alias] = $layoutVar;
        uasort($this->layoutVars, function ($type1, $type2) {
            $p1 = (null === $type1->getPriority() ? 0 : $type1->getPriority());
            $p2 = (null === $type2->getPriority() ? 0 : $type2->getPriority());

            return $p2 <=> $p1;
        });
    }

    /**
     * @return array|string
     */
    public function getDefaultLayoutPath()
    {
        $locator = new FileLocator(__DIR__ . '/../Resources/layout');

        return $locator->locate('layout.html');
    }

    public function applyLayout($bodyHtml, EmailLayout $layout = null, $mail, $locale, $params = [], $layoutFile = null)
    {
        if ($layout && '' !== $bodyHtml) {
            $layoutHtml = $layout->translate($locale)->getContentHtml();
        } elseif ('' !== $bodyHtml && (false !== $this->layoutFile || !empty($layoutFile))) {
            $layoutFile = !empty($layoutFile) || (isset($layoutFile) && false === $layoutFile) ? $layoutFile : $this->layoutFile;

            if (false !== $layoutFile && !is_file($layoutFile)) {
                $layoutFile = $this->getDefaultLayoutPath();
            }

            if (!empty($layoutFile)) {
                $layoutFile = strtr($layoutFile, ['%locale%' => $locale]);
                $layoutHtml = @file_get_contents($layoutFile);
            } else {
                $layoutHtml = null;
            }
        }
        if (!empty($layoutHtml)) {
            $bodyHtml = $this->finalizeLayout($layoutHtml, $bodyHtml, $mail, $params, $locale);
        }

        return $bodyHtml;
    }

    public function getVariables()
    {
        $result = [];
        foreach ($this->layoutVars as $layoutVar) {
            $result[$layoutVar->getPlaceholder()] = $layoutVar->getLabel();
        }

        return $result;
    }

    /**
     * @param $layout
     * @param $subject
     * @param $bodyHtml
     * @param $name
     * @param $email
     * @param mixed $layoutHtml
     * @param mixed $params
     * @param mixed $locale
     * @param mixed $mail
     *
     * @return string
     */
    protected function finalizeLayout($layoutHtml, $bodyHtml, $mail, $params, $locale)
    {
        foreach ($this->layoutVars as $class => $layoutVar) {
            $params[$layoutVar->getPlaceholder()] = $layoutVar->getValue($layoutHtml, $bodyHtml, $mail, $params, $locale);
        }

        $params = array_merge($params, [
            'host' => $this->hgabkaUtils->getSchemeAndHttpHost(),
            'styles' => '',
            'title' => $params['subject'],
            'content' => $bodyHtml,
        ]);

        return $this->paramSubstituter->substituteParams($layoutHtml, $params);
    }
}

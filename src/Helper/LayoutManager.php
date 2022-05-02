<?php

namespace Hgabka\EmailBundle\Helper;

use Hgabka\EmailBundle\Entity\EmailLayout;
use Hgabka\EmailBundle\Model\LayoutVarInterface;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\Config\FileLocator;
use Throwable;
use Twig\Environment;

class LayoutManager
{
    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /** @var ParamSubstituter */
    protected $paramSubstituter;

    /** @var Environment */
    protected $environment;

    /** @var string */
    protected $layoutFile;

    /** @var LayoutVarInterface[] */
    protected $layoutVars = [];

    /**
     * LayoutManager constructor.
     *
     * @param string $layoutFile
     */
    public function __construct(HgabkaUtils $hgabkaUtils, ParamSubstituter $paramSubstituter, Environment $environment, $layoutFile)
    {
        $this->hgabkaUtils = $hgabkaUtils;
        $this->paramSubstituter = $paramSubstituter;
        $this->layoutFile = $layoutFile;
        $this->environment = $environment;
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

    public function applyLayout($bodyHtml, ?EmailLayout $layout, $mail, $locale, $params = [], $layoutFile = null, $webversion = false)
    {
        $isTwig = false;
        if ($layout && '' !== $bodyHtml) {
            $layoutHtml = $layout->translate($locale)->getContentHtml();
        } elseif ('' !== $bodyHtml && (false !== $this->layoutFile || !empty($layoutFile))) {
            $layoutFile = !empty($layoutFile) || (isset($layoutFile) && false === $layoutFile) ? $layoutFile : $this->layoutFile;
            if (!empty($layoutFile)) {
                $layoutFile = strtr($layoutFile, ['%locale%' => $locale]);
                $ext = pathinfo($layoutFile, \PATHINFO_EXTENSION);
                if ('twig' === $ext) {
                    $isTwig = true;

                    try {
                        $layoutHtml = $this->environment->render($layoutFile, $this->getLayoutParams('', $bodyHtml, $mail, $params, $locale, $webversion));
                    } catch (Throwable $e) {
                        $layoutHtml = null;
                    }
                } else {
                    if (!is_file($layoutFile)) {
                        $layoutFile = $this->getDefaultLayoutPath();
                    }
                    $layoutHtml = @file_get_contents($layoutFile);
                }
            } else {
                $layoutHtml = null;
            }
        }

        if (!empty($layoutHtml)) {
            $bodyHtml = !$isTwig ? $this->finalizeLayout($layoutHtml, $bodyHtml, $mail, $params, $locale, $webversion) : $layoutHtml;
        }

        return $bodyHtml;
    }

    public function getVariables(): array
    {
        $result = [];
        foreach ($this->layoutVars as $layoutVar) {
            $result[$layoutVar->getPlaceholder()] = $layoutVar->getLabel();
        }

        return $result;
    }

    public function getLayoutParams($layoutHtml, $bodyHtml, $mail, $params, $locale, $webversion): array
    {
        foreach ($this->layoutVars as $layoutVar) {
            $params[$layoutVar->getPlaceholder()] = $layoutVar->getValue($layoutHtml, $bodyHtml, $mail, $params, $locale, $webversion);
        }

        return array_merge($params, [
            'host' => $this->hgabkaUtils->getSchemeAndHttpHost(),
            'styles' => '',
            'title' => $params['subject'],
            'content' => $bodyHtml,
        ]);
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
     * @param mixed $webversion
     *
     * @return string
     */
    protected function finalizeLayout($layoutHtml, $bodyHtml, $mail, $params, $locale, $webversion): string
    {
        return $this->paramSubstituter->substituteParams($layoutHtml, $this->getLayoutParams($layoutHtml, $bodyHtml, $mail, $params, $locale, $webversion));
    }
}

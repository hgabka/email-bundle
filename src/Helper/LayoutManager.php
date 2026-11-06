<?php

namespace Hgabka\EmailBundle\Helper;

use Hgabka\EmailBundle\Entity\EmailLayout;
use Hgabka\EmailBundle\Model\LayoutVarInterface;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Mime\Email;
use Throwable;
use Twig\Environment;

class LayoutManager
{
    /** @var LayoutVarInterface[] */
    protected array $layoutVars = [];

    /** @var iterable<LayoutVarInterface>|null tagged (hg_email.layout_var) services, ordered by tag priority */
    private ?iterable $pendingLayoutVars;

    public function __construct(
        protected readonly HgabkaUtils $hgabkaUtils,
        protected readonly ParamSubstituter $paramSubstituter,
        protected readonly Environment $environment,
        protected readonly ?string $layoutFile,
        iterable $layoutVars = [],
    ) {
        $this->pendingLayoutVars = $layoutVars;
    }

    public function addLayoutVar(LayoutVarInterface $layoutVar, ?int $priority = null)
    {
        $this->loadLayoutVars();
        $alias = $layoutVar::class;

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

    private function loadLayoutVars(): void
    {
        if (null === $this->pendingLayoutVars) {
            return;
        }

        $pending = $this->pendingLayoutVars;
        $this->pendingLayoutVars = null;

        // the iterator already yields the services in tag priority order
        $loaded = [];
        foreach ($pending as $layoutVar) {
            $loaded[$layoutVar::class] = $layoutVar;
        }
        $this->layoutVars = $loaded + $this->layoutVars;
    }

    public function getDefaultLayoutPath(): array|string
    {
        $locator = new FileLocator(__DIR__ . '/../Resources/layout');

        return $locator->locate('layout.html');
    }

    public function applyLayout(
        ?string $bodyHtml,
        ?EmailLayout $layout,
        ?Email $mail,
        ?string $locale,
        ?array $params = [],
        ?string $layoutFile = null,
        bool $webversion = false,
    ) {
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
        $this->loadLayoutVars();
        $result = [];
        foreach ($this->layoutVars as $layoutVar) {
            $result[$layoutVar->getPlaceholder()] = $layoutVar->getLabel();
        }

        return $result;
    }

    public function getLayoutParams(
        ?string $layoutHtml,
        ?string $bodyHtml,
        ?Email $mail,
        ?array $params,
        ?string $locale,
        ?bool $webversion,
    ): array {
        $this->loadLayoutVars();
        foreach ($this->layoutVars as $layoutVar) {
            if ($layoutVar->isEnabled($layoutHtml, $bodyHtml, $mail, $params, $locale, $webversion)) {
                $params[$layoutVar->getPlaceholder()] = $layoutVar->getValue($layoutHtml, $bodyHtml, $mail, $params, $locale, $webversion);
            }
        }

        return array_merge($params, [
            'host' => $this->hgabkaUtils->getSchemeAndHttpHost(),
            'styles' => '',
            'title' => $params['subject'],
            'content' => $bodyHtml,
        ]);
    }

    protected function finalizeLayout(
        ?string $layoutHtml,
        ?string $bodyHtml,
        ?Email $mail,
        ?array $params,
        ?string $locale,
        ?bool $webversion,
    ): string {
        return $this->paramSubstituter->substituteParams($layoutHtml, $this->getLayoutParams($layoutHtml, $bodyHtml, $mail, $params, $locale, $webversion));
    }
}

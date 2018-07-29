<?php

namespace Hgabka\KunstmaanEmailBundle\Helper;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\RequestStack;

class ParamSubstituter
{
    /** @var RequestStack */
    protected $requestStack;

    protected $varChars;

    /** @var string */
    protected $cacheDir;

    /** @var string */
    protected $projectDir;

    public function __construct(RequestStack $requestStack, string $cacheDir, string $projectDir, $varChars)
    {
        $this->requestStack = $requestStack;
        $this->cacheDir = $cacheDir;
        $this->projectDir = $projectDir;
        $this->varChars = $varChars;
    }

    /**
     * Szöveg paraméterek behelyettesítése.
     *
     * @param string $text
     * @param array  $params
     * @param bool   $normalized
     *
     * @return string
     */
    public function substituteParams($text, $params, $normalized = false)
    {
        $params = $normalized ? $params : $this->normalizeParams($params);

        $params = $this->addVarChars($params);

        foreach ($params as $key => $param) {
            if (is_string($param)) {
                $text = strtr($text, [$key => $param]);
            } else {
                if (!isset($param['value']) || !is_string($param['value'])) {
                    continue;
                }

                if (!isset($param['type']) || 'block' !== $param['type']) {
                    $text = str_replace($key, $param['value'], $text);
                } else {
                    $value = $param['value'];
                    $pattern = '/<p*>(.*)'.preg_quote($key, '/').'(.*)<\/p>/i';
                    $text = preg_replace($pattern, $value, $text);
                }
            }
        }

        return $text;
    }

    /**
     * A HTML content-ben lévő relatív image url-ekből abszolútot csinál.
     *
     * @param string $html
     *
     * @return mixed
     */
    public function setAbsoluteImageUrls($html)
    {
        $pattern = '/(<img[^>]+src=["\'])([^"\':]+)(["\'])/ie';

        $html = preg_replace_callback($pattern, function ($matches) {
            return $matches[1].$this->addHost(trim($matches[2], " '\"")).$matches[3];
        }, $html);

        $pattern = '/(<input[^>]+src=["\'])([^"\':]+)(["\'])/ie';

        return preg_replace_callback($pattern, function ($matches) {
            return $matches[1].$this->addHost(trim($matches[2], " '\"")).$matches[3];
        }, $html);
    }

    /**
     *  Beágyazza a képeket és az src-t a cid-re cseréli.
     *
     * @param mixed $html
     * @param mixed $email
     */
    public function embedImages($html, $email)
    {
        $pattern = '/(<img[^>]+src=["\'])([^"\']+)(["\'])(.*)/i';

        $html = preg_replace_callback($pattern, function ($matches) use ($email) {
            return $matches[1].$this->embedImage($matches[2], $email).$matches[3].$matches[4];
        }, $html);

        $pattern = '/(url\s*\()([^\)]+)/i';
        $html = preg_replace_callback($pattern, function ($matches) {
            return $matches[1].$this->addHost(trim($matches[2], " '\""));
        }, $html);

        return $html;
    }

    public function getVarChars()
    {
        $varChars = $this->varChars;
        if (empty($varChars)) {
            return ['prefix' => '%%', 'postfix' => '%%'];
        }
        if (is_string($varChars)) {
            return ['prefix' => $varChars, 'postfix' => $varChars];
        }

        return ['prefix' => isset($varChars['prefix']) ? $varChars['prefix'] : '', 'postfix' => isset($varChars['postfix']) ? $varChars['postfix'] : ''];
    }

    public function normalizeParams($params)
    {
        $normalized = [];

        foreach ($this->removeVarChars($params) as $key => $value) {
            if (is_object($value)) {
                foreach (get_object_vars($value) as $field => $val) {
                    if (!isset($normalized[$key.'.'.strtolower($field)])) {
                        $normalized[$key.'.'.strtolower($field)] = (string) $val;
                    }
                }
                foreach (get_class_methods($value) as $field) {
                    if (!isset($normalized[$key.'.'.$field]) && method_exists($value, $field)) {
                        $normalized[$key.'.'.$field] = (string) $value->$field();
                    }
                }
            }
            $normalized[$key] = empty($value) ? '' : $value;
        }

        return $normalized;
    }

    public function removeVarChars($data)
    {
        if (!is_array($data)) {
            return $this->removeVarCharsFromString($data);
        }
        if (empty($data)) {
            return $data;
        }

        $res = [];
        foreach ($data as $key => $value) {
            $res[$this->removeVarCharsFromString($key)] = $value;
        }

        return $res;
    }

    public function addVarChars($data)
    {
        if (!is_array($data)) {
            return $this->addVarCharsToString($data);
        }
        if (empty($data)) {
            return $data;
        }

        $res = [];
        foreach ($data as $key => $value) {
            $res[$this->addVarCharsToString($key)] = $value;
        }

        return $res;
    }

    /**
     * @return array|string
     */
    public function getDefaultLayoutPath()
    {
        $locator = new FileLocator(__DIR__.'/../Resources/layout');

        return $locator->locate('layout.html');
    }

    /**
     * Embeddel egy képet és visszaadja a cid-et.
     *
     * @param string $url
     * @param mixed  $email
     *
     * @return string
     */
    protected function embedImage($url, $email)
    {
        if (0 !== strpos($url, 'http://') && 0 !== strpos($url, 'https://')) {
            $file = $this->projectDir.'/web/'.$url;
            if (!is_file($file)) {
                return $url;
            }
        } else {
            $content = @file_get_contents($url);
            if (false === $content) {
                return $url;
            }
            $p = pathinfo($url);

            $dir = $this->cacheDir.'/mailimages/'.$email->getHeaders('Message-ID');
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $file = $dir.'/'.$p['basename'];
            file_put_contents($file, $content);
        }

        $img = \Swift_Image::fromPath($file);

        return $email->embed($img);
    }

    /**
     * Abszolút url-t generál a relatívból.
     *
     * @param string $url
     *
     * @return string
     */
    protected function addHost($url)
    {
        if (0 === strpos('http://', $url) || 0 === strpos('https://', $url)) {
            return $url;
        }

        $file = $this->requestStack->getCurrentRequest()->getBasePath().'/'.$url;
        if (!is_file($file)) {
            return $url;
        }

        return $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost().$url;
    }

    protected function removeVarCharsFromString($string)
    {
        if (!is_string($string)) {
            return $string;
        }
        $varChars = $this->getVarChars();
        if (!empty($varChars['prefix'])) {
            $len = mb_strlen($varChars['prefix'], 'utf-8');
            $first = mb_substr($string, 0, $len, 'utf-8');
            if ($first === $varChars['prefix']) {
                $string = mb_substr($string, $len, null, 'utf-8');
            }
        }
        if (!empty($varChars['postfix'])) {
            $len = mb_strlen($varChars['postfix'], 'utf-8');
            $last = mb_substr($string, -($len), $len, 'utf-8');
            if ($last === $varChars['postfix']) {
                $string = mb_substr($string, 0, mb_strlen($string, 'utf-8') - $len, 'utf-8');
            }
        }

        return $string;
    }

    protected function addVarCharsToString($string)
    {
        if (!is_string($string)) {
            return $string;
        }
        $string = $this->removeVarCharsFromString($string);
        $varChars = $this->getVarChars();

        if (!empty($varChars['prefix'])) {
            $string = $varChars['prefix'].$string;
        }
        if (!empty($varChars['postfix'])) {
            $string = $string.$varChars['postfix'];
        }

        return $string;
    }
}

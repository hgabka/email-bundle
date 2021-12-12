<?php

namespace Hgabka\EmailBundle\Helper;

class EmailParser
{
    public const PLAINTEXT = 1;
    public const HTML = 2;

    /**
     * @var associative array
     */
    protected $rawFields;

    /**
     * @var array of string (each element is a line)
     */
    protected $rawBodyLines;

    /**
     * @var bool
     */
    private $isImapExtensionAvailable = false;

    /**
     * @var string
     */
    private $emailRawContent;

    /**
     * @param string $emailRawContent
     */
    public function __construct($emailRawContent)
    {
        $this->emailRawContent = $emailRawContent;

        $this->extractHeadersAndRawBody();

        if (\function_exists('imap_open')) {
            $this->isImapExtensionAvailable = true;
        }
    }

    /**
     * @throws Exception if a subject header is not found
     *
     * @return string (in UTF-8 format)
     */
    public function getSubject()
    {
        if (!isset($this->rawFields['subject'])) {
            throw new Exception("Couldn't find the subject of the email");
        }

        $ret = '';

        if ($this->isImapExtensionAvailable) {
            foreach (imap_mime_header_decode($this->rawFields['subject']) as $h) { // subject can span into several lines
                $charset = ('default' === $h->charset) ? 'US-ASCII' : $h->charset;
                $ret .= iconv($charset, 'UTF-8//TRANSLIT', $h->text);
            }
        } else {
            $ret = utf8_encode(iconv_mime_decode($this->rawFields['subject']));
        }

        return $ret;
    }

    /**
     * @return array
     */
    public function getCc()
    {
        if (!isset($this->rawFields['cc'])) {
            return [];
        }

        return explode(',', $this->rawFields['cc']);
    }

    /**
     * @throws Exception if a to header is not found or if there are no recipient
     *
     * @return array
     */
    public function getTo()
    {
        if ((!isset($this->rawFields['to'])) || (!\count($this->rawFields['to']))) {
            throw new Exception("Couldn't find the recipients of the email");
        }

        return explode(',', $this->rawFields['to']);
    }

    /**
     * return string - UTF8 encoded.
     *
     * Example of an email body
     *
     * --0016e65b5ec22721580487cb20fd
     * Content-Type: text/plain; charset=ISO-8859-1
     *
     * Hi all. I am new to Android development.
     * Please help me.
     *
     * --
     * My signature
     *
     * email: myemail@gmail.com
     * web: http://www.example.com
     *
     * --0016e65b5ec22721580487cb20fd
     * Content-Type: text/html; charset=ISO-8859-1
     *
     * @param mixed $returnType
     */
    public function getBody($returnType = self::PLAINTEXT)
    {
        $body = '';
        $detectedContentType = false;
        $contentTransferEncoding = null;
        $charset = 'ASCII';
        $waitingForContentStart = true;

        if (self::HTML === $returnType) {
            $contentTypeRegex = '/^Content-Type: ?text\/html/i';
        } else {
            $contentTypeRegex = '/^Content-Type: ?text\/plain/i';
        }

        // there could be more than one boundary
        preg_match_all('!boundary=(.*)$!mi', $this->emailRawContent, $matches);
        $boundaries = $matches[1];
        // sometimes boundaries are delimited by quotes - we want to remove them
        foreach ($boundaries as $i => $v) {
            $boundaries[$i] = str_replace(["'", '"'], '', $v);
        }

        foreach ($this->rawBodyLines as $line) {
            if (!$detectedContentType) {
                if (preg_match($contentTypeRegex, $line, $matches)) {
                    $detectedContentType = true;
                }

                if (preg_match('/charset=(.*)/i', $line, $matches)) {
                    $charset = strtoupper(trim($matches[1], '"'));
                }
            } elseif ($detectedContentType && $waitingForContentStart) {
                if (preg_match('/charset=(.*)/i', $line, $matches)) {
                    $charset = strtoupper(trim($matches[1], '"'));
                }

                if (null === $contentTransferEncoding && preg_match('/^Content-Transfer-Encoding: ?(.*)/i', $line, $matches)) {
                    $contentTransferEncoding = $matches[1];
                }

                if (self::isNewLine($line)) {
                    $waitingForContentStart = false;
                }
            } else {  // ($detectedContentType && !$waitingForContentStart)
                // collecting the actual content until we find the delimiter

                // if the delimited is AAAAA, the line will be --AAAAA  - that's why we use substr
                if (\is_array($boundaries)) {
                    if (\in_array(substr($line, 2), $boundaries, true)) {  // found the delimiter
                        break;
                    }
                }
                $body .= $line . "\n";
            }
        }

        if (!$detectedContentType) {
            // if here, we missed the text/plain content-type (probably it was
            // in the header), thus we assume the whole body is what we are after
            $body = implode("\n", $this->rawBodyLines);
        }

        // removing trailing new lines
        $body = preg_replace('/((\r?\n)*)$/', '', $body);

        if ('base64' === $contentTransferEncoding) {
            $body = base64_decode($body, true);
        } elseif ('quoted-printable' === $contentTransferEncoding) {
            $body = quoted_printable_decode($body);
        }

        if ('UTF-8' !== $charset) {
            // FORMAT=FLOWED, despite being popular in emails, it is not
            // supported by iconv
            $charset = str_replace('FORMAT=FLOWED', '', $charset);

            $bodyCopy = $body;
            $body = iconv($charset, 'UTF-8//TRANSLIT', $body);

            if (false === $body) { // iconv returns FALSE on failure
                $body = utf8_encode($bodyCopy);
            }
        }

        return $body;
    }

    /**
     * @return string - UTF8 encoded
     */
    public function getPlainBody()
    {
        return $this->getBody(self::PLAINTEXT);
    }

    /**
     * return string - UTF8 encoded.
     */
    public function getHTMLBody()
    {
        return $this->getBody(self::HTML);
    }

    /**
     * N.B.: if the header doesn't exist an empty string is returned.
     *
     * @param string $headerName - the header we want to retrieve
     *
     * @return string - the value of the header
     */
    public function getHeader($headerName)
    {
        $headerName = strtolower($headerName);

        if (isset($this->rawFields[$headerName])) {
            return $this->rawFields[$headerName];
        }

        return '';
    }

    /**
     * @param string $line
     *
     * @return bool
     */
    public static function isNewLine($line)
    {
        $line = str_replace("\r", '', $line);
        $line = str_replace("\n", '', $line);

        return '' === $line;
    }

    private function extractHeadersAndRawBody()
    {
        $lines = preg_split("/(\r?\n|\r)/", $this->emailRawContent);

        $currentHeader = '';

        $i = 0;
        foreach ($lines as $line) {
            if (self::isNewLine($line)) {
                // end of headers
                $this->rawBodyLines = \array_slice($lines, $i);

                break;
            }

            if ($this->isLineStartingWithPrintableChar($line)) { // start of new header
                preg_match('/([^:]+): ?(.*)$/', $line, $matches);
                $newHeader = isset($matches[1]) ? strtolower($matches[1]) : '';
                $value = $matches[2] ?? '';
                $this->rawFields[$newHeader] = $value;
                $currentHeader = $newHeader;
            } else { // more lines related to the current header
                if ($currentHeader) { // to prevent notice from empty lines
                    $this->rawFields[$currentHeader] .= substr($line, 1);
                }
            }
            ++$i;
        }
    }

    /**
     * @param string $line
     *
     * @return bool
     */
    private function isLineStartingWithPrintableChar($line)
    {
        return preg_match('/^[A-Za-z]/', $line);
    }
}

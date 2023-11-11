<?php

namespace Hgabka\EmailBundle\Helper;

class MailboxReader
{
    protected $handle;

    /** @var EmailParser */
    protected $parser;

    public function __construct(EmailParser $parser, $host, $port, $user, $pass, $folder = 'INBOX', $type = 'imap', $ssl = false)
    {
        $ssl = false === $ssl ? '/novalidate-cert' : '/ssl';

        $connStr = '{' . "$host:$port/$type$ssl" . "}$folder";
        $this->handle = @imap_open($connStr, $user, $pass);

        if (false === $this->handle) {
            throw new sfException('Failed to connect ' . $type . ' server at ' . $connStr);
        }

        $this->parser = $parser;
    }

    public function getStats()
    {
        return (array) imap_mailboxmsginfo($this->handle);
    }

    public function getBody($message)
    {
        return imap_body($this->handle, $message);
    }

    public function getPart($message, $part)
    {
        return imap_fetchbody($this->handle, $message, $part);
    }

    public function getStructure($message)
    {
        return imap_fetchstructure($this->handle, $message);
    }

    public function getMessageInfo($message = '')
    {
        if ($message) {
            $range = $message;
        } else {
            $MC = imap_check($this->handle);
            $range = '1:' . $MC->Nmsgs;
        }
        $response = imap_fetch_overview($this->handle, $range);

        foreach ($response as $msg) {
            $result[$msg->msgno] = (array) $msg;
        }

        return $result;
    }

    public function fetchHeader($message)
    {
        return imap_fetchheader($this->handle, $message, \FT_PREFETCHTEXT);
    }

    public function deleteMessage($message)
    {
        return imap_delete($this->handle, $message);
    }

    public function getMessages($criteria = 'ALL')
    {
        return imap_search($this->handle, $criteria);
    }

    public function setFlags($message, $flags)
    {
        imap_setflag_full($this->handle, $message, $flags);
    }

    public function markMessageAsRead($message)
    {
        $this->setFlags($message, '\\Seen \\Flagged');
    }

    public function expunge()
    {
        imap_expunge($this->handle);
    }

    public function getHeaderInfo($message)
    {
        $header = \is_string($message) || !is_numeric($message) ? $message : imap_fetchheader($this->handle, $message);

        return imap_rfc822_parse_headers($header);
    }

    public function getMessageFrom($message)
    {
        $headerInfo = $this->getHeaderInfo($message);

        $res = [];
        foreach ($headerInfo->from as $from) {
            $address = $from->mailbox . '@' . $from->host;
            if (empty($from->personal)) {
                $thisFrom = $address;
            } else {
                $thisFrom = [$address => imap_utf8($from->personal)];
            }

            if (1 === \count($headerInfo->from)) {
                return $thisFrom;
            }

            $res[] = $thisFrom;
        }

        return $res;
    }

    public function getMessageTo($message)
    {
        $headerInfo = $this->getHeaderInfo($message);

        $res = [];
        foreach ($headerInfo->to as $to) {
            $address = $to->mailbox . '@' . $to->host;
            if (empty($to->personal)) {
                $thisTo = $address;
            } else {
                $thisTo = [$address => imap_utf8($to->personal)];
            }

            if (1 === \count($headerInfo->to)) {
                return $thisTo;
            }

            $res[] = $thisTo;
        }

        return $res;
    }

    public function getUnreadMessages()
    {
        return $this->getMessages('UNSEEN');
    }

    public function isBouncing($message)
    {
        $headers = $this->parseHeaders($this->fetchHeader($message));

        return isset($headers['X-Failed-Recipients']) || (isset($headers['Content-Type']) && false !== strpos($headers['Content-Type'], 'report'));
    }

    public function getBouncingHeaderInfo($message)
    {
        if (!$this->isBouncing($message)) {
            return false;
        }

        return $this->getHeaderInfo($this->getBody($message));
    }

    public function getBouncingFailedTo($message)
    {
        if (!$this->isBouncing($message)) {
            return false;
        }
        $headers = $this->parseHeaders($this->fetchHeader($message));

        $struct = imap_rfc822_parse_adrlist($headers['X-Failed-Recipients'], '');

        $res = [];
        foreach ($struct as $data) {
            $address = $data->mailbox . '@' . $data->host;
            if (empty($data->personal)) {
                $thisTo = $address;
            } else {
                $thisTo = [$address => imap_utf8($data->personal)];
            }

            $res[] = $thisTo;
        }

        return $res;
    }

    public function parseBouncingHeaders($message)
    {
        if (!$this->isBouncing($message)) {
            return false;
        }
        $body = $this->getBody($message);

        $boundary = preg_match_all('/boundary="(.+)"/', $body, $matches);

        if ($boundary) {
            $parts = explode("\n" . '--' . $matches[1][0], $body);
            $body = $parts[0] . "\n";

            return $this->parseHeaders($body, false);
        }

        return $this->parseHeaders($body);
    }

    public function getBouncingBody($message)
    {
        if (!$this->isBouncing($message)) {
            return false;
        }
        $body = $this->getBody($message);
        $parser = new EmailParser($body);

        $origBody = $parser->getBody();
        $boundary = preg_match_all('/boundary="(.+)"/', $body, $matches);

        if ($boundary) {
            $parts = explode("\n" . '--' . $matches[1][0], $origBody);

            return preg_replace('/\r\n\s+/m', '', $parts[0]);
        }

        return $origBody;
    }

    public function getBouncingTo($message)
    {
        if (!$this->isBouncing($message)) {
            return false;
        }

        return $this->getMessageTo($this->getBody($message));
    }

    public function getBouncingFrom($message)
    {
        if (!$this->isBouncing($message)) {
            return false;
        }

        return $this->getMessageFrom($this->getBody($message));
    }

    public function getBouncingSubject($message)
    {
        if (!$this->isBouncing($message)) {
            return false;
        }

        $headers = $this->getHeaderInfo($this->getBody($message));

        return isset($headers->Subject) ? imap_utf8($headers->Subject) : '';
    }

    public function getAttachmentsData($message)
    {
        $structure = imap_fetchstructure($this->handle, $message);

        $attachments = [];
        if (isset($structure->parts) && \count($structure->parts)) {
            for ($i = 0; $i < \count($structure->parts); ++$i) {
                $attachments[$i] = [
                    'is_attachment' => false,
                    'filename' => '',
                    'name' => '',
                    'attachment' => '', ];

                if ($structure->parts[$i]->ifdparameters) {
                    foreach ($structure->parts[$i]->dparameters as $object) {
                        if ('filename' === strtolower($object->attribute)) {
                            $attachments[$i]['is_attachment'] = true;
                            $attachments[$i]['filename'] = $object->value;
                        }
                    }
                }

                if ($structure->parts[$i]->ifparameters) {
                    foreach ($structure->parts[$i]->parameters as $object) {
                        if ('name' === strtolower($object->attribute)) {
                            $attachments[$i]['is_attachment'] = true;
                            $attachments[$i]['name'] = imap_utf8($object->value);
                        }
                    }
                }

                if ($attachments[$i]['is_attachment']) {
                    $attachments[$i]['content'] = imap_fetchbody($this->handle, $message, $i + 1);
                    if (3 === $structure->parts[$i]->encoding) { // 3 = BASE64
                        $attachments[$i]['content'] = base64_decode($attachments[$i]['content'], true);
                    } elseif (4 === $structure->parts[$i]->encoding) { // 4 = QUOTED-PRINTABLE
                        $attachments[$i]['content'] = quoted_printable_decode($attachments[$i]['content']);
                    }

                    unset($attachments[$i]['is_attachment']);
                } else {
                    unset($attachments[$i]);
                }
            }
        }

        return $attachments;
    }

    public function saveAttachments($message, $dir, $chmod = 0664)
    {
        if (!is_dir($dir) || !is_writable($dir)) {
            return;
        }

        foreach ($this->getAttachmentsData($message) as $attachment) {
            $content = $attachment['content'] ?? @file_get_contents($attachment['filename']);

            if (empty($content)) {
                continue;
            }

            $name = $attachment['name'] ?? '';

            if (isset($attachment['filename']) && empty($name)) {
                $name = pathinfo($attachment['filename'], \PATHINFO_BASENAME);
            }

            if (!empty($name)) {
                file_put_contents($dir . '/' . $name, $content);
                chmod($dir . '/' . $name, $chmod);
            }
        }
    }

    public function parsePart($message, $part)
    {
        return imap_bodystruct($this->handle, $message, $part);
    }

    protected function parseHeaders($headers, $deleteWhitespace = true)
    {
        if ($deleteWhitespace) {
            $headers = preg_replace('/\r\n\s+/m', '', $headers);
        }

        preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)?\r\n/m', $headers, $matches);
        foreach ($matches[1] as $key => $value) {
            $result[$value] = $matches[2][$key];
        }

        return $result;
    }
}

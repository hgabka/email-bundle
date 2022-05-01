<?php

namespace Hgabka\EmailBundle\Helper;

use function is_array;
use function is_string;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\InvalidArgumentException;

class MailHelper
{
    public function displayAddresses($addresses, string $glue = ','): string
    {
        if (empty($addresses)) {
            return '';
        }

        if ($addresses instanceof Address) {
            return $addresses->toString();
        }

        if (is_string($addresses)) {
            $addresses = Address::create($addresses);

            return $addresses->toString();
        }

        if (!is_array($addresses)) {
            return '';
        }

        $addressStrings = [];

        foreach ($addresses as $address) {
            try {
                $address = Address::create($address);
            } catch (InvalidArgumentException $e) {
                continue;
            }

            $addressStrings[] = $address->toString();
        }

        return implode($glue, $addressStrings);
    }

    public function generateEmbedId(): string
    {
        return bin2hex(random_bytes(16)) . '@hg_email';
    }

    /**
     * email cím formázás.
     *
     * @param Address|array|string $address
     *
     * @return Address
     */
    public function translateEmailAddress($address): Address
    {
        if ($address instanceof Address) {
            return $address;
        }

        if (is_string($address)) {
            return Address::create($address);
        }

        if ((!isset($address['name']) || '' === $address['name']) && (!isset($address['email']) || '' === $address['email'])) {
            if (is_array($address)) {
                $origAddress = $address;
                $address = reset($address);

                if ($address instanceof Address) {
                    return $address;
                }

                $address = $origAddress;
            }

            return new Address(key($address), current($address));
        }

        if (isset($address['name']) && \strlen($address['name'])) {
            return new Address($address['email'], $address['name']);
        }

        return new Address($address['email']);
    }
}

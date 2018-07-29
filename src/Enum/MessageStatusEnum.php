<?php

namespace Hgabka\EmailBundle\Enum;

abstract class MessageStatusEnum
{
    const STATUS_INIT = 'init';
    const STATUS_KULDENDO = 'kuldendo';
    const STATUS_FOLYAMATBAN = 'folyamatban';
    const STATUS_ELKULDVE = 'elkuldve';

    /** @var array user friendly named type */
    protected static $statusName = [
        self::STATUS_INIT => 'hgabka_email.statuses.init',
        self::STATUS_KULDENDO => 'hgabka_email.statuses.kuldendo',
        self::STATUS_FOLYAMATBAN => 'hgabka_email.statuses.folyamatban',
        self::STATUS_ELKULDVE => 'hgabka_email.statuses.elkuldve',
    ];

    /**
     * @param string $statusShortName
     *
     * @return string
     */
    public static function getStatusName($statusShortName)
    {
        if (empty($statusShortName)) {
            return '';
        }

        if (!isset(static::$statusName[$statusShortName])) {
            return "Unknown type ($statusShortName)";
        }

        return static::$statusName[$statusShortName];
    }

    /**
     * @return array<string>
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_INIT,
            self::STATUS_KULDENDO,
            self::STATUS_FOLYAMATBAN,
            self::STATUS_ELKULDVE,
        ];
    }

    public static function getStatusTextChoices()
    {
        $res = [];

        foreach (self::getAvailableStatuses() as $status) {
            $res[$status] = 'hgabka_email.statuses.'.$status;
        }

        return $res;
    }
}

<?php

namespace Hgabka\EmailBundle\Enum;

abstract class QueueStatusEnum
{
    const STATUS_INIT = 'init';
    const STATUS_ELKULDVE = 'elkuldve';
    const STATUS_HIBA = 'hiba';
    const STATUS_SIKERTELEN = 'sikertelen';
    const STATUS_VISSZAPATTANT = 'visszapattant';

    /** @var array user friendly named type */
    protected static $statusName = [
        self::STATUS_INIT => 'Létrehozva',
        self::STATUS_ELKULDVE => 'Elküldve',
        self::STATUS_HIBA => 'Hiba',
        self::STATUS_SIKERTELEN => 'Sikertelen',
        self::STATUS_VISSZAPATTANT => 'Sikertelen',
    ];

    /**
     * @param string $statusShortName
     *
     * @return string
     */
    public static function getStatusName($statusShortName)
    {
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
            self::STATUS_ELKULDVE,
            self::STATUS_HIBA,
            self::STATUS_SIKERTELEN,
            self::STATUS_VISSZAPATTANT,
        ];
    }
}

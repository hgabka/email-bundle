<?php

namespace Hgabka\EmailBundle\Enum;

abstract class QueueStatusEnum
{
    public const STATUS_INIT = 'init';
    public const STATUS_ELKULDVE = 'elkuldve';
    public const STATUS_HIBA = 'hiba';
    public const STATUS_SIKERTELEN = 'sikertelen';
    public const STATUS_VISSZAPATTANT = 'visszapattant';

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

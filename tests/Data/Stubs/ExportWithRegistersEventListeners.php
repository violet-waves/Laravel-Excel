<?php

namespace VioletWaves\Excel\Tests\Data\Stubs;

use VioletWaves\Excel\Concerns\Exportable;
use VioletWaves\Excel\Concerns\RegistersEventListeners;
use VioletWaves\Excel\Concerns\WithEvents;

class ExportWithRegistersEventListeners implements WithEvents
{
    use Exportable, RegistersEventListeners;

    /**
     * @var callable
     */
    public static $beforeExport;

    /**
     * @var callable
     */
    public static $beforeWriting;

    /**
     * @var callable
     */
    public static $beforeSheet;

    /**
     * @var callable
     */
    public static $afterSheet;

    public static function beforeExport()
    {
        (static::$beforeExport)(...func_get_args());
    }

    public static function beforeWriting()
    {
        (static::$beforeWriting)(...func_get_args());
    }

    public static function beforeSheet()
    {
        (static::$beforeSheet)(...func_get_args());
    }

    public static function afterSheet()
    {
        (static::$afterSheet)(...func_get_args());
    }
}

<?php

namespace VioletWaves\Excel\Tests;

use VioletWaves\Excel\Concerns\Exportable;
use VioletWaves\Excel\Concerns\RegistersEventListeners;
use VioletWaves\Excel\Concerns\WithEvents;
use VioletWaves\Excel\Events\BeforeExport;
use VioletWaves\Excel\Events\BeforeSheet;
use VioletWaves\Excel\Sheet;
use VioletWaves\Excel\Writer;
use PhpOffice\PhpSpreadsheet\Document\Properties;

class DelegatedMacroableTest extends TestCase
{
    public function test_can_call_methods_from_delegate()
    {
        $export = new class implements WithEvents
        {
            use RegistersEventListeners, Exportable;

            public static function beforeExport(BeforeExport $event)
            {
                // ->getProperties() will be called via __call on the ->getDelegate()
                TestCase::assertInstanceOf(Properties::class, $event->writer->getProperties());
            }
        };

        $export->download('some-file.xlsx');
    }

    public function test_can_use_writer_macros()
    {
        $called = false;
        Writer::macro('test', function () use (&$called) {
            $called = true;
        });

        $export = new class implements WithEvents
        {
            use RegistersEventListeners, Exportable;

            public static function beforeExport(BeforeExport $event)
            {
                // call macro method
                $event->writer->test();
            }
        };

        $export->download('some-file.xlsx');

        $this->assertTrue($called);
    }

    public function test_can_use_sheet_macros()
    {
        $called = false;
        Sheet::macro('test', function () use (&$called) {
            $called = true;
        });

        $export = new class implements WithEvents
        {
            use RegistersEventListeners, Exportable;

            public static function beforeSheet(BeforeSheet $event)
            {
                // call macro method
                $event->sheet->test();
            }
        };

        $export->download('some-file.xlsx');

        $this->assertTrue($called);
    }
}

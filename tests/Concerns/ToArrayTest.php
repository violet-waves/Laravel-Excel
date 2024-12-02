<?php

namespace VioletWaves\Excel\Tests\Concerns;

use VioletWaves\Excel\Concerns\Importable;
use VioletWaves\Excel\Concerns\ToArray;
use VioletWaves\Excel\Tests\TestCase;
use PHPUnit\Framework\Assert;

class ToArrayTest extends TestCase
{
    public function test_can_import_to_array()
    {
        $import = new class implements ToArray
        {
            use Importable;

            public $called = false;

            /**
             * @param  array  $array
             */
            public function array(array $array)
            {
                $this->called = true;

                Assert::assertEquals([
                    ['test', 'test'],
                    ['test', 'test'],
                ], $array);
            }
        };

        $import->import('import.xlsx');

        $this->assertTrue($import->called);
    }

    public function test_can_import_multiple_sheets_to_array()
    {
        $import = new class implements ToArray
        {
            use Importable;

            public $called = 0;

            /**
             * @param  array  $array
             */
            public function array(array $array)
            {
                $this->called++;

                $sheetNumber = $this->called;

                Assert::assertEquals([
                    [$sheetNumber . '.A1', $sheetNumber . '.B1'],
                    [$sheetNumber . '.A2', $sheetNumber . '.B2'],
                ], $array);
            }
        };

        $import->import('import-multiple-sheets.xlsx');

        $this->assertEquals(2, $import->called);
    }
}

<?php

namespace VioletWaves\Excel\Tests\Concerns;

use Illuminate\Support\Collection;
use VioletWaves\Excel\Concerns\Importable;
use VioletWaves\Excel\Concerns\SkipsEmptyRows;
use VioletWaves\Excel\Concerns\ToArray;
use VioletWaves\Excel\Concerns\ToCollection;
use VioletWaves\Excel\Concerns\ToModel;
use VioletWaves\Excel\Concerns\WithFormatData;
use VioletWaves\Excel\Tests\TestCase;
use PHPUnit\Framework\Assert;

class WithFormatDataTest extends TestCase
{
    public function test_by_default_import_to_array()
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

                Assert::assertSame(44328, $array[0][0]);
            }
        };

        $import->import('import-format-data.xlsx');

        $this->assertTrue($import->called);
    }

    public function test_can_import_to_array_with_format_data()
    {
        config()->set('excel.imports.read_only', false);
        $import = new class implements ToArray, WithFormatData
        {
            use Importable;

            public $called = false;

            /**
             * @param  array  $array
             */
            public function array(array $array)
            {
                $this->called = true;

                Assert::assertSame('5/12/2021', $array[0][0]);
            }
        };

        $import->import('import-format-data.xlsx');

        $this->assertTrue($import->called);
    }

    public function test_can_import_to_array_with_format_data_and_skips_empty_rows()
    {
        config()->set('excel.imports.read_only', false);
        $import = new class implements ToArray, WithFormatData, SkipsEmptyRows
        {
            use Importable;

            public $called = false;

            /**
             * @param  array  $array
             */
            public function array(array $array)
            {
                $this->called = true;

                Assert::assertSame('5/12/2021', $array[0][0]);
            }
        };

        $import->import('import-format-data.xlsx');

        $this->assertTrue($import->called);
    }

    public function test_by_default_import_to_collection()
    {
        $import = new class implements ToCollection
        {
            use Importable;

            public $called = false;

            /**
             * @param  array  $row
             * @return Model|null
             */
            public function collection(collection $collection)
            {
                $this->called = true;

                Assert::assertSame(44328, $collection[0][0]);

                return null;
            }
        };

        $import->import('import-format-data.xlsx');

        $this->assertTrue($import->called);
    }

    public function test_can_import_to_collection_with_format_data()
    {
        config()->set('excel.imports.read_only', false);
        $import = new class implements ToCollection, WithFormatData
        {
            use Importable;

            public $called = false;

            /**
             * @param  array  $row
             * @return Model|null
             */
            public function collection(collection $collection)
            {
                $this->called = true;

                Assert::assertSame('5/12/2021', $collection[0][0]);

                return null;
            }
        };

        $import->import('import-format-data.xlsx');

        $this->assertTrue($import->called);
    }

    public function test_by_default_import_to_model()
    {
        $import = new class implements ToModel
        {
            use Importable;

            public $called = false;

            /**
             * @param  array  $row
             * @return Model|null
             */
            public function model(array $row)
            {
                $this->called = true;

                Assert::assertSame(44328, $row[0]);

                return null;
            }
        };

        $import->import('import-format-data.xlsx');

        $this->assertTrue($import->called);
    }

    public function test_can_import_to_model_with_format_data()
    {
        config()->set('excel.imports.read_only', false);
        $import = new class implements ToModel, WithFormatData
        {
            use Importable;

            public $called = false;

            /**
             * @param  array  $row
             * @return Model|null
             */
            public function model(array $row)
            {
                $this->called = true;

                Assert::assertSame('5/12/2021', $row[0]);

                return null;
            }
        };

        $import->import('import-format-data.xlsx');

        $this->assertTrue($import->called);
    }
}

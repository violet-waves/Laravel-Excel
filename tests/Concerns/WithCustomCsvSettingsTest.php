<?php

namespace VioletWaves\Excel\Tests\Concerns;

use Illuminate\Support\Collection;
use VioletWaves\Excel\Concerns\FromCollection;
use VioletWaves\Excel\Concerns\ToArray;
use VioletWaves\Excel\Concerns\WithCustomCsvSettings;
use VioletWaves\Excel\Excel;
use VioletWaves\Excel\HeadingRowImport;
use VioletWaves\Excel\Tests\TestCase;
use PHPUnit\Framework\Assert;

class WithCustomCsvSettingsTest extends TestCase
{
    /**
     * @var Excel
     */
    protected $SUT;

    protected function setUp(): void
    {
        parent::setUp();

        $this->SUT = $this->app->make(Excel::class);
    }

    public function test_can_store_csv_export_with_custom_settings()
    {
        $export = new class implements FromCollection, WithCustomCsvSettings
        {
            /**
             * @return Collection
             */
            public function collection()
            {
                return collect([
                    ['A1', 'B1'],
                    ['A2', 'B2'],
                ]);
            }

            /**
             * @return array
             */
            public function getCsvSettings(): array
            {
                return [
                    'delimiter'              => ';',
                    'enclosure'              => '',
                    'line_ending'            => PHP_EOL,
                    'use_bom'                => true,
                    'include_separator_line' => true,
                    'excel_compatibility'    => false,
                    'output_encoding'        => '',
                    'test_auto_detect'       => false,
                ];
            }
        };

        $this->SUT->store($export, 'custom-csv.csv');

        $contents = file_get_contents(__DIR__ . '/../Data/Disks/Local/custom-csv.csv');

        $this->assertStringContains('sep=;', $contents);
        $this->assertStringContains('A1;B1', $contents);
        $this->assertStringContains('A2;B2', $contents);
    }

    public function test_can_store_csv_export_with_custom_encoding()
    {
        $export = new class implements FromCollection, WithCustomCsvSettings
        {
            /**
             * @return Collection
             */
            public function collection()
            {
                return collect([
                    ['A1', '€ŠšŽžŒœŸ'],
                    ['A2', 'åßàèòìù'],
                ]);
            }

            /**
             * @return array
             */
            public function getCsvSettings(): array
            {
                return [
                    'delimiter'              => ';',
                    'enclosure'              => '',
                    'line_ending'            => PHP_EOL,
                    'use_bom'                => false,
                    'include_separator_line' => true,
                    'excel_compatibility'    => false,
                    'output_encoding'        => 'ISO-8859-15',
                ];
            }
        };

        $this->SUT->store($export, 'custom-csv-iso.csv');

        $contents = file_get_contents(__DIR__ . '/../Data/Disks/Local/custom-csv-iso.csv');

        Assert::assertEquals('ISO-8859-15', mb_detect_encoding($contents, 'ISO-8859-15', true));
        Assert::assertFalse(mb_detect_encoding($contents, 'UTF-8', true));

        $contents = mb_convert_encoding($contents, 'UTF-8', 'ISO-8859-15');

        $this->assertStringContains('sep=;', $contents);
        $this->assertStringContains('A1;€ŠšŽžŒœŸ', $contents);
        $this->assertStringContains('A2;åßàèòìù', $contents);
    }

    public function test_can_read_csv_with_auto_detecting_delimiter_semicolon()
    {
        $this->assertEquals([
            [
                ['a1', 'b1'],
            ],
        ], (new HeadingRowImport())->toArray('csv-with-other-delimiter.csv'));
    }

    public function test_can_read_csv_with_auto_detecting_delimiter_comma()
    {
        $this->assertEquals([
            [
                ['a1', 'b1'],
            ],
        ], (new HeadingRowImport())->toArray('csv-with-comma.csv'));
    }

    public function test_can_read_csv_import_with_custom_settings()
    {
        $import = new class implements WithCustomCsvSettings, ToArray
        {
            /**
             * @return array
             */
            public function getCsvSettings(): array
            {
                return [
                    'delimiter'        => ';',
                    'enclosure'        => '',
                    'escape_character' => '\\',
                    'contiguous'       => true,
                    'input_encoding'   => 'UTF-8',
                ];
            }

            /**
             * @param  array  $array
             */
            public function array(array $array)
            {
                Assert::assertEquals([
                    ['A1', 'B1'],
                    ['A2', 'B2'],
                ], $array);
            }
        };

        $this->SUT->import($import, 'csv-with-other-delimiter.csv');
    }

    public function test_cannot_read_with_wrong_delimiter()
    {
        $import = new class implements WithCustomCsvSettings, ToArray
        {
            /**
             * @return array
             */
            public function getCsvSettings(): array
            {
                return [
                    'delimiter' => ',',
                ];
            }

            /**
             * @param  array  $array
             */
            public function array(array $array)
            {
                Assert::assertEquals([
                    ['A1;B1'],
                    ['A2;B2'],
                ], $array);
            }
        };

        $this->SUT->import($import, 'csv-with-other-delimiter.csv');
    }
}

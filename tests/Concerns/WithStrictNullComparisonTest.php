<?php

namespace VioletWaves\Excel\Tests\Concerns;

use Illuminate\Support\Collection;
use VioletWaves\Excel\Concerns\Exportable;
use VioletWaves\Excel\Concerns\FromCollection;
use VioletWaves\Excel\Concerns\WithHeadings;
use VioletWaves\Excel\Concerns\WithStrictNullComparison;
use VioletWaves\Excel\Tests\TestCase;

class WithStrictNullComparisonTest extends TestCase
{
    public function test_exported_zero_values_are_not_null_when_exporting_with_strict_null_comparison()
    {
        $export = new class implements FromCollection, WithHeadings, WithStrictNullComparison
        {
            use Exportable;

            /**
             * @return Collection
             */
            public function collection()
            {
                return collect([
                    ['string', '0', 0, 0.0, 'string'],
                ]);
            }

            /**
             * @return array
             */
            public function headings(): array
            {
                return ['string', '0', 0, 0.0, 'string'];
            }
        };

        $response = $export->store('with-strict-null-comparison-store.xlsx');

        $this->assertTrue($response);

        $actual = $this->readAsArray(__DIR__ . '/../Data/Disks/Local/with-strict-null-comparison-store.xlsx', 'Xlsx');

        $expected = [
            ['string', 0.0, 0.0, 0.0, 'string'],
            ['string', 0.0, 0.0, 0.0, 'string'],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function test_exported_zero_values_are_null_when_not_exporting_with_strict_null_comparison()
    {
        $export = new class implements FromCollection, WithHeadings
        {
            use Exportable;

            /**
             * @return Collection
             */
            public function collection()
            {
                return collect([
                    ['string', 0, 0.0, 'string'],
                ]);
            }

            /**
             * @return array
             */
            public function headings(): array
            {
                return ['string', 0, 0.0, 'string'];
            }
        };

        $response = $export->store('without-strict-null-comparison-store.xlsx');

        $this->assertTrue($response);

        $actual = $this->readAsArray(__DIR__ . '/../Data/Disks/Local/without-strict-null-comparison-store.xlsx', 'Xlsx');

        $expected = [
            ['string', null, null, 'string'],
            ['string', null, null, 'string'],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function test_exports_trailing_empty_cells()
    {
        $export = new class implements FromCollection, WithStrictNullComparison
        {
            use Exportable;

            /**
             * @return Collection
             */
            public function collection()
            {
                return collect([
                    ['a1', '', '', 'd1', ''],
                    ['a2', '', '', 'd2', ''],
                ]);
            }
        };

        $response = $export->store('empty-cells.csv');

        $this->assertTrue($response);

        $file   = __DIR__ . '/../Data/Disks/Local/empty-cells.csv';
        $actual = $this->readAsArray($file, 'Csv');

        $expected = [
            ['a1', null, null, 'd1'],
            ['a2', null, null, 'd2'],
        ];

        $this->assertEquals($expected, $actual);

        $contents = file_get_contents($file);
        $this->assertStringContains('"a1","","","d1",""', $contents);
        $this->assertStringContains('"a2","","","d2",""', $contents);
    }

    public function test_exports_trailing_empty_cells_by_setting_config_strict_null_comparison()
    {
        config()->set('excel.exports.strict_null_comparison', false);

        $export = new class implements FromCollection
        {
            use Exportable;

            /**
             * @return Collection
             */
            public function collection()
            {
                return collect([
                    ['a1', '', '', 'd1', ''],
                    ['a2', '', '', 'd2', ''],
                ]);
            }
        };

        $file = __DIR__ . '/../Data/Disks/Local/empty-cells-config.csv';

        $export->store('empty-cells-config.csv');

        $contents = file_get_contents($file);
        $this->assertStringContains('"a1","","","d1"', $contents);

        config()->set('excel.exports.strict_null_comparison', true);

        $export->store('empty-cells-config.csv');

        $contents = file_get_contents($file);
        $this->assertStringContains('"a1","","","d1",""', $contents);
    }
}

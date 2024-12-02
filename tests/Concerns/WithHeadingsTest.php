<?php

namespace VioletWaves\Excel\Tests\Concerns;

use Illuminate\Support\Collection;
use VioletWaves\Excel\Concerns\Exportable;
use VioletWaves\Excel\Concerns\FromCollection;
use VioletWaves\Excel\Concerns\WithCustomStartCell;
use VioletWaves\Excel\Concerns\WithHeadings;
use VioletWaves\Excel\Tests\TestCase;

class WithHeadingsTest extends TestCase
{
    public function test_can_export_from_collection_with_heading_row()
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
                    ['A1', 'B1', 'C1'],
                    ['A2', 'B2', 'C2'],
                ]);
            }

            /**
             * @return array
             */
            public function headings(): array
            {
                return ['A', 'B', 'C'];
            }
        };

        $response = $export->store('with-heading-store.xlsx');

        $this->assertTrue($response);

        $actual = $this->readAsArray(__DIR__ . '/../Data/Disks/Local/with-heading-store.xlsx', 'Xlsx');

        $expected = [
            ['A', 'B', 'C'],
            ['A1', 'B1', 'C1'],
            ['A2', 'B2', 'C2'],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function test_can_export_from_collection_with_multiple_heading_rows()
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
                    ['A1', 'B1', 'C1'],
                    ['A2', 'B2', 'C2'],
                ]);
            }

            /**
             * @return array
             */
            public function headings(): array
            {
                return [
                    ['A', 'B', 'C'],
                    ['Aa', 'Bb', 'Cc'],
                ];
            }
        };

        $response = $export->store('with-heading-store.xlsx');

        $this->assertTrue($response);

        $actual = $this->readAsArray(__DIR__ . '/../Data/Disks/Local/with-heading-store.xlsx', 'Xlsx');

        $expected = [
            ['A', 'B', 'C'],
            ['Aa', 'Bb', 'Cc'],
            ['A1', 'B1', 'C1'],
            ['A2', 'B2', 'C2'],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function test_can_export_from_collection_with_heading_row_with_custom_start_cell()
    {
        $export = new class implements FromCollection, WithHeadings, WithCustomStartCell
        {
            use Exportable;

            /**
             * @return Collection
             */
            public function collection()
            {
                return collect([
                    ['A1', 'B1', 'C1'],
                    ['A2', 'B2', 'C2'],
                ]);
            }

            /**
             * @return array
             */
            public function headings(): array
            {
                return ['A', 'B', 'C'];
            }

            /**
             * @return string
             */
            public function startCell(): string
            {
                return 'B2';
            }
        };

        $response = $export->store('with-heading-store.xlsx');

        $this->assertTrue($response);

        $actual = $this->readAsArray(__DIR__ . '/../Data/Disks/Local/with-heading-store.xlsx', 'Xlsx');

        $expected = [
            [null, null, null, null],
            [null, 'A', 'B', 'C'],
            [null, 'A1', 'B1', 'C1'],
            [null, 'A2', 'B2', 'C2'],
        ];

        $this->assertEquals($expected, $actual);
    }
}

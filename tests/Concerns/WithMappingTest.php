<?php

namespace VioletWaves\Excel\Tests\Concerns;

use VioletWaves\Excel\Concerns\Exportable;
use VioletWaves\Excel\Concerns\FromArray;
use VioletWaves\Excel\Concerns\WithMapping;
use VioletWaves\Excel\Tests\Data\Stubs\WithMappingExport;
use VioletWaves\Excel\Tests\TestCase;

class WithMappingTest extends TestCase
{
    public function test_can_export_with_heading()
    {
        $export = new WithMappingExport();

        $response = $export->store('with-mapping-store.xlsx');

        $this->assertTrue($response);

        $actual = $this->readAsArray(__DIR__ . '/../Data/Disks/Local/with-mapping-store.xlsx', 'Xlsx');

        $expected = [
            [
                'mapped-A1',
                'mapped-B1',
                'mapped-C1',
            ],
            [
                'mapped-A2',
                'mapped-B2',
                'mapped-C2',
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function test_can_return_multiple_rows_in_map()
    {
        $export = new class implements FromArray, WithMapping
        {
            use Exportable;

            /**
             * @return array
             */
            public function array(): array
            {
                return [
                    ['id' => 1],
                    ['id' => 2],
                    ['id' => 3],
                ];
            }

            /**
             * @param  mixed  $row
             * @return array
             */
            public function map($row): array
            {
                return [
                    [$row['id']],
                    [$row['id']],
                ];
            }
        };

        $response = $export->store('with-mapping-store.xlsx');

        $this->assertTrue($response);

        $actual = $this->readAsArray(__DIR__ . '/../Data/Disks/Local/with-mapping-store.xlsx', 'Xlsx');

        $this->assertCount(6, $actual);
    }

    public function test_json_array_columns_shouldnt_be_detected_as_multiple_rows()
    {
        $export = new class implements FromArray
        {
            use Exportable;

            /**
             * @return array
             */
            public function array(): array
            {
                return [
                    ['id' => 1, 'json' => ['other_id' => 1]],
                    ['id' => 2, 'json' => ['other_id' => 2]],
                    ['id' => 3, 'json' => ['other_id' => 3]],
                ];
            }
        };

        $response = $export->store('with-mapping-store.xlsx');

        $this->assertTrue($response);

        $actual = $this->readAsArray(__DIR__ . '/../Data/Disks/Local/with-mapping-store.xlsx', 'Xlsx');

        $this->assertCount(3, $actual);

        $this->assertEquals([
            [1, \json_encode(['other_id' => 1])],
            [2, \json_encode(['other_id' => 2])],
            [3, \json_encode(['other_id' => 3])],
        ], $actual);
    }
}

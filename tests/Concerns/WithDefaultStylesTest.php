<?php

namespace VioletWaves\Excel\Tests\Concerns;

use VioletWaves\Excel\Concerns\Exportable;
use VioletWaves\Excel\Concerns\FromArray;
use VioletWaves\Excel\Concerns\WithDefaultStyles;
use VioletWaves\Excel\Tests\TestCase;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;

class WithDefaultStylesTest extends TestCase
{
    public function test_can_configure_default_styles()
    {
        $export = new class implements FromArray, WithDefaultStyles
        {
            use Exportable;

            public function defaultStyles(Style $defaultStyle)
            {
                return [
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'fff2f2f2'],
                    ],
                ];
            }

            public function array(): array
            {
                return [
                    ['A1', 'B1', 'C1'],
                    ['A2', 'B2', 'C2'],
                ];
            }
        };

        $export->store('with-default-styles.xlsx');

        $spreadsheet = $this->read(__DIR__ . '/../Data/Disks/Local/with-default-styles.xlsx', 'Xlsx');
        $sheet       = $spreadsheet->getDefaultStyle();

        $this->assertEquals(Fill::FILL_SOLID, $sheet->getFill()->getFillType());
        $this->assertEquals('fff2f2f2', $sheet->getFill()->getStartColor()->getARGB());
    }
}

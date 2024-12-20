<?php

namespace VioletWaves\Excel\Tests\Concerns;

use VioletWaves\Excel\Concerns\Exportable;
use VioletWaves\Excel\Concerns\WithProperties;
use VioletWaves\Excel\Tests\TestCase;

class WithPropertiesTest extends TestCase
{
    public function test_can_set_custom_document_properties()
    {
        $export = new class implements WithProperties
        {
            use Exportable;

            public function properties(): array
            {
                return [
                    'creator'        => 'A',
                    'lastModifiedBy' => 'B',
                    'title'          => 'C',
                    'description'    => 'D',
                    'subject'        => 'E',
                    'keywords'       => 'F',
                    'category'       => 'G',
                    'manager'        => 'H',
                    'company'        => 'I',
                ];
            }
        };

        $export->store('with-properties.xlsx');

        $spreadsheet = $this->read(__DIR__ . '/../Data/Disks/Local/with-properties.xlsx', 'Xlsx');
        $props       = $spreadsheet->getProperties();

        $this->assertEquals('A', $props->getCreator());
        $this->assertEquals('B', $props->getLastModifiedBy());
        $this->assertEquals('C', $props->getTitle());
        $this->assertEquals('D', $props->getDescription());
        $this->assertEquals('E', $props->getSubject());
        $this->assertEquals('F', $props->getKeywords());
        $this->assertEquals('G', $props->getCategory());
        $this->assertEquals('H', $props->getManager());
        $this->assertEquals('I', $props->getCompany());
    }

    public function test_it_merges_with_default_properties()
    {
        config()->set('excel.exports.properties.title', 'Default Title');
        config()->set('excel.exports.properties.description', 'Default Description');

        $export = new class implements WithProperties
        {
            use Exportable;

            public function properties(): array
            {
                return [
                    'description' => 'Custom Description',
                ];
            }
        };

        $export->store('with-properties.xlsx');

        $spreadsheet = $this->read(__DIR__ . '/../Data/Disks/Local/with-properties.xlsx', 'Xlsx');
        $props       = $spreadsheet->getProperties();

        $this->assertEquals('Default Title', $props->getTitle());
        $this->assertEquals('Custom Description', $props->getDescription());
    }

    public function test_it_ignores_empty_properties()
    {
        $export = new class implements WithProperties
        {
            use Exportable;

            public function properties(): array
            {
                return [
                    'description' => '',
                ];
            }
        };

        $export->store('with-properties.xlsx');

        $spreadsheet = $this->read(__DIR__ . '/../Data/Disks/Local/with-properties.xlsx', 'Xlsx');
        $props       = $spreadsheet->getProperties();

        $this->assertSame('Unknown Creator', $props->getCreator());
        $this->assertSame('Untitled Spreadsheet', $props->getTitle());
        $this->assertSame('', $props->getDescription());
    }
}

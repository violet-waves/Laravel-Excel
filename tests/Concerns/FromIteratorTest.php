<?php

namespace VioletWaves\Excel\Tests\Concerns;

use ArrayIterator;
use Iterator;
use VioletWaves\Excel\Concerns\Exportable;
use VioletWaves\Excel\Concerns\FromIterator;
use VioletWaves\Excel\Tests\TestCase;

class FromIteratorTest extends TestCase
{
    public function test_can_export_from_iterator()
    {
        $export = new class implements FromIterator
        {
            use Exportable;

            /**
             * @return array
             */
            public function array()
            {
                return [
                    ['test', 'test'],
                    ['test', 'test'],
                ];
            }

            /**
             * @return Iterator
             */
            public function iterator(): Iterator
            {
                return new ArrayIterator($this->array());
            }
        };

        $response = $export->store('from-iterator-store.xlsx');

        $this->assertTrue($response);

        $contents = $this->readAsArray(__DIR__ . '/../Data/Disks/Local/from-iterator-store.xlsx', 'Xlsx');

        $this->assertEquals($export->array(), $contents);
    }
}

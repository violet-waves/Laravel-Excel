<?php

namespace VioletWaves\Excel\Tests\Mixins;

use Illuminate\Support\Collection;
use VioletWaves\Excel\Excel;
use VioletWaves\Excel\Tests\Data\Stubs\Database\User;
use VioletWaves\Excel\Tests\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadCollectionTest extends TestCase
{
    public function test_can_download_a_collection_as_excel()
    {
        $collection = new Collection([
            ['column_1' => 'test', 'column_2' => 'test'],
            ['column_1' => 'test2', 'column_2' => 'test2'],
        ]);

        $response = $collection->downloadExcel('collection-download.xlsx', Excel::XLSX);

        $array = $this->readAsArray($response->getFile()->getPathName(), Excel::XLSX);

        // First row are not headings
        $firstRow = collect($array)->first();
        $this->assertEquals(['test', 'test'], $firstRow);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals(
            'attachment; filename=collection-download.xlsx',
            str_replace('"', '', $response->headers->get('Content-Disposition'))
        );
    }

    public function test_can_download_a_collection_with_headers_as_excel()
    {
        $collection = new Collection([
            ['column_1' => 'test', 'column_2' => 'test'],
            ['column_1' => 'test', 'column_2' => 'test'],
        ]);

        $response = $collection->downloadExcel('collection-headers-download.xlsx', Excel::XLSX, true);

        $array = $this->readAsArray($response->getFile()->getPathName(), Excel::XLSX);

        $this->assertEquals(['column_1', 'column_2'], collect($array)->first());
    }

    public function test_can_download_collection_with_headers_with_hidden_eloquent_attributes()
    {
        $collection = new Collection([
            new User(['name' => 'Patrick', 'password' => 'my_password']),
        ]);

        $response = $collection->downloadExcel('collection-headers-download.xlsx', Excel::XLSX, true);

        $array = $this->readAsArray($response->getFile()->getPathName(), Excel::XLSX);

        $this->assertEquals(['name'], collect($array)->first());
    }

    public function test_can_download_collection_with_headers_when_making_attributes_visible()
    {
        $user = new User(['name' => 'Patrick', 'password' => 'my_password']);
        $user->makeVisible(['password']);

        $collection = new Collection([
            $user,
        ]);

        $response = $collection->downloadExcel('collection-headers-download.xlsx', Excel::XLSX, true);

        $array = $this->readAsArray($response->getFile()->getPathName(), Excel::XLSX);

        $this->assertEquals(['name', 'password'], collect($array)->first());
    }

    public function test_can_set_custom_response_headers()
    {
        $collection = new Collection([
            ['column_1' => 'test', 'column_2' => 'test'],
            ['column_1' => 'test2', 'column_2' => 'test2'],
        ]);

        $responseHeaders = [
            'CUSTOMER-HEADER-1' => 'CUSTOMER-HEADER1-VAL',
            'CUSTOMER-HEADER-2' => 'CUSTOMER-HEADER2-VAL',
        ];
        /** @var BinaryFileResponse $response */
        $response = $collection->downloadExcel('collection-download.xlsx', Excel::XLSX, false, $responseHeaders);
        $this->assertTrue($response->headers->contains('CUSTOMER-HEADER-1', 'CUSTOMER-HEADER1-VAL'));
        $this->assertTrue($response->headers->contains('CUSTOMER-HEADER-2', 'CUSTOMER-HEADER2-VAL'));
    }
}

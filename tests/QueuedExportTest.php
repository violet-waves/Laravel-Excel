<?php

namespace VioletWaves\Excel\Tests;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use VioletWaves\Excel\Excel;
use VioletWaves\Excel\Files\RemoteTemporaryFile;
use VioletWaves\Excel\Files\TemporaryFile;
use VioletWaves\Excel\Jobs\AppendDataToSheet;
use VioletWaves\Excel\Tests\Data\Stubs\AfterQueueExportJob;
use VioletWaves\Excel\Tests\Data\Stubs\EloquentCollectionWithMappingExport;
use VioletWaves\Excel\Tests\Data\Stubs\QueuedExport;
use VioletWaves\Excel\Tests\Data\Stubs\QueuedExportWithFailedEvents;
use VioletWaves\Excel\Tests\Data\Stubs\QueuedExportWithFailedHook;
use VioletWaves\Excel\Tests\Data\Stubs\QueuedExportWithLocalePreferences;
use VioletWaves\Excel\Tests\Data\Stubs\ShouldQueueExport;
use Throwable;

class QueuedExportTest extends TestCase
{
    public function test_can_queue_an_export()
    {
        $export = new QueuedExport();

        $export->queue('queued-export.xlsx')->chain([
            new AfterQueueExportJob(__DIR__ . '/Data/Disks/Local/queued-export.xlsx'),
        ]);
    }

    public function test_can_queue_an_export_and_store_on_different_disk()
    {
        $export = new QueuedExport();

        $export->queue('queued-export.xlsx', 'test')->chain([
            new AfterQueueExportJob(__DIR__ . '/Data/Disks/Test/queued-export.xlsx'),
        ]);
    }

    public function test_can_queue_export_with_remote_temp_disk()
    {
        config()->set('excel.temporary_files.remote_disk', 'test');

        // Delete the local temp file before each append job
        // to simulate using a shared remote disk, without
        // having a dependency on a local temp file.
        $jobs = 0;
        Queue::before(function (JobProcessing $event) use (&$jobs) {
            if ($event->job->resolveName() === AppendDataToSheet::class) {
                /** @var TemporaryFile $tempFile */
                $tempFile = $this->inspectJobProperty($event->job, 'temporaryFile');

                $this->assertInstanceOf(RemoteTemporaryFile::class, $tempFile);

                // Should exist remote
                $this->assertTrue(
                    $tempFile->exists()
                );

                // File was deleted locally
                $this->assertFalse(
                    file_exists($tempFile->getLocalPath())
                );

                $jobs++;
            }
        });

        $export = new QueuedExport();

        $export->queue('queued-export.xlsx')->chain([
            new AfterQueueExportJob(__DIR__ . '/Data/Disks/Local/queued-export.xlsx'),
        ]);

        $array = $this->readAsArray(__DIR__ . '/Data/Disks/Local/queued-export.xlsx', Excel::XLSX);

        $this->assertCount(100, $array);
        $this->assertEquals(3, $jobs);
    }

    public function test_can_queue_export_with_remote_temp_disk_and_prefix()
    {
        config()->set('excel.temporary_files.remote_disk', 'test');
        config()->set('excel.temporary_files.remote_prefix', 'tmp/');

        $export = new QueuedExport();

        $export->queue('queued-export.xlsx')->chain([
            new AfterQueueExportJob(__DIR__ . '/Data/Disks/Local/queued-export.xlsx'),
        ]);
    }

    public function test_can_implicitly_queue_an_export()
    {
        $export = new ShouldQueueExport();

        $export->store('queued-export.xlsx', 'test')->chain([
            new AfterQueueExportJob(__DIR__ . '/Data/Disks/Test/queued-export.xlsx'),
        ]);
    }

    public function test_can_queue_export_with_mapping_on_eloquent_models()
    {
        $export = new EloquentCollectionWithMappingExport();

        $export->queue('queued-export.xlsx')->chain([
            new AfterQueueExportJob(__DIR__ . '/Data/Disks/Local/queued-export.xlsx'),
        ]);

        $actual = $this->readAsArray(__DIR__ . '/Data/Disks/Local/queued-export.xlsx', 'Xlsx');

        $this->assertEquals([
            ['Patrick', 'Brouwers'],
        ], $actual);
    }

    public function test_can_catch_failures()
    {
        $export = new QueuedExportWithFailedHook();
        try {
            $export->queue('queued-export.xlsx');
        } catch (Throwable $e) {
        }

        $this->assertTrue(app('queue-has-failed'));
    }

    public function test_can_catch_failures_on_queue_export_job()
    {
        $export = new QueuedExportWithFailedEvents();

        try {
            $export->queue('queued-export.xlsx');
        } catch (Throwable $e) {
        }

        $this->assertTrue(app('queue-has-failed-from-queue-export-job'));
    }

    public function test_can_set_locale_on_queue_export_job()
    {
        $currentLocale = app()->getLocale();

        $export = new QueuedExportWithLocalePreferences('ru');

        $export->queue('queued-export.xlsx');

        $this->assertTrue(app('queue-has-correct-locale'));

        $this->assertEquals($currentLocale, app()->getLocale());
    }

    public function test_can_queue_export_not_flushing_the_cache()
    {
        config()->set('excel.cache.driver', 'illuminate');

        Cache::put('test', 'test');

        $export = new QueuedExport();

        $export->queue('queued-export.xlsx')->chain([
            new AfterQueueExportJob(__DIR__ . '/Data/Disks/Local/queued-export.xlsx'),
        ]);

        $array = $this->readAsArray(__DIR__ . '/Data/Disks/Local/queued-export.xlsx', Excel::XLSX);
        $this->assertCount(100, $array);

        $this->assertEquals('test', Cache::get('test'));
    }
}

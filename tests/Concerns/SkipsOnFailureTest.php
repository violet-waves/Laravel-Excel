<?php

namespace VioletWaves\Excel\Tests\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use VioletWaves\Excel\Concerns\Importable;
use VioletWaves\Excel\Concerns\OnEachRow;
use VioletWaves\Excel\Concerns\SkipsFailures;
use VioletWaves\Excel\Concerns\SkipsOnFailure;
use VioletWaves\Excel\Concerns\ToCollection;
use VioletWaves\Excel\Concerns\ToModel;
use VioletWaves\Excel\Concerns\WithBatchInserts;
use VioletWaves\Excel\Concerns\WithValidation;
use VioletWaves\Excel\Row;
use VioletWaves\Excel\Tests\Data\Stubs\Database\User;
use VioletWaves\Excel\Tests\TestCase;
use VioletWaves\Excel\Validators\Failure;
use PHPUnit\Framework\Assert;

class SkipsOnFailureTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'testing']);
    }

    public function test_can_skip_on_error()
    {
        $import = new class implements ToModel, WithValidation, SkipsOnFailure
        {
            use Importable;

            public $failures = 0;

            /**
             * @param  array  $row
             * @return Model|null
             */
            public function model(array $row)
            {
                return new User([
                    'name'     => $row[0],
                    'email'    => $row[1],
                    'password' => 'secret',
                ]);
            }

            /**
             * @return array
             */
            public function rules(): array
            {
                return [
                    '1' => Rule::in(['patrick@maatwebsite.nl']),
                ];
            }

            /**
             * @param  Failure[]  $failures
             */
            public function onFailure(Failure ...$failures)
            {
                $failure = $failures[0];

                Assert::assertEquals(2, $failure->row());
                Assert::assertEquals('1', $failure->attribute());
                Assert::assertEquals(['The selected 1 is invalid.'], $failure->errors());
                Assert::assertEquals(['Taylor Otwell', 'taylor@laravel.com'], $failure->values());
                Assert::assertEquals(2, $failure->jsonSerialize()['row']);
                Assert::assertEquals('1', $failure->jsonSerialize()['attribute']);
                Assert::assertEquals(['The selected 1 is invalid.'], $failure->jsonSerialize()['errors']);
                Assert::assertEquals(['Taylor Otwell', 'taylor@laravel.com'], $failure->jsonSerialize()['values']);

                $this->failures += \count($failures);
            }
        };

        $import->import('import-users.xlsx');

        $this->assertEquals(1, $import->failures);

        // Shouldn't have rollbacked other imported rows.
        $this->assertDatabaseHas('users', [
            'email' => 'patrick@maatwebsite.nl',
        ]);

        // Should have skipped inserting
        $this->assertDatabaseMissing('users', [
            'email' => 'taylor@laravel.com',
        ]);
    }

    public function test_skips_only_failed_rows_in_batch()
    {
        $import = new class implements ToModel, WithValidation, WithBatchInserts, SkipsOnFailure
        {
            use Importable;

            public $failures = 0;

            /**
             * @param  array  $row
             * @return Model|null
             */
            public function model(array $row)
            {
                return new User([
                    'name'     => $row[0],
                    'email'    => $row[1],
                    'password' => 'secret',
                ]);
            }

            /**
             * @return array
             */
            public function rules(): array
            {
                return [
                    '1' => Rule::in(['patrick@maatwebsite.nl']),
                ];
            }

            /**
             * @param  Failure[]  $failures
             */
            public function onFailure(Failure ...$failures)
            {
                $failure = $failures[0];

                Assert::assertEquals(2, $failure->row());
                Assert::assertEquals('1', $failure->attribute());
                Assert::assertEquals(['The selected 1 is invalid.'], $failure->errors());

                $this->failures += \count($failures);
            }

            /**
             * @return int
             */
            public function batchSize(): int
            {
                return 100;
            }
        };

        $import->import('import-users.xlsx');

        $this->assertEquals(1, $import->failures);

        // Shouldn't have rollbacked/skipped the rest of the batch.
        $this->assertDatabaseHas('users', [
            'email' => 'patrick@maatwebsite.nl',
        ]);

        // Should have skipped inserting
        $this->assertDatabaseMissing('users', [
            'email' => 'taylor@laravel.com',
        ]);
    }

    public function test_can_skip_failures_and_collect_all_failures_at_the_end()
    {
        $import = new class implements ToModel, WithValidation, SkipsOnFailure
        {
            use Importable, SkipsFailures;

            /**
             * @param  array  $row
             * @return Model|null
             */
            public function model(array $row)
            {
                return new User([
                    'name'     => $row[0],
                    'email'    => $row[1],
                    'password' => 'secret',
                ]);
            }

            /**
             * @return array
             */
            public function rules(): array
            {
                return [
                    '1' => Rule::in(['patrick@maatwebsite.nl']),
                ];
            }
        };

        $import->import('import-users.xlsx');

        $this->assertCount(1, $import->failures());

        /** @var Failure $failure */
        $failure = $import->failures()->first();

        $this->assertEquals(2, $failure->row());
        $this->assertEquals('1', $failure->attribute());
        $this->assertEquals(['The selected 1 is invalid.'], $failure->errors());

        // Shouldn't have rollbacked other imported rows.
        $this->assertDatabaseHas('users', [
            'email' => 'patrick@maatwebsite.nl',
        ]);

        // Should have skipped inserting
        $this->assertDatabaseMissing('users', [
            'email' => 'taylor@laravel.com',
        ]);
    }

    public function test_can_validate_using_oneachrow_and_skipsonfailure()
    {
        $import = new class implements OnEachRow, WithValidation, SkipsOnFailure
        {
            use Importable, SkipsFailures;

            /**
             * @param  Row  $row
             * @return Model|null
             */
            public function onRow(Row $row)
            {
                $row = $row->toArray();

                return User::create([
                    'name'     => $row[0],
                    'email'    => $row[1],
                    'password' => 'secret',
                ]);
            }

            /**
             * @return array
             */
            public function rules(): array
            {
                return [
                    '1' => Rule::in(['patrick@maatwebsite.nl']),
                ];
            }
        };
        $this->assertEmpty(User::all());

        $import->import('import-users.xlsx');

        $this->assertCount(1, $import->failures());

        // Shouldn't have rollbacked other imported rows.
        $this->assertDatabaseHas('users', [
            'email' => 'patrick@maatwebsite.nl',
        ]);

        // Should have skipped inserting
        $this->assertDatabaseMissing('users', [
            'email' => 'taylor@laravel.com',
        ]);
    }

    public function test_can_validate_using_tocollection_and_skipsonfailure()
    {
        $import = new class implements ToCollection, WithValidation, SkipsOnFailure
        {
            use Importable, SkipsFailures;

            /**
             * @param  Row  $row
             * @return Model|null
             */
            public function collection(Collection $rows)
            {
                $rows = $rows->each(function ($row) {
                    return User::create([
                        'name'     => $row[0],
                        'email'    => $row[1],
                        'password' => 'secret',
                    ]);
                });
            }

            /**
             * @return array
             */
            public function rules(): array
            {
                return [
                    '1' => Rule::in(['patrick@maatwebsite.nl']),
                ];
            }
        };
        $this->assertEmpty(User::all());

        $import->import('import-users.xlsx');

        $this->assertCount(1, $import->failures());

        // Shouldn't have rollbacked other imported rows.
        $this->assertDatabaseHas('users', [
            'email' => 'patrick@maatwebsite.nl',
        ]);

        // Should have skipped inserting
        $this->assertDatabaseMissing('users', [
            'email' => 'taylor@laravel.com',
        ]);
    }
}

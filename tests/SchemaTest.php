<?php

namespace HarryGulliford\Firebird\Tests;

use HarryGulliford\Firebird\Tests\Support\MigrateDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaTest extends TestCase
{
    use MigrateDatabase;

    /** @test */
    public function it_has_table()
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertFalse(Schema::hasTable('foobar'));
    }

    /** @test */
    public function it_has_column()
    {
        $this->assertTrue(Schema::hasColumn('users', 'id'));
        $this->assertFalse(Schema::hasColumn('users', 'foobar'));
    }

    /** @test */
    public function it_has_columns()
    {
        $this->assertTrue(Schema::hasColumns('users', ['id', 'country']));
    }

    /** @test */
    public function it_throws_an_exception_for_creating_temporary_tables()
    {
        Schema::dropIfExists('foo');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This database driver does not support temporary tables.');

        $this->assertFalse(Schema::hasTable('foo'));

        Schema::create('foo', function (Blueprint $table) {
            $table->temporary();

            $table->string('bar');
        });

        $this->assertFalse(Schema::hasTable('foo'));
    }

    /** @test */
    public function it_can_drop_table()
    {
        DB::select('RECREATE TABLE "foobar" ("id" INTEGER NOT NULL)');

        $this->assertTrue(Schema::hasTable('foobar'));

        Schema::drop('foobar');

        $this->assertFalse(Schema::hasTable('foobar'));
    }

    /** @test */
    public function it_can_drop_table_if_exists()
    {
        DB::select('RECREATE TABLE "foobar" ("id" INTEGER NOT NULL)');

        $this->assertTrue(Schema::hasTable('foobar'));

        Schema::dropIfExists('foobar');

        $this->assertFalse(Schema::hasTable('foobar'));

        // Run again to check exists = false.

        Schema::dropIfExists('foobar');

        $this->assertFalse(Schema::hasTable('foobar'));
    }
}

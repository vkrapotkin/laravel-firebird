<?php

namespace Firebird\Tests;

use Firebird\Tests\Support\MigrateDatabase;
use Firebird\Tests\TestCase;
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
        $this->assertFalse(Schema::hasColumns('users', ['id', 'foobar']));
    }
}

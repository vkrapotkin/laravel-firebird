<?php

use Vkrapotkin\Firebird\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(TestCase::class);

it('has table', function () {
    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('foo'))->toBeFalse();
});

it('has column', function () {
    expect(Schema::hasColumn('users', 'id'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'foo'))->toBeFalse();
});

it('has columns', function () {
    expect(Schema::hasColumns('users', ['id', 'country']))->toBeTrue()
        ->and(Schema::hasColumns('users', ['id', 'foo']))->toBeFalse();
});

it('can create a table', function () {
    Schema::dropIfExists('foo');

    expect(Schema::hasTable('foo'))->toBeFalse();

    Schema::create('foo', function (Blueprint $table) {
        $table->string('bar');
    });

    expect(Schema::hasTable('foo'))->toBeTrue();

    // Clean up...
    Schema::drop('foo');
});

it('throws an exception for creating temporary tables', function () {
    Schema::dropIfExists('foo');
    expect(Schema::hasTable('foo'))->toBeFalse()
        ->and(function () {
            Schema::create('foo', function (Blueprint $table) {
                $table->temporary();

                $table->string('bar');
            });
        })->toThrow(LogicException::class, 'This database driver does not support temporary tables.')
        ->and(Schema::hasTable('foo'))->toBeFalse();

});

it('can drop table', function () {
    DB::select('RECREATE TABLE "foo" ("id" INTEGER NOT NULL)');

    expect(Schema::hasTable('foo'))->toBeTrue();

    Schema::drop('foo');

    expect(Schema::hasTable('foo'))->toBeFalse();
});

it('can drop table if exists', function () {
    DB::select('RECREATE TABLE "foo" ("id" INTEGER NOT NULL)');

    expect(Schema::hasTable('foo'))->toBeTrue();

    Schema::dropIfExists('foo');

    expect(Schema::hasTable('foo'))->toBeFalse();

    // Run again to check exists = false.
    Schema::dropIfExists('foo');

    expect(Schema::hasTable('foo'))->toBeFalse();
});

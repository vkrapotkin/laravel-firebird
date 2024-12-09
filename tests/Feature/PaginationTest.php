<?php

use Vkrapotkin\Firebird\Tests\Support\Models\Order;
use Vkrapotkin\Firebird\Tests\TestCase;
use Illuminate\Support\Facades\DB;

uses(TestCase::class);

it('can paginate results', function () {
    Order::factory()->count(10)->create(['price' => 50]);

    $paginator = DB::table('orders')->paginate(3, ['id', 'price'], 'orders');
    expect($paginator)->toHaveCount(3)
        ->and($paginator->total())->toEqual(10)
        ->and($paginator->hasMorePages())->toBeTrue();

    $paginator = DB::table('orders')->paginate(3, ['id', 'price'], 'orders', 2);
    expect($paginator)->toHaveCount(3)
        ->and($paginator->total())->toEqual(10)
        ->and($paginator->hasMorePages())->toBeTrue();
});

it('can simple paginate results', function () {
    Order::factory()->count(10)->create(['price' => 50]);

    $paginator = DB::table('orders')->simplePaginate(3);
    expect($paginator)->toHaveCount(3)
        ->and($paginator->hasMorePages())->toBeTrue();

    $paginator = DB::table('orders')->simplePaginate(3, ['id', 'price'], 'orders', 2);
    expect($paginator)->toHaveCount(3)
        ->and($paginator->hasMorePages())->toBeTrue();
});

it('can cursor paginate results', function () {
    Order::factory()->count(10)->create(['price' => 50]);

    $paginator = DB::table('orders')->orderBy('id')->cursorPaginate(3, ['id', 'price'], 'orders');
    expect($paginator)->toHaveCount(3);

    $paginator = DB::table('orders')->orderBy('id')->cursorPaginate(3, ['id', 'price'], 'orders', $paginator->nextCursor());
    expect($paginator)->toHaveCount(3);
});

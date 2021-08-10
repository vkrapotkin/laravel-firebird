<?php

namespace Firebird\Tests;

use Firebird\Tests\Support\Factories\OrderFactory;
use Firebird\Tests\Support\Factories\UserFactory;
use Firebird\Tests\Support\Models\Order;
use Firebird\Tests\Support\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QueryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->dropTables();
        $this->createTables();
    }

    public function tearDown(): void
    {
        $this->dropTables();

        // Reset the static ids on the users table, as firebird does not support
        // auto-incrementing ids.
        UserFactory::$id = 1;
        OrderFactory::$id = 1;

        parent::tearDown();
    }

    public function createTables(): void
    {
        DB::select('CREATE TABLE "users" ("id" INTEGER NOT NULL, "name" VARCHAR(255) NOT NULL, "email" VARCHAR(255) NOT NULL, "city" VARCHAR(255), "state" VARCHAR(255), "post_code" VARCHAR(255), "country" VARCHAR(255), "created_at" TIMESTAMP, "updated_at" TIMESTAMP, "deleted_at" TIMESTAMP)');
        DB::select('ALTER TABLE "users" ADD PRIMARY KEY ("id")');

        DB::select('CREATE TABLE "orders" ("id" INTEGER NOT NULL, "user_id" INTEGER NOT NULL, "name" VARCHAR(255) NOT NULL, "price" INTEGER NOT NULL, "quantity" INTEGER NOT NULL, "created_at" TIMESTAMP, "updated_at" TIMESTAMP, "deleted_at" TIMESTAMP)');
        DB::select('ALTER TABLE "orders" ADD CONSTRAINT orders_user_id_foreign FOREIGN KEY ("user_id") REFERENCES "users" ("id")');
        DB::select('ALTER TABLE "orders" ADD PRIMARY KEY ("id")');
    }

    public function dropTables(): void
    {
        try {
            DB::select('drop table "orders"');
        } catch (QueryException $e) {
            // ...
        }

        try {
            DB::select('drop table "users"');
        } catch (QueryException $e) {
            // ...
        }
    }

    /** @test */
    public function it_has_the_correct_connection()
    {
        $this->assertEquals('firebird', DB::getDefaultConnection());
    }

    /** @test */
    public function it_can_get()
    {
        Order::factory()->count(3)->create();

        $users = DB::table('users')->get();

        $this->assertCount(3, $users);
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertIsObject($users->first());
        $this->assertIsArray($users->toArray());

        $orders = DB::table('orders')->get();

        $this->assertCount(3, $orders);
        $this->assertInstanceOf(Collection::class, $orders);
        $this->assertIsObject($orders->first());
        $this->assertIsArray($orders->toArray());
    }

    /** @test */
    public function it_can_select()
    {
        User::factory()->create([
            'name' => 'Anna',
            'city' => 'Sydney',
            'country' => 'Australia',
        ]);

        $result = DB::table('users')
            ->select(['name', 'city', 'country'])
            ->first();

        $this->assertCount(3, (array) $result);

        $this->assertObjectHasAttribute('name', $result);
        $this->assertObjectHasAttribute('city', $result);
        $this->assertObjectHasAttribute('country', $result);

        $this->assertEquals('Anna', $result->name);
        $this->assertEquals('Sydney', $result->city);
        $this->assertEquals('Australia', $result->country);
    }

    /** @test */
    public function it_can_select_with_aliases()
    {
        User::factory()->create([
            'name' => 'Anna',
            'city' => 'Sydney',
            'country' => 'Australia',
        ]);

        $result = DB::table('users')
            ->select([
                'name as USER_NAME',
                'city as user_city',
                'country as User_Country',
            ])
            ->first();

        $this->assertCount(3, (array) $result);

        $this->assertObjectHasAttribute('USER_NAME', $result);
        $this->assertObjectHasAttribute('user_city', $result);
        $this->assertObjectHasAttribute('User_Country', $result);

        $this->assertEquals('Anna', $result->USER_NAME);
        $this->assertEquals('Sydney', $result->user_city);
        $this->assertEquals('Australia', $result->User_Country);
    }

    /** @test */
    public function it_can_select_distinct()
    {
        Order::factory()->count(1)->create(['price' => 10]);
        Order::factory()->count(10)->create(['price' => 50]);
        Order::factory()->count(5)->create(['price' => 100]);

        $results = DB::table('orders')->select('price')->distinct()->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_can_filter_where_with_results()
    {
        User::factory()->count(5)->create(['name' => 'Frank']);
        User::factory()->count(2)->create(['name' => 'Inigo']);
        User::factory()->count(7)->create(['name' => 'Ashley']);

        $results = DB::table('users')
            ->where('name', 'Frank')
            ->get();

        $this->assertCount(5, $results);
        $this->assertCount(1, $results->pluck('name')->unique());
        $this->assertEquals('Frank', $results->random()->name);
    }

    /** @test */
    public function it_can_filter_where_without_results()
    {
        User::factory()->count(25)->create();

        $results = DB::table('users')
            ->where('id', 26)
            ->get();

        $this->assertCount(0, $results);
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertEquals([], $results->toArray());
        $this->assertNull($results->first());
    }

    /** @test */
    public function it_can_filter_where_gt()
    {
        Order::factory()
            ->count(8)
            ->state(new Sequence(
                ['price' => 5],
                ['price' => 25],
                ['price' => 50],
                ['price' => 99],
                ['price' => 100],
                ['price' => 101],
                ['price' => 150],
                ['price' => 200],
            ))
            ->create();

        $results = DB::table('orders')
            ->where('price', '>', 100)
            ->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_can_filter_where_gte()
    {
        Order::factory()
            ->count(8)
            ->state(new Sequence(
                ['price' => 5],
                ['price' => 25],
                ['price' => 50],
                ['price' => 99],
                ['price' => 100],
                ['price' => 101],
                ['price' => 150],
                ['price' => 200],
            ))
            ->create();

        $results = DB::table('orders')
            ->where('price', '>=', 100)
            ->get();

        $this->assertCount(4, $results);
    }

    /** @test */
    public function it_can_filter_where_lt()
    {
        Order::factory()
            ->count(8)
            ->state(new Sequence(
                ['price' => 5],
                ['price' => 25],
                ['price' => 50],
                ['price' => 99],
                ['price' => 100],
                ['price' => 101],
                ['price' => 150],
                ['price' => 200],
            ))
            ->create();

        $results = DB::table('orders')
            ->where('price', '<', 100)
            ->get();

        $this->assertCount(4, $results);
    }

    /** @test */
    public function it_can_filter_where_lte()
    {
        Order::factory()
            ->count(8)
            ->state(new Sequence(
                ['price' => 5],
                ['price' => 25],
                ['price' => 50],
                ['price' => 99],
                ['price' => 100],
                ['price' => 101],
                ['price' => 150],
                ['price' => 200],
            ))
            ->create();

        $results = DB::table('orders')
            ->where('price', '<=', 100)
            ->get();

        $this->assertCount(5, $results);
    }

    /** @test */
    public function it_can_filter_where_not_equal()
    {
        Order::factory()
            ->count(8)
            ->state(new Sequence(
                ['price' => 5],
                ['price' => 25],
                ['price' => 50],
                ['price' => 99],
                ['price' => 100],
                ['price' => 101],
                ['price' => 150],
                ['price' => 200],
            ))
            ->create();

        $results = DB::table('orders')
            ->where('price', '!=', 100)
            ->get();

        $this->assertCount(7, $results);

        $results = DB::table('orders')
            ->where('price', '<>', 100)
            ->get();

        $this->assertCount(7, $results);
    }

    /** @test */
    public function it_can_filter_where_like()
    {
        Order::factory()->create(['name' => 'Pants Small']);
        Order::factory()->create(['name' => 'Pants Large']);
        Order::factory()->create(['name' => 'Shirt Small']);
        Order::factory()->create(['name' => 'Shirt Medium']);
        Order::factory()->create(['name' => 'Shirt Large']);

        $results = DB::table('orders')
            ->where('name', 'like', 'Shirt%')
            ->get();

        $this->assertCount(3, $results);

        $results = DB::table('orders')
            ->where('name', 'like', '%Small')
            ->get();

        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_can_filter_where_not_like()
    {
        Order::factory()->create(['name' => 'Pants Small']);
        Order::factory()->create(['name' => 'Pants Large']);
        Order::factory()->create(['name' => 'Shirt Small']);
        Order::factory()->create(['name' => 'Shirt Medium']);
        Order::factory()->create(['name' => 'Shirt Large']);

        $results = DB::table('orders')
            ->where('name', 'not like', 'Shirt%')
            ->get();

        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_can_filter_where_array()
    {
        Order::factory()->create(['name' => 'Pants Small', 'price' => 60]);
        Order::factory()->create(['name' => 'Pants Large', 'price' => 80]);
        Order::factory()->create(['name' => 'Shirt Small', 'price' => 50]);
        Order::factory()->create(['name' => 'Shirt Medium', 'price' => 60]);
        Order::factory()->create(['name' => 'Shirt Large', 'price' => 70]);

        $results = DB::table('orders')
            ->where([
                ['price', '>=', 60],
                ['name', 'like', '%Large%'],
                ['name', 'not like', '%Pants%'],
            ])
            ->get();

        $this->assertCount(1, $results);
    }

    /** @test */
    public function it_can_filter_or_where()
    {
        Order::factory()
            ->count(8)
            ->state(new Sequence(
                ['price' => 5],
                ['price' => 25],
                ['price' => 50],
                ['price' => 99],
                ['price' => 100],
                ['price' => 100],
                ['price' => 150],
                ['price' => 200],
            ))
            ->create();

        $results = DB::table('orders')
            ->where('price', 100)
            ->orWhere('price', 5)
            ->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_can_filter_grouped_or_where()
    {
        Order::factory()->count(2)->create(['price' => 100]);
        Order::factory()->create(['price' => 25, 'quantity' => 1]);
        Order::factory()->create(['price' => 30, 'quantity' => 3]);

        $results = DB::table('orders')
            ->where('price', '>=', 100)
            ->orWhere(function ($query) {
                $query->where('price', '>', 10)
                    ->where('quantity', '>', 2);
            })
            ->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_can_filter_where_in()
    {
        Order::factory()->count(1)->create(['price' => 75]);
        Order::factory()->count(3)->create(['price' => 100]);
        Order::factory()->count(5)->create(['price' => 125]);

        $results = DB::table('orders')
            ->whereIn('price', [100, 125])
            ->get();

        $this->assertCount(8, $results);
    }

    /** @test */
    public function it_can_filter_where_not_in()
    {
        Order::factory()->count(1)->create(['price' => 75]);
        Order::factory()->count(3)->create(['price' => 100]);
        Order::factory()->count(5)->create(['price' => 125]);

        $results = DB::table('orders')
            ->whereNotIn('price', [100, 125])
            ->get();

        $this->assertCount(1, $results);
    }

    /** @test */
    public function it_can_filter_where_between()
    {
        Order::factory()->create(['price' => 10]);
        Order::factory()->create(['price' => 20]);
        Order::factory()->create(['price' => 30]);
        Order::factory()->create(['price' => 40]);
        Order::factory()->create(['price' => 50]);

        $results = DB::table('orders')
            ->whereBetween('price', [30, 60])
            ->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_can_filter_where_not_between()
    {
        Order::factory()->create(['price' => 10]);
        Order::factory()->create(['price' => 20]);
        Order::factory()->create(['price' => 30]);
        Order::factory()->create(['price' => 40]);
        Order::factory()->create(['price' => 50]);

        $results = DB::table('orders')
            ->whereNotBetween('price', [30, 60])
            ->get();

        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_can_filter_where_null()
    {
        Order::factory()->count(10)->create();

        $results = DB::table('orders')
            ->whereNull('deleted_at')
            ->get();

        $this->assertCount(10, $results);
    }

    /** @test */
    public function it_can_filter_where_not_null()
    {
        Order::factory()->count(10)->create();

        $results = DB::table('orders')
            ->whereNotNull('created_at')
            ->get();

        $this->assertCount(10, $results);
    }

    /** @test */
    public function it_can_filter_where_date()
    {
        $this->markTestSkipped('The necessary grammar for whereDate() has not been implemented.');

        $results = DB::table('orders')
            ->whereDate('created_at', now())
            ->get();

        $this->assertCount(10, $results);
    }

    /** @test */
    public function it_can_filter_where_time()
    {
        $this->markTestSkipped('The necessary grammar for whereTime() has not been implemented.');

        $results = DB::table('orders')
            ->whereTime('created_at', now())
            ->get();

        $this->assertCount(10, $results);
    }

    /** @test */
    public function it_can_filter_where_day()
    {
        Order::factory()->count(3)->create(['created_at' => now()]);
        Order::factory()->count(5)->create(['created_at' => now()->subDay()]);

        $results = DB::table('orders')
            ->whereDay('created_at', now())
            ->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_can_filter_where_month()
    {
        Order::factory()->count(3)->create(['created_at' => now()]);
        Order::factory()->count(5)->create(['created_at' => now()->subMonth()]);

        $results = DB::table('orders')
            ->whereMonth('created_at', now())
            ->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_can_filter_where_year()
    {
        Order::factory()->count(3)->create(['created_at' => now()]);
        Order::factory()->count(5)->create(['created_at' => now()->subYear()]);

        $results = DB::table('orders')
            ->whereYear('created_at', now())
            ->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_can_filter_where_exists()
    {
        Order::factory()->count(2)->create(['price' => 120]);
        Order::factory()->count(3)->create(['price' => 80]);

        $results = DB::table('users')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('orders')
                    ->whereColumn('orders.user_id', 'users.id')
                    ->where('price', '>', 100);
            })
            ->get();

        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_can_filter_subquery_where()
    {
        Order::factory()->count(2)->create(['price' => 100]);
        Order::factory()->count(3)->create(['price' => 80]);
        Order::factory()->count(6)->create(['price' => 120]);

        $results = DB::table('users')
            ->where(function ($query) {
                $query->select('price')
                    ->from('orders')
                    ->whereColumn('orders.user_id', 'users.id')
                    ->limit(1);
            }, 100)
            ->get();

        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_can_order_by_asc()
    {
        Order::factory()->create(['created_at' => now()->subMonths(2)]);
        Order::factory()->create(['created_at' => now()->subMonths(1)]);
        Order::factory()->create(['created_at' => now()]);

        $results = DB::table('users')->orderBy('id')->get();

        $this->assertEquals(1, $results->first()->id);
        $this->assertEquals(3, $results->last()->id);
    }

    /** @test */
    public function it_can_order_by_desc()
    {
        Order::factory()->create(['created_at' => now()->subMonths(2)]);
        Order::factory()->create(['created_at' => now()->subMonths(1)]);
        Order::factory()->create(['created_at' => now()]);

        $results = DB::table('users')->orderByDesc('id')->get();

        $this->assertEquals(3, $results->first()->id);
        $this->assertEquals(1, $results->last()->id);
    }

    /** @test */
    public function it_can_order_latest()
    {
        Order::factory()->create(['created_at' => now()->subMonths(2)]);
        Order::factory()->create(['created_at' => now()->subMonths(1)]);
        Order::factory()->create(['created_at' => now()]);

        $results = DB::table('users')->latest()->get();

        $this->assertEquals(3, $results->first()->id);
        $this->assertEquals(1, $results->last()->id);
    }

    /** @test */
    public function it_can_order_oldest()
    {
        Order::factory()->create(['created_at' => now()->subMonths(2)]);
        Order::factory()->create(['created_at' => now()->subMonths(1)]);
        Order::factory()->create(['created_at' => now()]);

        $results = DB::table('users')->oldest()->get();

        $this->assertEquals(1, $results->first()->id);
        $this->assertEquals(3, $results->last()->id);
    }

    /** @test */
    public function it_can_return_random_order()
    {
        User::factory()->count(25)->create();

        $resultsA = DB::table('users')->inRandomOrder()->get();
        $resultsB = DB::table('users')->inRandomOrder()->get();
        $resultsC = DB::table('users')->inRandomOrder()->get();

        $this->assertNotEquals($resultsA, $resultsB);
        $this->assertNotEquals($resultsA, $resultsC);
        $this->assertNotEquals($resultsB, $resultsC);
    }

    /** @test */
    public function it_can_remove_existing_orderings()
    {
        Order::factory()->count(10)->create();

        $query = DB::table('users')->orderByDesc('id');

        $results = $query->get();

        $this->assertEquals(10, $results->first()->id);
        $this->assertEquals(1, $results->last()->id);

        $results = $query->reorder()->get();

        $this->assertEquals(1, $results->first()->id);
        $this->assertEquals(10, $results->last()->id);
    }

    /** @test */
    public function it_can_pluck()
    {
        Order::factory()->count(10)->create();

        $results = DB::table('users')->pluck('id');

        $this->assertCount(10, $results);
        foreach (range(1, 10) as $expectedId) {
            $this->assertContains($expectedId, $results);
        }
    }

    /** @test */
    public function it_can_count()
    {
        Order::factory()->count(10)->create();

        $count = DB::table('orders')->count();

        $this->assertEquals(10, $count);
    }

    /** @test */
    public function it_can_aggregate_max()
    {
        Order::factory()
            ->count(5)
            ->state(new Sequence(
                ['price' => 68],
                ['price' => 92],
                ['price' => 12],
                ['price' => 37],
                ['price' => 54],
            ))
            ->create();

        $price = DB::table('orders')->max('price');

        $this->assertEquals(92, $price);
    }

    /** @test */
    public function it_can_aggregate_min()
    {
        Order::factory()
            ->count(5)
            ->state(new Sequence(
                ['price' => 68],
                ['price' => 92],
                ['price' => 12],
                ['price' => 37],
                ['price' => 54],
            ))
            ->create();

        $price = DB::table('orders')->min('price');

        $this->assertEquals(12, $price);
    }

    /** @test */
    public function it_can_aggregate_average()
    {
        Order::factory()
            ->count(5)
            ->state(new Sequence(
                ['price' => 68],
                ['price' => 92],
                ['price' => 12],
                ['price' => 37],
                ['price' => 54],
            ))
            ->create();

        $price = DB::table('orders')->avg('price');

        $this->assertEquals((int) 52.6, $price);
    }

    /** @test */
    public function it_can_aggregate_sum()
    {
        Order::factory()
            ->count(5)
            ->state(new Sequence(
                ['price' => 68],
                ['price' => 92],
                ['price' => 12],
                ['price' => 37],
                ['price' => 54],
            ))
            ->create();

        $price = DB::table('orders')->sum('price');

        $this->assertEquals(263, $price);
    }

    /** @test */
    public function it_can_check_exists()
    {
        User::factory()->create();

        $this->assertTrue(DB::table('users')->where('id', 1)->exists());
        $this->assertFalse(DB::table('users')->where('id', null)->exists());
    }

    /** @test */
    public function it_can_execute_raw_expressions()
    {
        Order::factory()
            ->count(6)
            ->state(new Sequence(
                ['price' => 50],
                ['price' => 50],
                ['price' => 70],
                ['price' => 90],
                ['price' => 90],
                ['price' => 90],
            ))
            ->create();

        $results = DB::table('orders')
            ->select(DB::raw('count(*) as "price_count", "price"'))
            ->groupBy('price')
            ->get();

        $this->assertCount(3, $results);
        $this->assertEquals(2, $results->where('price', 50)->first()->price_count);
        $this->assertEquals(1, $results->where('price', 70)->first()->price_count);
        $this->assertEquals(3, $results->where('price', 90)->first()->price_count);
    }

    /** @test */
    public function it_can_execute_raw_select()
    {
        Order::factory()
            ->count(3)
            ->state(new Sequence(
                ['price' => 50],
                ['price' => 70],
                ['price' => 90],
            ))
            ->create();

        $results = DB::table('orders')
            ->selectRaw('"price", "price" * 1.1 as "price_with_tax"')
            ->get();

        foreach ($results as $result) {
            $this->assertEquals($result->price * 1.1, $result->price_with_tax);
        }
    }

    /** @test */
    public function it_can_execute_raw_where()
    {
        User::factory()->count(3)->create();
        User::factory()->create(['city' => null]);

        $results = DB::table('users')
            ->whereRaw('"city" is not null')
            ->get();

        $this->assertCount(3, $results);
    }

    /** @test */
    public function it_can_execute_raw_order_by()
    {
        Order::factory()
            ->count(3)
            ->state(new Sequence(
                ['price' => 50, 'quantity' => 10],
                ['price' => 70, 'quantity' => 5],
                ['price' => 90, 'quantity' => 1],
            ))
            ->create();

        $results = DB::table('orders')
            ->orderByRaw('"price" * "quantity" desc')
            ->get();

        $max = $results->first()->price * $results->first()->quantity;
        $min = $results->last()->price * $results->last()->quantity;

        $this->assertEquals(500, $max);
        $this->assertEquals(90, $min);
    }

    /** @test */
    public function it_can_add_inner_join()
    {
        Order::factory()->count(10)->create();

        $results = DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->get();

        $this->assertCount(10, $results);
        $this->assertObjectHasAttribute('name', $results->first());
        $this->assertObjectHasAttribute('email', $results->first());
        $this->assertObjectHasAttribute('state', $results->first());
        $this->assertObjectHasAttribute('price', $results->first());
        $this->assertObjectHasAttribute('quantity', $results->first());
    }

    /** @test */
    public function it_can_add_inner_join_where()
    {
        Order::factory()->count(2)->create(['price' => 100]);
        Order::factory()->count(3)->create(['price' => 50]);

        $results = DB::table('orders')
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'orders.user_id')
                    ->where('orders.price', 100);
            })
            ->get();

        $this->assertCount(2, $results);
        $this->assertEquals(100, $results->first()->price);

        $this->assertObjectHasAttribute('name', $results->first());
        $this->assertObjectHasAttribute('email', $results->first());
        $this->assertObjectHasAttribute('state', $results->first());
        $this->assertObjectHasAttribute('price', $results->first());
        $this->assertObjectHasAttribute('quantity', $results->first());
    }

    /** @test */
    public function it_can_add_left_join()
    {
        Order::factory()->count(2)->create(['price' => 100]);
        Order::factory()->count(3)->create(['price' => 50]);

        $results = DB::table('orders')
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'orders.user_id')
                    ->where('orders.price', 100);
            })
            ->get();

        $this->assertCount(5, $results);
        $this->assertCount(0, $results->whereNull('price'));
        $this->assertCount(3, $results->whereNull('email'));
    }

    /** @test */
    public function it_can_add_right_join()
    {
        Order::factory()->count(2)->create(['price' => 100]);
        Order::factory()->count(3)->create(['price' => 50]);

        $results = DB::table('orders')
            ->rightJoin('users', function ($join) {
                $join->on('users.id', '=', 'orders.user_id')
                    ->where('orders.price', 100);
            })
            ->get();

        $this->assertCount(5, $results);
        $this->assertCount(3, $results->whereNull('price'));
        $this->assertCount(0, $results->whereNull('email'));
    }

    /** @test */
    public function it_can_add_subquery_join()
    {
        Order::factory()->create();

        $latestOrder = DB::table('orders')
                   ->select('user_id', DB::raw('MAX("created_at") as "last_order_created_at"'))
                   ->groupBy('user_id');

        $user = DB::table('users')
                ->joinSub($latestOrder, 'latest_order', function ($join) {
                    $join->on('users.id', '=', 'latest_order.user_id');
                })->first();

        $this->assertNotNull($user->last_order_created_at);
    }

    /** @test */
    public function it_can_union_queries()
    {
        Order::factory()
            ->count(5)
            ->state(new Sequence(
                ['price' => 110],
                ['price' => 100],
                ['price' => 100],
                ['price' => 80],
                ['price' => 16],
            ))
            ->create();

        $first = DB::table('orders')
            ->where('price', 100);

        $orders = DB::table('orders')
                    ->where('price', 16)
                    ->union($first)
                    ->get();

        $this->assertCount(3, $orders);
    }

    /** @test */
    public function it_can_group_having()
    {
        User::factory()->count(5)->create(['country' => 'Australia']);
        User::factory()->count(3)->create(['country' => 'New Zealand']);
        User::factory()->count(2)->create(['country' => 'England']);

        $results = DB::table('users')
            ->selectRaw('count("id") as "count", "country"')
            ->groupBy('country')
            ->having('country', '!=', 'England')
            ->get();

        $this->assertCount(2, $results);
        $results = $results->mapWithKeys(fn ($result) => [$result->country => $result->count]);
        $this->assertEquals(5, $results['Australia']);
        $this->assertEquals(3, $results['New Zealand']);
    }

    /** @test */
    public function it_can_group_having_raw()
    {
        User::factory()->count(5)->create(['country' => 'Australia']);
        User::factory()->count(3)->create(['country' => 'New Zealand']);
        User::factory()->count(2)->create(['country' => 'England']);

        $results = DB::table('users')
            ->selectRaw('count("id") as "count", "country"')
            ->groupBy('country')
            ->havingRaw('count("id") > 2')
            ->get();

        $this->assertCount(2, $results);
        $results = $results->mapWithKeys(fn ($result) => [$result->country => $result->count]);
        $this->assertEquals(5, $results['Australia']);
        $this->assertEquals(3, $results['New Zealand']);
    }
}

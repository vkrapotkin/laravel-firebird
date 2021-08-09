<?php

namespace Firebird\Tests;

use Firebird\Tests\Support\Factories\OrderFactory;
use Firebird\Tests\Support\Factories\UserFactory;
use Firebird\Tests\Support\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QueryTest extends TestCase
{
    public static array $productPrices = [5, 16, 16, 32, 57, 81, 100, 100, 106, 255];

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

    public function seedTables(): void
    {
        User::factory()
            ->hasOrders(1, fn ($attributes) => [
                'price' => self::$productPrices[$attributes['id'] - 1],
            ])
            ->count(10)
            ->create();
    }

    /** @test */
    public function it_has_the_correct_connection()
    {
        $this->seedTables();

        $this->assertEquals('firebird', DB::getDefaultConnection());
    }

    /** @test */
    public function it_can_get()
    {
        $this->seedTables();

        $users = DB::table('users')->get();

        $this->assertCount(10, $users);
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertIsObject($users->first());
        $this->assertIsArray($users->toArray());

        $orders = DB::table('orders')->get();

        $this->assertCount(10, $orders);
        $this->assertInstanceOf(Collection::class, $orders);
        $this->assertIsObject($orders->first());
        $this->assertIsArray($orders->toArray());
    }

    /** @test */
    public function it_can_select()
    {
        $this->seedTables();

        $results = DB::table('users')
            ->select(['name', 'city', 'country'])
            ->get();

        $result = $results->random();

        $this->assertCount(3, (array) $result);
        $this->assertObjectHasAttribute('name', $result);
        $this->assertObjectHasAttribute('city', $result);
        $this->assertObjectHasAttribute('country', $result);
    }

    /** @test */
    public function it_can_select_with_aliases()
    {
        $this->seedTables();

        $results = DB::table('users')
            ->select([
                'name as USER_NAME',
                'city as user_city',
                'country as User_Country',
            ])
            ->get();

        $result = $results->random();

        $this->assertCount(3, (array) $result);
        $this->assertObjectHasAttribute('USER_NAME', $result);
        $this->assertObjectHasAttribute('user_city', $result);
        $this->assertObjectHasAttribute('User_Country', $result);
    }

    /** @test */
    public function it_can_select_distinct()
    {
        $this->seedTables();

        $results = DB::table('orders')->select('price')->distinct()->get();

        $uniquePricesCount = count(array_unique(self::$productPrices));
        $this->assertCount($uniquePricesCount, $results);
    }

    /** @test */
    public function it_can_filter_where_with_results()
    {
        $this->seedTables();

        $results = DB::table('users')
            ->where('id', 5)
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals(5, $results->first()->id);
    }

    /** @test */
    public function it_can_filter_where_without_results()
    {
        $this->seedTables();

        $results = DB::table('users')
            ->where('id', 2147483647)
            ->get();

        $this->assertCount(0, $results);
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertEquals([], $results->toArray());
        $this->assertNull($results->first());
    }

    /** @test */
    public function it_can_filter_where_in()
    {
        $this->seedTables();

        $results = DB::table('users')
            ->whereIn('id', [2, 5])
            ->get();

        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_can_order_by_asc()
    {
        $this->seedTables();

        $results = DB::table('users')->orderBy('id')->get();

        $this->assertEquals(1, $results->first()->id);
        $this->assertEquals(10, $results->last()->id);
    }

    /** @test */
    public function it_can_order_by_desc()
    {
        $this->seedTables();

        $results = DB::table('users')->orderByDesc('id')->get();

        $this->assertEquals(10, $results->first()->id);
        $this->assertEquals(1, $results->last()->id);
    }

    /** @test */
    public function it_can_pluck()
    {
        $this->seedTables();

        $results = DB::table('users')->pluck('id');

        $this->assertCount(10, $results);
        foreach (range(1, 10) as $expectedId) {
            $this->assertContains($expectedId, $results);
        }
    }

    /** @test */
    public function it_can_count()
    {
        $this->seedTables();

        $count = DB::table('orders')->count();

        $this->assertEquals(10, $count);
    }

    /** @test */
    public function it_can_aggregate_max()
    {
        $this->seedTables();

        $price = DB::table('orders')->max('price');

        $this->assertEquals(max(self::$productPrices), $price);
    }

    /** @test */
    public function it_can_aggregate_min()
    {
        $this->seedTables();

        $price = DB::table('orders')->min('price');

        $this->assertEquals(min(self::$productPrices), $price);
    }

    /** @test */
    public function it_can_aggregate_average()
    {
        $this->seedTables();

        $price = DB::table('orders')->avg('price');

        $expectedAverage = array_sum(self::$productPrices) / count(array_filter(self::$productPrices));
        $this->assertEquals((int) $expectedAverage, $price);
    }

    /** @test */
    public function it_can_aggregate_sum()
    {
        $this->seedTables();

        $price = DB::table('orders')->sum('price');

        $this->assertEquals(array_sum(self::$productPrices), $price);
    }

    /** @test */
    public function it_can_check_exists()
    {
        $this->seedTables();

        $this->assertTrue(DB::table('users')->where('id', 1)->exists());
        $this->assertFalse(DB::table('users')->where('id', null)->exists());
    }

    /** @test */
    public function it_can_execute_raw_expressions()
    {
        $this->seedTables();

        $results = DB::table('orders')
            ->select(DB::raw('count(*) as "price_count", "price"'))
            ->groupBy('price')
            ->get();

        $productPricesValueCount = array_count_values(self::$productPrices);

        $this->assertCount(count($productPricesValueCount), $results);

        foreach ($results as $result) {
            $this->assertEquals($productPricesValueCount[$result->price], $result->price_count);
        }
    }

    /** @test */
    public function it_can_execute_raw_select()
    {
        $this->seedTables();

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
        $this->seedTables();

        $results = DB::table('orders')
            ->whereRaw('"name" is not null')
            ->get();

        $this->assertCount(10, $results);
    }

    /** @test */
    public function it_can_execute_raw_order_by()
    {
        $this->seedTables();

        $results = DB::table('orders')
            ->orderByRaw('"price" * "quantity" desc')
            ->get();

        $max = $results->first()->price * $results->first()->quantity;
        $min = $results->last()->price * $results->last()->quantity;

        $this->assertTrue($max > $min);
    }

    /** @test */
    public function it_can_add_inner_join()
    {
        $this->seedTables();

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
        $this->seedTables();

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
        $this->seedTables();

        $results = DB::table('orders')
            ->leftJoin('users', function ($join) {
                $join->on('users.id', '=', 'orders.user_id')
                    ->where('orders.price', 100);
            })
            ->get();

        $this->assertCount(10, $results);
        $this->assertCount(0, $results->whereNull('price'));
        $this->assertCount(8, $results->whereNull('email'));
    }

    /** @test */
    public function it_can_add_right_join()
    {
        $this->seedTables();

        $results = DB::table('orders')
            ->rightJoin('users', function ($join) {
                $join->on('users.id', '=', 'orders.user_id')
                    ->where('orders.price', 100);
            })
            ->get();

        $this->assertCount(10, $results);
        $this->assertCount(8, $results->whereNull('price'));
        $this->assertCount(0, $results->whereNull('email'));
    }

    /** @test */
    public function it_can_add_subquery_join()
    {
        $this->seedTables();

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
        $this->seedTables();

        $first = DB::table('orders')
            ->where('price', 100);

        $orders = DB::table('orders')
                    ->where('price', 16)
                    ->union($first)
                    ->get();

        $this->assertCount(4, $orders);
    }
}

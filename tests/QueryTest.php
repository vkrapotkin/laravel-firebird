<?php

namespace Firebird\Tests;

use Faker\Factory as Faker;
use Firebird\Schema\Grammars\FirebirdGrammar;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QueryTest extends TestCase
{
    public static array $productPrices = [5, 16, 16, 32, 57, 81, 100, 100, 106, 255];

    public function setUp(): void
    {
        parent::setUp();

        try {
            DB::select('drop table "orders"');
            DB::select('drop table "users"');
        } catch (QueryException $e) {
            // ...
        }

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->integer('id')->primary();
        $blueprint->string('name');
        $blueprint->string('email');
        $blueprint->string('city')->nullable();
        $blueprint->string('state')->nullable();
        $blueprint->string('post_code')->nullable();
        $blueprint->string('country')->nullable();

        foreach ($blueprint->toSql($this->getConnection(), new FirebirdGrammar) as $sql) {
            DB::select($sql);
        }

        $blueprint = new Blueprint('orders');
        $blueprint->create();
        $blueprint->integer('id')->primary();
        $blueprint->integer('user_id');
        $blueprint->string('name');
        $blueprint->integer('price');
        $blueprint->integer('quantity');
        $blueprint->foreign('user_id')->references('id')->on('users');

        foreach ($blueprint->toSql($this->getConnection(), new FirebirdGrammar) as $sql) {
            DB::select($sql);
        }

        foreach (range(1, 10) as $id) {
            $faker = Faker::create();

            DB::table('users')->insert([
                'id' => $id,
                'name' => $faker->name,
                'email' => $faker->email,
                'city' => $faker->city,
                'state' => $faker->state,
                'post_code' => $faker->postcode,
                'country' => $faker->country,
            ]);

            DB::table('orders')->insert([
                'id' => $id,
                'user_id' => $id,
                'name' => $faker->word,
                'price' => self::$productPrices[$id - 1],
                'quantity' => $faker->numberBetween(0, 8),
            ]);
        }
    }

    public function tearDown(): void
    {
        try {
            DB::select('drop table "orders"');
            DB::select('drop table "users"');
        } catch (QueryException $e) {
            // ...
        }

        parent::tearDown();
    }

    /** @test */
    public function it_has_the_correct_connection()
    {
        $this->assertEquals('firebird', DB::getDefaultConnection());
    }

    /** @test */
    public function it_can_get()
    {
        $results = DB::table('users')->get();

        $this->assertCount(10, $results);
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertIsObject($results->first());
        $this->assertIsArray($results->toArray());
    }

    /** @test */
    public function it_can_select()
    {
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
        $results = DB::table('orders')->select('price')->distinct()->get();

        $uniquePricesCount = count(array_unique(self::$productPrices));
        $this->assertCount($uniquePricesCount, $results);
    }

    /** @test */
    public function it_can_filter_where_with_results()
    {
        $results = DB::table('users')
            ->where('id', 5)
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals(5, $results->first()->id);
    }

    /** @test */
    public function it_can_filter_where_without_results()
    {
        $results = DB::table('users')
            ->where('id', 5000)
            ->get();

        $this->assertCount(0, $results);
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertEquals([], $results->toArray());
        $this->assertNull($results->first());
    }

    /** @test */
    public function it_can_filter_where_in()
    {
        $results = DB::table('users')
            ->whereIn('id', [2, 5])
            ->get();

        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_can_order_by_asc()
    {
        $results = DB::table('users')->orderBy('id')->get();

        $this->assertEquals(1, $results->first()->id);
        $this->assertEquals(10, $results->last()->id);
    }

    /** @test */
    public function it_can_order_by_desc()
    {
        $results = DB::table('users')->orderByDesc('id')->get();

        $this->assertEquals(10, $results->first()->id);
        $this->assertEquals(1, $results->last()->id);
    }

    /** @test */
    public function it_can_pluck()
    {
        $results = DB::table('users')->pluck('id');

        $this->assertCount(10, $results);
        foreach (range(1, 10) as $expectedId) {
            $this->assertContains($expectedId, $results);
        }
    }

    /** @test */
    public function it_can_count()
    {
        $count = DB::table('orders')->count();

        $this->assertEquals(10, $count);
    }

    /** @test */
    public function it_can_aggregate_max()
    {
        $price = DB::table('orders')->max('price');

        $this->assertEquals(max(self::$productPrices), $price);
    }

    /** @test */
    public function it_can_aggregate_min()
    {
        $price = DB::table('orders')->min('price');

        $this->assertEquals(min(self::$productPrices), $price);
    }

    /** @test */
    public function it_can_aggregate_average()
    {
        $price = DB::table('orders')->avg('price');

        $expectedAverage = array_sum(self::$productPrices) / count(array_filter(self::$productPrices));
        $this->assertEquals((int) $expectedAverage, $price);
    }

    /** @test */
    public function it_can_aggregate_sum()
    {
        $price = DB::table('orders')->sum('price');

        $this->assertEquals(array_sum(self::$productPrices), $price);
    }

    /** @test */
    public function it_can_check_exists()
    {
        $this->assertTrue(DB::table('users')->where('id', 1)->exists());
        $this->assertFalse(DB::table('users')->where('id', null)->exists());
    }

    /** @test */
    public function it_can_execute_raw_expressions()
    {
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
        $results = DB::table('orders')
            ->whereRaw('"name" is not null')
            ->get();

        $this->assertCount(10, $results);
    }
}

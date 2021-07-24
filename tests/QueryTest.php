<?php

namespace Firebird\Tests;

use Faker\Factory as Faker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QueryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        DB::select('recreate table users (
            id integer not null,
            name varchar(255) not null,
            email varchar(255) not null,
            city varchar(255),
            state varchar(255),
            post_code varchar(255),
            country varchar(255),
            is_staff char(1) default null,
            constraint pk_users primary key (id)
        );');

        foreach (range(1, 10) as $id) {
            $faker = Faker::create();

            DB::table('USERS')->insert([
                'ID' => $id,
                'NAME' => $faker->name,
                'EMAIL' => $faker->email,
                'CITY' => $faker->city,
                'STATE' => $faker->state,
                'POST_CODE' => $faker->postcode,
                'COUNTRY' => $faker->country,
                'IS_STAFF' => $faker->boolean ? true : false,
            ]);
        }
    }

    public function tearDown(): void
    {
        DB::select('drop table users;');

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
        $results = DB::table('USERS')->get();

        $this->assertCount(10, $results);
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertIsObject($results->first());
        $this->assertIsArray($results->toArray());
    }

    /** @test */
    public function it_can_select()
    {
        $results = DB::table('USERS')
            ->select(['NAME', 'CITY', 'COUNTRY'])
            ->get();

        $result = $results->random();

        $this->assertCount(3, (array) $result);
        $this->assertObjectHasAttribute('NAME', $result);
        $this->assertObjectHasAttribute('CITY', $result);
        $this->assertObjectHasAttribute('COUNTRY', $result);
    }

    /** @test */
    public function it_can_select_with_aliases()
    {
        $results = DB::table('USERS')
            ->select([
                'NAME as USER_NAME',
                'CITY as user_city',
                'COUNTRY as User_Country',
            ])
            ->get();

        $result = $results->random();

        $this->assertCount(3, (array) $result);
        $this->assertObjectHasAttribute('USER_NAME', $result);
        $this->assertObjectHasAttribute('user_city', $result);
        $this->assertObjectHasAttribute('User_Country', $result);
    }

    /** @test */
    public function it_can_filter_where_with_results()
    {
        $results = DB::table('USERS')
            ->where('ID', 5)
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals(5, $results->first()->ID);
    }

    /** @test */
    public function it_can_filter_where_without_results()
    {
        $results = DB::table('USERS')
            ->where('ID', 5000)
            ->get();

        $this->assertCount(0, $results);
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertEquals([], $results->toArray());
        $this->assertNull($results->first());
    }

    /** @test */
    public function it_can_filter_where_in()
    {
        $results = DB::table('USERS')
            ->whereIn('ID', [2, 5])
            ->get();

        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_can_order_by_asc()
    {
        $results = DB::table('USERS')->orderBy('ID')->get();

        $this->assertEquals(1, $results->first()->ID);
        $this->assertEquals(10, $results->last()->ID);
    }

    /** @test */
    public function it_can_order_by_desc()
    {
        $results = DB::table('USERS')->orderByDesc('ID')->get();

        $this->assertEquals(10, $results->first()->ID);
        $this->assertEquals(1, $results->last()->ID);
    }

    /** @test */
    public function it_can_pluck()
    {
        $results = DB::table('USERS')->pluck('ID');

        $this->assertCount(10, $results);
        foreach (range(1, 10) as $expectedId) {
            $this->assertContains($expectedId, $results);
        }
    }
}

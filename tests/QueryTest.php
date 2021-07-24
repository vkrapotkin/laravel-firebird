<?php

namespace Firebird\Tests;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QueryTest extends TestCase
{
    /** @test */
    public function it_has_the_correct_connection()
    {
        $this->assertEquals('firebird', DB::getDefaultConnection());
    }

    /** @test */
    public function it_can_get()
    {
        $results = DB::table('CUSTOMER')->get();

        $this->assertCount(15, $results);
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertIsObject($results->first());
        $this->assertIsArray($results->toArray());
    }

    /** @test */
    public function it_can_select()
    {
        $results = DB::table('CUSTOMER')
            ->select([
                'CUSTOMER',
                'CITY',
                'COUNTRY',
            ])
            ->get();

        $result = $results->random();

        $this->assertCount(3, (array) $result);
        $this->assertObjectHasAttribute('CUSTOMER', $result);
        $this->assertObjectHasAttribute('CITY', $result);
        $this->assertObjectHasAttribute('COUNTRY', $result);
    }

    /** @test */
    public function it_can_select_with_aliases()
    {
        $results = DB::table('CUSTOMER')
            ->select([
                'CUSTOMER as CUSTOMER_NAME',
                'CITY as customer_city',
                'COUNTRY as Customer_Country',
            ])
            ->get();

        $result = $results->random();

        $this->assertCount(3, (array) $result);
        $this->assertObjectHasAttribute('CUSTOMER_NAME', $result);
        $this->assertObjectHasAttribute('customer_city', $result);
        $this->assertObjectHasAttribute('Customer_Country', $result);
    }

    /** @test */
    public function it_can_filter_where_with_results()
    {
        $results = DB::table('CUSTOMER')
            ->where('CUST_NO', 1001)
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals(1001, $results->first()->CUST_NO);
    }

    /** @test */
    public function it_can_filter_where_without_results()
    {
        $results = DB::table('CUSTOMER')
            ->where('CUST_NO', null)
            ->get();

        $this->assertCount(0, $results);
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertEquals([], $results->toArray());
        $this->assertNull($results->first());
    }

    /** @test */
    public function it_can_filter_where_in()
    {
        $results = DB::table('CUSTOMER')
            ->whereIn('CUST_NO', [1001, 1002])
            ->get();

        $this->assertCount(2, $results);
    }
}

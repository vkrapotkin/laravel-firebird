<?php

use Vkrapotkin\Firebird\Tests\Support\Factories\UserFactory;
use Vkrapotkin\Firebird\Tests\Support\Models\User;
use Vkrapotkin\Firebird\Tests\TestCase;

uses(TestCase::class);

it('can create a record', function () {
    User::create($fields = [
        'id' => $id = UserFactory::$id++, // Firebird < 3 does not support auto-incrementing columns.
        'name' => 'Anna',
        'email' => 'anna@example.com',
        'city' => 'Sydney',
        'state' => 'New South Wales',
        'post_code' => '2000',
        'country' => 'Australia',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    $user = User::find($id);

    expect($user)->toBeInstanceOf(User::class);

    // Check all fields have been persisted the model.
    foreach ($fields as $key => $value) {
        expect($user->{$key})->toEqual($value);
    }
});

<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders audit date filters as date pickers', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('audit.index'));

    $response->assertOk()
        ->assertSee('data-audit-date-from data-datepicker type="text"', false)
        ->assertSee('data-audit-date-to data-datepicker type="text"', false)
        ->assertDontSee('data-audit-date-from type="date"', false)
        ->assertDontSee('data-audit-date-to type="date"', false);
});

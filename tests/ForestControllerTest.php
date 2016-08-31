<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ForestControllerTest extends TestCase
{
    /** @test */
    public function is_forest_package_installed()
    {
        $response = $this->call('GET', '/forest/');

        $this->assertEquals(204, $response->status());
    }

    /** @test */
    public function is_session_with_no_parameters_return_unauthorized()
    {
        $response = $this->call('POST', '/forest/sessions');

        $this->assertEquals(401, $response->status());
    }

    /** @test */
    public function is_session_with_wrong_parameters_return_unauthorized()
    {
        $response = $this->call('POST', '/forest/sessions/', [
            'email' => 'test@gmail.com',
            'password' => 'abcdef',
            'renderingId' => '1234'
        ]);

        $this->assertEquals(401, $response->status());
    }
}

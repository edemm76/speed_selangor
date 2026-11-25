<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** The home page sends visitors to the login page, which loads */
    public function test_home_redirects_to_login_page(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();
    }
}

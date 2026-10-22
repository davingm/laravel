<?php

namespace Tests\Feature;

use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    public function test_the_homepage_renders_the_welcome_page_component(): void
    {
        $this->get('/')
            ->assertSee('Your application is ready. Make it yours.');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Integration;

final class IntegrationTestCaseTest extends IntegrationTestCase
{
    public function test_app_boots(): void
    {
        $this->assertTrue($this->app->bound('db'));
        $this->assertSame($this->appBasePath, $this->app->basePath());
    }
}

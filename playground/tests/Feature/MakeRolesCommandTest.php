<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MakeRolesCommandTest extends TestCase
{
    public function test_it_generates_role_middlewares_and_registers_aliases(): void
    {
        $bootstrapPath = base_path('bootstrap/app.php');
        $originalContent = File::get($bootstrapPath);

        try {
            $this->artisan('make:roles', ['roles' => ['admin', 'author', 'editor']])
                ->expectsOutputToContain('Role middleware generated successfully.')
                ->assertExitCode(0);

            $adminMiddleware = app_path('Http/Middleware/EnsureUserIsAdminMiddleware.php');
            $authorMiddleware = app_path('Http/Middleware/EnsureUserIsAuthorMiddleware.php');
            $editorMiddleware = app_path('Http/Middleware/EnsureUserIsEditorMiddleware.php');

            $this->assertFileExists($adminMiddleware);
            $this->assertFileExists($authorMiddleware);
            $this->assertFileExists($editorMiddleware);

            $this->assertStringContainsString("user->role === 'admin'", File::get($adminMiddleware));
            $this->assertStringContainsString("'role.admin'", File::get($bootstrapPath));
            $this->assertStringContainsString("'role.author'", File::get($bootstrapPath));
            $this->assertStringContainsString("'role.editor'", File::get($bootstrapPath));
        } finally {
            File::put($bootstrapPath, $originalContent);
            foreach (['Admin', 'Author', 'Editor'] as $role) {
                $path = app_path('Http/Middleware/EnsureUserIs'.$role.'Middleware.php');
                if (File::exists($path)) {
                    File::delete($path);
                }
            }
        }
    }
}

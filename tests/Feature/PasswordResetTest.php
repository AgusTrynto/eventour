<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('PDO SQLite extension is not installed.');
        }

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('mail.default', 'array');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createAuthTables();
    }

    public function test_forgot_password_page_can_be_opened(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Lupa Password?');
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = $this->createUser();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = $this->createUser();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('password-baru', $user->refresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    private function createAuthTables(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('role')->default('user');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function ($table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Reset User',
            'email' => 'reset@example.com',
            'password' => Hash::make('password-lama'),
            'role' => 'user',
        ]);
    }
}

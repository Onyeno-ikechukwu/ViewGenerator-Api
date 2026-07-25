<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;

test('user can request password reset using api', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->postJson('/api/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertStatus(200);

    Notification::assertSentTo(
        $user,
        ResetPassword::class
    );
});


test('user can reset password using api', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->postJson('/api/forgot-password', [
        'email' => $user->email,
    ]);

    Notification::assertSentTo(
        $user,
        ResetPassword::class,
        function ($notification) use ($user) {

            $response = $this->postJson('/api/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

            $response->assertStatus(200);

            return true;
        }
    );
});
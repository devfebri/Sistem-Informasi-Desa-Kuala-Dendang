<?php

use App\Models\User;

test('login memakai username dan redirect sesuai role', function (string $role, string $rute) {
    $user = User::factory()->{$role}()->create(['username' => 'tes_'.$role]);

    $this->post('/login', ['username' => 'tes_'.$role, 'password' => 'password'])
        ->assertRedirect(route($rute));

    $this->assertAuthenticatedAs($user);
})->with([
    ['admin', 'admin.dashboard'],
    ['kades', 'kades.dashboard'],
    ['warga', 'warga.beranda'],
]);

test('login gagal dengan kata sandi salah', function () {
    User::factory()->admin()->create(['username' => 'admin']);

    $this->from('/login')->post('/login', ['username' => 'admin', 'password' => 'salah'])
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('akun nonaktif ditolak saat login', function () {
    User::factory()->admin()->inactive()->create(['username' => 'nonaktif']);

    $this->post('/login', ['username' => 'nonaktif', 'password' => 'password'])
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('registrasi publik dimatikan', function () {
    $this->get('/register')->assertNotFound();
});

test('role dibatasi oleh middleware', function () {
    $this->actingAs(User::factory()->warga()->create())
        ->get(route('admin.dashboard'))->assertForbidden();

    $this->actingAs(User::factory()->kades()->create())
        ->get(route('admin.pengguna.index'))->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))->assertOk();
});

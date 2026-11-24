<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('admin dapat membuka halaman dan data tabel server-side', function () {
    User::factory()->count(15)->create();

    $this->get(route('admin.pengguna.index'))->assertOk();

    $this->getJson(route('admin.pengguna.data', ['draw' => 1, 'start' => 0, 'length' => 10]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 16)
        ->assertJsonCount(10, 'data');
});

test('admin dapat menambah akun warga', function () {
    $this->postJson(route('admin.pengguna.store'), [
        'name' => 'Budi', 'username' => 'budi', 'password' => 'rahasia', 'password_confirmation' => 'rahasia',
        'role' => 'warga', 'nik' => '6401010101900002', 'is_active' => 1,
    ])->assertOk();

    expect(User::where('username', 'budi')->first())
        ->role->toBe('warga')->nik->toBe('6401010101900002');
});

test('validasi: warga wajib NIK 16 digit dan username unik', function () {
    $this->postJson(route('admin.pengguna.store'), [
        'name' => 'X', 'username' => $this->admin->username, 'password' => 'rahasia',
        'password_confirmation' => 'rahasia', 'role' => 'warga', 'nik' => '123', 'is_active' => 1,
    ])->assertUnprocessable()->assertJsonValidationErrors(['username', 'nik']);
});

test('admin dapat mengubah akun tanpa mengganti kata sandi', function () {
    $user = User::factory()->warga()->create(['nik' => '6401010101900003']);
    $hash = $user->password;

    $this->putJson(route('admin.pengguna.update', $user), [
        'name' => 'Baru', 'username' => $user->username, 'role' => 'warga',
        'nik' => '6401010101900003', 'is_active' => 0,
    ])->assertOk();

    $user->refresh();
    expect($user->name)->toBe('Baru')->and($user->is_active)->toBeFalse()->and($user->password)->toBe($hash);
});

test('admin tidak dapat menghapus atau menonaktifkan akunnya sendiri', function () {
    $this->deleteJson(route('admin.pengguna.destroy', $this->admin))->assertUnprocessable();

    $this->putJson(route('admin.pengguna.update', $this->admin), [
        'name' => 'A', 'username' => $this->admin->username, 'role' => 'admin', 'is_active' => 0,
    ])->assertUnprocessable();
});

test('admin dapat menghapus akun lain', function () {
    $user = User::factory()->create();

    $this->deleteJson(route('admin.pengguna.destroy', $user))->assertOk();

    $this->assertModelMissing($user);
});

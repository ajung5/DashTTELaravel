<?php

use App\Models\TteRecord;
use App\Models\User;

it('shows only aggregate data on the public dashboard', function (): void {
    TteRecord::create([
        'source_row' => 2,
        'sync_batch' => 'test-batch',
        'nama' => 'Pegawai Rahasia',
        'nik' => '3210123456789012',
        'email' => 'pegawai-rahasia@example.test',
        'unit_kerja' => 'Dinas Contoh',
        'status_pengguna' => 'Verified',
        'status_sertifikat' => 'Issued'
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Ringkasan TTE Kabupaten Subang')
        ->assertDontSee('Pegawai Rahasia')
        ->assertDontSee('pegawai-rahasia@example.test')
        ->assertDontSee('3210123456789012');
});

it('requires admin authentication for detailed records', function (): void {
    $this->get('/admin/users')->assertRedirect(route('login'));
    $this->get('/admin/akan-expired')->assertRedirect(route('login'));
    $this->get('/admin/sudah-expired')->assertRedirect(route('login'));
});

it('allows an admin account to sign in and view detailed records', function (): void {
    $admin = User::factory()->create([
        'email' => 'admin@example.test',
        'password' => 'a-long-test-password'
    ]);

    $this->post('/login', [
        'email' => 'admin@example.test',
        'password' => 'a-long-test-password'
    ])->assertRedirect(route('admin.records.index'));

    $this->assertAuthenticatedAs($admin);
    $this->get('/admin/users')->assertOk()->assertSee('User per Unit Kerja');
});

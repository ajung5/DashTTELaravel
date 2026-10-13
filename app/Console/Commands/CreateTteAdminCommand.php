<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateTteAdminCommand extends Command {
    protected $signature = 'tte:admin:create {--email= : Email admin}';

    protected $description = 'Buat atau perbarui akun admin dashboard TTE';

    public function handle(): int {
        $email = (string) ($this->option('email') ?: $this->ask('Email admin'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Format email tidak valid.');

            return self::FAILURE;
        }

        $name = (string) $this->ask('Nama admin', 'Admin TTE');
        $password = (string) $this->secret('Password (minimal 12 karakter)');

        if (Str::length($password) < 12) {
            $this->error('Password harus minimal 12 karakter.');

            return self::FAILURE;
        }

        if ($password !== $this->secret('Ulangi password')) {
            $this->error('Konfirmasi password tidak sama.');

            return self::FAILURE;
        }

        User::updateOrCreate(['email' => Str::lower($email)], ['name' => $name, 'password' => $password]);

        $this->info('Akun admin berhasil disimpan.');

        return self::SUCCESS;
    }
}

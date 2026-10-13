<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSheetsClient {
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const SHEETS_SCOPE = 'https://www.googleapis.com/auth/spreadsheets.readonly';

    /** @return list<list<string|int|float|null>> */
    public function fetchValues(): array {
        $spreadsheetId = (string) config('tte.spreadsheet_id');
        $range = (string) config('tte.range');

        if ($spreadsheetId === '' || $range === '') {
            throw new RuntimeException('ID spreadsheet atau range Google Sheets belum diatur.');
        }

        $url =
            'https://sheets.googleapis.com/v4/spreadsheets/' .
            rawurlencode($spreadsheetId) .
            '/values/' .
            rawurlencode($range);

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(30)
            ->get($url, [
                'valueRenderOption' => 'FORMATTED_VALUE',
                'dateTimeRenderOption' => 'FORMATTED_STRING'
            ]);

        if ($response->status() === 401) {
            Cache::forget('tte.google.access_token');
            $response = Http::withToken($this->accessToken())
                ->acceptJson()
                ->timeout(30)
                ->get($url, [
                    'valueRenderOption' => 'FORMATTED_VALUE',
                    'dateTimeRenderOption' => 'FORMATTED_STRING'
                ]);
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'Google Sheets API gagal membaca data. Periksa izin service account, ID spreadsheet, dan range.'
            );
        }

        $values = $response->json('values', []);

        return is_array($values) ? $values : [];
    }

    private function accessToken(): string {
        return Cache::remember('tte.google.access_token', now()->addMinutes(50), function (): string {
            $path = base_path((string) config('tte.credentials_path'));

            if (!is_file($path) || !is_readable($path)) {
                throw new RuntimeException(
                    'File kredensial Google service account tidak ditemukan atau tidak dapat dibaca.'
                );
            }

            $credentials = json_decode((string) file_get_contents($path), true);

            if (!is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
                throw new RuntimeException(
                    'File kredensial Google service account tidak memiliki client_email/private_key yang valid.'
                );
            }

            $now = time();
            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
            $claims = $this->base64UrlEncode(
                json_encode(
                    [
                        'iss' => $credentials['client_email'],
                        'scope' => self::SHEETS_SCOPE,
                        'aud' => self::TOKEN_URL,
                        'iat' => $now,
                        'exp' => $now + 3600
                    ],
                    JSON_THROW_ON_ERROR
                )
            );
            $unsignedToken = $header . '.' . $claims;
            $privateKey = openssl_pkey_get_private($credentials['private_key']);

            if ($privateKey === false || !openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Kunci privat service account tidak dapat digunakan untuk membuat token.');
            }

            $assertion = $unsignedToken . '.' . $this->base64UrlEncode($signature);
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(20)
                ->post(self::TOKEN_URL, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion
                ]);

            if ($response->failed() || !is_string($response->json('access_token'))) {
                throw new RuntimeException(
                    'Google OAuth tidak berhasil menerbitkan access token. Periksa kredensial service account.'
                );
            }

            return $response->json('access_token');
        });
    }

    private function base64UrlEncode(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

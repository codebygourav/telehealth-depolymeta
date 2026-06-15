<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleSheetsService
{
    public function rows(?string $spreadsheetId = null, ?string $range = null): array
    {
        $spreadsheetId ??= config('services.google_sheets.spreadsheet_id');
        $range ??= config('services.google_sheets.range', 'A:L');

        if (! $spreadsheetId) {
            throw new RuntimeException('Google Sheet spreadsheet ID is not configured.');
        }

        $values = $this->http()
            ->get(sprintf(
                'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s',
                rawurlencode($spreadsheetId),
                rawurlencode($range),
            ))
            ->throw()
            ->json('values', []);

        if (! is_array($values) || count($values) < 2) {
            return [];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), array_shift($values));
        $trackUploadStatusIndex = array_search('track_upload_status', $headers, true);
        $rangePrefix = $this->rangePrefix($range);

        return collect($values)
            ->map(function (array $row, int $index) use ($headers, $rangePrefix, $trackUploadStatusIndex): array {
                $normalized = [];
                $sheetRowNumber = $index + 2;

                foreach ($headers as $columnIndex => $header) {
                    if ($header === '') {
                        continue;
                    }

                    $normalized[$header] = $row[$columnIndex] ?? null;
                }

                $normalized['__sheet_row_number'] = $sheetRowNumber;

                if ($trackUploadStatusIndex !== false) {
                    $normalized['__sheet_track_upload_status_cell'] = $rangePrefix.$this->columnLetter($trackUploadStatusIndex + 1).$sheetRowNumber;
                }

                return $normalized;
            })
            ->filter(fn (array $row) => collect($row)
                ->reject(fn ($value, string $key) => str_starts_with($key, '__sheet_'))
                ->contains(fn ($value) => $value !== null && trim((string) $value) !== ''))
            ->values()
            ->all();
    }

    public function markTrackUploadStatusUploaded(array $rows, ?string $spreadsheetId = null): int
    {
        $spreadsheetId ??= config('services.google_sheets.spreadsheet_id');

        if (! $spreadsheetId) {
            throw new RuntimeException('Google Sheet spreadsheet ID is not configured.');
        }

        $data = collect($rows)
            ->map(fn (array $row) => $row['__sheet_track_upload_status_cell'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->map(fn (string $cell) => [
                'range' => $cell,
                'values' => [['Uploaded']],
            ])
            ->all();

        if ($data === []) {
            return 0;
        }

        $this->http()
            ->post(sprintf(
                'https://sheets.googleapis.com/v4/spreadsheets/%s/values:batchUpdate',
                rawurlencode($spreadsheetId),
            ), [
                'valueInputOption' => 'USER_ENTERED',
                'data' => $data,
            ])
            ->throw();

        return count($data);
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout((int) config('services.google_sheets.timeout', 30));
    }

    private function accessToken(): string
    {
        $credentials = $this->credentials();
        $now = time();

        $jwt = implode('.', [
            $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'] ?? null,
                'scope' => 'https://www.googleapis.com/auth/spreadsheets',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_THROW_ON_ERROR)),
        ]);

        $privateKey = $credentials['private_key'] ?? null;
        if (! $privateKey || ! openssl_sign($jwt, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign Google service account request.');
        }

        $assertion = $jwt.'.'.$this->base64UrlEncode($signature);

        return Http::asForm()
            ->acceptJson()
            ->timeout((int) config('services.google_sheets.timeout', 30))
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ])
            ->throw()
            ->json('access_token');
    }

    private function credentials(): array
    {
        $json = config('services.google_sheets.service_account_json');

        if (! $json && ($path = config('services.google_sheets.service_account_json_path'))) {
            $path = Str::startsWith($path, '/') ? $path : base_path($path);

            if (! is_readable($path)) {
                throw new RuntimeException("Google service account JSON file is not readable at [{$path}].");
            }

            $json = file_get_contents($path);
        }

        if (! $json) {
            throw new RuntimeException('Google service account credentials are not configured.');
        }

        $credentials = json_decode($json, true);

        if (isset($credentials['web']) || isset($credentials['installed'])) {
            throw new RuntimeException('The configured Google JSON is an OAuth client secret. External booking sync needs a service account JSON key with type=service_account, client_email, and private_key.');
        }

        if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('Google service account JSON must include type=service_account, client_email, and private_key.');
        }

        return $credentials;
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function rangePrefix(string $range): string
    {
        if (! str_contains($range, '!')) {
            return '';
        }

        return Str::beforeLast($range, '!').'!';
    }

    private function columnLetter(int $columnNumber): string
    {
        $letter = '';

        while ($columnNumber > 0) {
            $modulo = ($columnNumber - 1) % 26;
            $letter = chr(65 + $modulo).$letter;
            $columnNumber = intdiv($columnNumber - $modulo, 26);
        }

        return $letter;
    }
}

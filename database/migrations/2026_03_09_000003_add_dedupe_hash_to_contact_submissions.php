<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_submissions', function (Blueprint $table) {
            $table->string('dedupe_hash', 64)->nullable()->index()->after('ip_hash');
        });

        // Backfill existing records using PHP-side hashing
        $key = (string) config('app.key', '');
        $key = preg_replace('/^base64:/', '', $key);
        $secret = base64_decode($key, true) ?: $key;

        $aliases = [
            'email'      => ['email', 'email_address', 'e_mail'],
            'phone'      => ['phone', 'phone_number', 'tel', 'telephone', 'mobile'],
            'first_name' => ['fname', 'first_name', 'vorname'],
            'last_name'  => ['lname', 'last_name', 'nachname'],
            'name'       => ['name', 'full_name', 'contact_name'],
            'plz'        => ['plz', 'zip', 'zip_code', 'postal_code'],
            'birth_year' => ['birth_year', 'birthYear', 'year_of_birth', 'geburtstag', 'birthday', 'dob', 'date_of_birth'],
        ];

        DB::table('contact_submissions')
            ->whereNull('dedupe_hash')
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($aliases, $secret) {
                foreach ($rows as $row) {
                    $data = is_string($row->data) ? (json_decode($row->data, true) ?? []) : [];
                    $hash = self::computeHash($data, $aliases, $secret);
                    DB::table('contact_submissions')
                        ->where('id', $row->id)
                        ->update(['dedupe_hash' => $hash]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('contact_submissions', function (Blueprint $table) {
            $table->dropColumn('dedupe_hash');
        });
    }

    private static function computeHash(array $data, array $aliases, string $secret): string
    {
        $parts = [];
        foreach ($aliases as $canonical => $keys) {
            $value = null;
            foreach ($keys as $k) {
                $v = $data[$k] ?? null;
                if ($v !== null && $v !== '') {
                    $value = strtolower(trim((string) $v));
                    break;
                }
            }
            // Extract birth year from date-like values
            if ($canonical === 'birth_year' && $value !== null && strlen($value) > 4) {
                if (preg_match('/(\d{4})/', $value, $m)) {
                    $value = $m[1];
                }
            }
            $parts[$canonical] = $value ?? '';
        }

        // Normalise phone: digits only
        if (isset($parts['phone']) && $parts['phone'] !== '') {
            $parts['phone'] = preg_replace('/\D/', '', $parts['phone']);
        }

        $payload = implode('|', [
            $parts['email'],
            $parts['phone'],
            trim($parts['first_name'] . ' ' . $parts['last_name'] . ' ' . $parts['name']),
            $parts['plz'],
            $parts['birth_year'],
        ]);

        return hash_hmac('sha256', $payload, $secret);
    }
};

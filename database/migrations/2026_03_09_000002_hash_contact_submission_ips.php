<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_submissions', function (Blueprint $table) {
            $table->string('ip_hash', 64)->nullable()->after('ip_address');
        });

        // Backfill existing rows (best-effort).
        // Use HMAC-SHA256 with app key so it is not reversible but remains stable for dedupe/analytics if needed.
        $key = (string) config('app.key', '');
        if ($key !== '') {
            $key = preg_replace('/^base64:/', '', $key);
            $secret = base64_decode($key, true) ?: $key;
        } else {
            $secret = '';
        }

        if ($secret !== '') {
            \DB::table('contact_submissions')
                ->whereNotNull('ip_address')
                ->whereNull('ip_hash')
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($secret) {
                    foreach ($rows as $row) {
                        $ip = (string) $row->ip_address;
                        if ($ip === '') continue;
                        $hash = hash_hmac('sha256', $ip, $secret);
                        \DB::table('contact_submissions')
                            ->where('id', $row->id)
                            ->update(['ip_hash' => $hash]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('contact_submissions', function (Blueprint $table) {
            $table->dropColumn('ip_hash');
        });
    }
};


<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use Illuminate\Database\Seeder;

/**
 * Purely cosmetic: gives the AD-020 audit log screen something to show
 * without waiting for real admin/assistant actions to accumulate.
 */
class DemoAuditLogSeeder extends Seeder
{
    public function run(): void
    {
        AuditLog::factory()->count(40)->create();
    }
}

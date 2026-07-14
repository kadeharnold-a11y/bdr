<?php

namespace Database\Seeders;

use App\Models\EventTypeConfig;
use App\Models\StaffUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed the six PRD event types (9.1 fee/duration config is
        // admin-editable in principle; these are placeholder defaults per
        // OQ-01/OQ-02 - the real fee schedule is still an open question in
        // the PRD). Only early_birth and death ship with a real application
        // form in this v1 slice.
        $eventTypes = [
            ['event_type' => 'early_birth', 'label' => 'Early Birth Registration', 'standard_fee' => 5, 'express_fee' => 50, 'standard_duration_days' => 15, 'express_duration_days' => 3, 'express_enabled' => true, 'form_supported' => true],
            ['event_type' => 'late_birth', 'label' => 'Late Birth Registration', 'standard_fee' => 20, 'express_fee' => 100, 'standard_duration_days' => 20, 'express_duration_days' => 5, 'express_enabled' => true, 'form_supported' => false],
            ['event_type' => 'death', 'label' => 'Death Registration', 'standard_fee' => 5, 'express_fee' => 50, 'standard_duration_days' => 15, 'express_duration_days' => 3, 'express_enabled' => true, 'form_supported' => true],
            ['event_type' => 'foetal_death', 'label' => 'Foetal Death Registration', 'standard_fee' => 5, 'express_fee' => 50, 'standard_duration_days' => 15, 'express_duration_days' => 3, 'express_enabled' => true, 'form_supported' => false],
            ['event_type' => 'adoption', 'label' => 'Adoption Registration', 'standard_fee' => 30, 'express_fee' => 150, 'standard_duration_days' => 20, 'express_duration_days' => 7, 'express_enabled' => true, 'form_supported' => false],
            ['event_type' => 'surrogacy', 'label' => 'Surrogacy Birth Registration', 'standard_fee' => 30, 'express_fee' => 150, 'standard_duration_days' => 20, 'express_duration_days' => 7, 'express_enabled' => true, 'form_supported' => false],
        ];

        foreach ($eventTypes as $config) {
            $existing = EventTypeConfig::find($config['event_type']);
            if ($existing) {
                // form_supported reflects code capability, not admin config -
                // keep it in sync without touching admin-edited fees.
                $existing->update(['form_supported' => $config['form_supported']]);
            } else {
                EventTypeConfig::create($config);
            }
        }

        // Dev convenience: back-office user provisioning (PRD 9.2) isn't
        // built as an admin UI yet, so seed one account per role with a known
        // dev password. Never run this in production.
        if (! app()->isProduction()) {
            $staffSeed = [
                ['staff_id' => 'ADM-001', 'full_name' => 'Dev Admin', 'role' => 'ADMIN'],
                ['staff_id' => 'OFF-001', 'full_name' => 'Dev Registration Officer', 'role' => 'REGISTRATION_OFFICER'],
                ['staff_id' => 'SUP-001', 'full_name' => 'Dev Supervisor', 'role' => 'SUPERVISOR'],
                ['staff_id' => 'FIN-001', 'full_name' => 'Dev Finance Officer', 'role' => 'FINANCE_OFFICER'],
            ];

            foreach ($staffSeed as $staff) {
                StaffUser::firstOrCreate(
                    ['staff_id' => $staff['staff_id']],
                    [
                        'id' => (string) Str::uuid(),
                        'full_name' => $staff['full_name'],
                        'role' => $staff['role'],
                        'password' => Hash::make('changeme123'),
                        'active' => true,
                    ]
                );
            }
        }
    }
}

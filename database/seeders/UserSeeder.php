<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Staff accounts created for local development, one per role.
     *
     * Passwords come from the environment so no credential is ever committed.
     *
     * @var list<array{name: string, email: string, role: string}>
     */
    private const STAFF = [
        ['name' => 'Administrator', 'email' => 'admin@kompra.test', 'role' => 'admin'],
        ['name' => 'Sales Team', 'email' => 'sales@kompra.test', 'role' => 'sales'],
        ['name' => 'Stock Custodian', 'email' => 'inventory@kompra.test', 'role' => 'inventory'],
        ['name' => 'Accounting', 'email' => 'accounts@kompra.test', 'role' => 'accounts'],
        ['name' => 'Auditor', 'email' => 'auditor@kompra.test', 'role' => 'auditor'],
        ['name' => 'Human Resources', 'email' => 'hr@kompra.test', 'role' => 'hr'],
    ];

    public function run(): void
    {
        $password = Hash::make(config('kompra.seed_password'));

        foreach (self::STAFF as $staff) {
            $user = User::query()->updateOrCreate(
                ['email' => $staff['email']],
                [
                    'name' => $staff['name'],
                    'password' => $password,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$staff['role']]);
        }

        $this->seedBranchManagers($password);
    }

    /**
     * One branch manager per pickup branch. A branch added later simply needs
     * a user assigned to it; no new role or route is involved.
     */
    private function seedBranchManagers(string $hashedPassword): void
    {
        Branch::query()->where('is_pickup_location', true)->each(
            function (Branch $branch) use ($hashedPassword): void {
                $user = User::query()->updateOrCreate(
                    ['email' => $branch->slug.'@kompra.test'],
                    [
                        'name' => $branch->name.' Manager',
                        'password' => $hashedPassword,
                        'branch_id' => $branch->id,
                        'email_verified_at' => now(),
                        'is_active' => true,
                    ]
                );

                $user->syncRoles(['branch_manager']);
            }
        );
    }
}

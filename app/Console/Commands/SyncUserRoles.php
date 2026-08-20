<?php

namespace App\Console\Commands;

use App\Models\Role as SchoolRole;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class SyncUserRoles extends Command
{
    protected $signature = 'users:sync-roles';

    protected $description = 'Synchronize users\' Spatie roles from their supported user_type values';

    public function handle(PermissionRegistrar $permissionRegistrar): int
    {
        $supportedRoles = SchoolRole::supportedNames();
        $existingRoles = SpatieRole::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $supportedRoles)
            ->pluck('name')
            ->all();

        $updated = 0;
        $skipped = 0;
        $unchanged = 0;

        User::query()
            ->whereNotNull('user_type')
            ->where('user_type', '<>', '')
            ->with('roles')
            ->orderBy('id')
            ->chunkById(200, function ($users) use (
                $supportedRoles,
                $existingRoles,
                &$updated,
                &$skipped,
                &$unchanged,
            ): void {
                foreach ($users as $user) {
                    $label = sprintf('#%d %s', $user->id, $user->email);

                    if (! in_array($user->user_type, $supportedRoles, true)) {
                        $this->warn(sprintf(
                            'SKIPPED %s: unknown user_type "%s".',
                            $label,
                            $user->user_type,
                        ));
                        $skipped++;

                        continue;
                    }

                    if (! in_array($user->user_type, $existingRoles, true)) {
                        $this->warn(sprintf(
                            'SKIPPED %s: Spatie role "%s" does not exist for the web guard.',
                            $label,
                            $user->user_type,
                        ));
                        $skipped++;

                        continue;
                    }

                    $assignedRoles = $user->getRoleNames();

                    if ($assignedRoles->count() === 1 && $assignedRoles->first() === $user->user_type) {
                        $this->line(sprintf('UNCHANGED %s: %s.', $label, $user->user_type));
                        $unchanged++;

                        continue;
                    }

                    $before = $assignedRoles->isEmpty() ? 'none' : $assignedRoles->implode(', ');
                    $user->syncRoles([$user->user_type]);

                    $this->info(sprintf(
                        'UPDATED %s: [%s] -> %s.',
                        $label,
                        $before,
                        $user->user_type,
                    ));
                    $updated++;
                }
            });

        if ($updated > 0) {
            $permissionRegistrar->forgetCachedPermissions();
        }

        $this->newLine();
        $this->info(sprintf(
            'Role synchronization complete. Updated: %d; skipped: %d; unchanged: %d.',
            $updated,
            $skipped,
            $unchanged,
        ));

        return self::SUCCESS;
    }
}

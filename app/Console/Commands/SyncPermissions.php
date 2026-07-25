<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and sync permissions from config/permission.php';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $modules = config('permission.modules');
        $actions = config('permission.actions');

        if (! $modules || ! $actions) {
            $this->error('Modules or actions config not found in path config/permission.php!');

            return;
        }

        $this->info('Starting permission syncronization ...');
        $count = 0;

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissionName = $action.' '.$module;

                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);

                if ($permission->wasRecentlyCreated) {
                    $this->line("Created: {$permissionName}");
                    $count++;
                }
            }
        }

        $this->info("Syncronization finish! {$count} new permission added");
    }
}

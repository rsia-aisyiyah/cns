<?php

namespace App\Console\Commands;

use BezhanSalleh\FilamentShield\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CustomShieldSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $signature = 'shield:super-admin
        {--user= : ID of user to be made super admin.}
        {--panel= : Panel ID to get the configuration from.}
        {--tenant= : Team/Tenant ID to assign role to user.}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description = 'Custom Command to Creates Filament Super Admin';


    protected Authenticatable $superAdmin;

    /** @var ?\Illuminate\Database\Eloquent\Model */
    protected $superAdminRole = null;

    protected function getAuthGuard(): Guard
    {
        if ($this->option('panel')) {
            Filament::setCurrentPanel(Filament::getPanel($this->option('panel')));
        }

        return Filament::getCurrentPanel()?->auth();
    }

    protected function getUserProvider(): UserProvider
    {
        return $this->getAuthGuard()->getProvider();
    }

    protected function getUserModel(): string
    {
        /** @var EloquentUserProvider $provider */
        $provider = $this->getUserProvider();

        return $provider->getModel();
    }



    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $usersCount = static::getUserModel()::count();
        $tenantId = $this->option('tenant');

        if ($this->option('user')) {
            $this->superAdmin = static::getUserModel()::select([ \Illuminate\Support\Facades\DB::raw('AES_DECRYPT(id_user, "' . env('MYSQL_AES_KEY_IDUSER') . '") AS id_user') ])
                ->findOrFail($this->option('user'));
        } elseif ($usersCount === 1) {
            $this->superAdmin = static::getUserModel()::select([ \Illuminate\Support\Facades\DB::raw('AES_DECRYPT(id_user, "' . env('MYSQL_AES_KEY_IDUSER') . '") AS id_user') ])
                ->first();
        } elseif ($usersCount > 1) {
            $this->table(
                ['ID', 'Name', 'Roles'],
                static::getUserModel()::select([ \Illuminate\Support\Facades\DB::raw('AES_DECRYPT(id_user, "' . env('MYSQL_AES_KEY_IDUSER') . '") AS id_user') ])
                    ->with(['roles', 'detail'])->get()->map(function (Authenticatable $user) {
                    return [
                        'id' => $user->getKey(),
                        'name' => $user->detail?->nama ?? $user->getAttribute('name'),
                        /** @phpstan-ignore-next-line */
                        'roles' => implode(',', $user->roles->pluck('name')->toArray()),
                    ];
                })
            );

            $superAdminId = text(
                label: 'Please provide the `UserID` to be set as `super_admin`',
                required: true
            );

            $this->superAdmin = static::getUserModel()::select([ \Illuminate\Support\Facades\DB::raw('AES_DECRYPT(id_user, "' . env('MYSQL_AES_KEY_IDUSER') . '") AS id_user') ])
                ->findOrFail($superAdminId);
        } else {
            $this->info('No users found. Creating a new super admin user.');
            return self::FAILURE;
        }

        if (Utils::isTenancyEnabled()) {
            if (blank($tenantId)) {
                $this->components->error('Please provide the team/tenant id via `--tenant` option to assign the super admin to a team/tenant.');

                return self::FAILURE;
            }
            setPermissionsTeamId($tenantId);
            $this->superAdminRole = FilamentShield::createRole(tenantId: $tenantId);
            $this->superAdminRole->syncPermissions(Utils::getPermissionModel()::pluck('id'));
        } else {
            $this->superAdminRole = FilamentShield::createRole();
        }

        $this->superAdmin
            ->unsetRelation('roles')
            ->unsetRelation('permissions');

        $this->superAdmin
            ->assignRole($this->superAdminRole);

        $loginUrl = Filament::getCurrentPanel()?->getLoginUrl();

        $this->components->info("Success! {$this->superAdmin->email} may now log in at {$loginUrl}.");

        return self::SUCCESS;
    }
}

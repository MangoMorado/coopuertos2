<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class AssignMangoRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'new-mango {email : El email del usuario}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asigna el rol Mango a un usuario por su email';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        // Buscar usuario por email
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Usuario con email '{$email}' no encontrado.");

            return self::FAILURE;
        }

        // Verificar si el rol Mango existe
        $mangoRole = Role::where('name', 'Mango')->where('guard_name', 'web')->first();

        if (! $mangoRole) {
            $this->error('El rol Mango no existe. Ejecuta primero: php artisan db:seed --class=RolePermissionSeeder');

            return self::FAILURE;
        }

        // Verificar si ya tiene el rol
        if ($user->hasRole('Mango')) {
            $this->warn("El usuario '{$user->name}' ({$email}) ya tiene el rol Mango.");

            return self::SUCCESS;
        }

        // Asignar rol
        $user->assignRole('Mango');

        $this->info("✓ Rol Mango asignado exitosamente a: {$user->name} ({$email})");

        return self::SUCCESS;
    }
}

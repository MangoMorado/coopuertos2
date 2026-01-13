<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
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
    protected $description = 'Asigna el rol Mango a un usuario por su email. Si el usuario no existe, lo crea.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        // Normalizar email a minúsculas
        $email = strtolower($email);

        // Buscar usuario por email
        $user = User::where('email', $email)->first();

        // Si el usuario no existe, crear uno nuevo
        if (! $user) {
            $this->info("Usuario con email '{$email}' no encontrado. Se creará un nuevo usuario con rol Mango.");

            // Pedir nombre del usuario
            $name = $this->ask('Ingrese el nombre del usuario');

            if (empty($name)) {
                $this->error('El nombre es requerido.');

                return self::FAILURE;
            }

            // Pedir contraseña con validación
            $password = $this->secret('Ingrese la contraseña (mínimo 8 caracteres)');

            if (empty($password)) {
                $this->error('La contraseña es requerida.');

                return self::FAILURE;
            }

            // Confirmar contraseña
            $passwordConfirmation = $this->secret('Confirme la contraseña');

            // Validar contraseña con las reglas de seguridad de Laravel
            $validator = Validator::make(
                [
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'password_confirmation' => $passwordConfirmation,
                ],
                [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
                    'password' => ['required', 'confirmed', Rules\Password::defaults()],
                ]
            );

            if ($validator->fails()) {
                $this->error('Errores de validación:');
                foreach ($validator->errors()->all() as $error) {
                    $this->error("  - {$error}");
                }

                return self::FAILURE;
            }

            // Verificar si el rol Mango existe
            $mangoRole = Role::where('name', 'Mango')->where('guard_name', 'web')->first();

            if (! $mangoRole) {
                $this->error('El rol Mango no existe. Ejecuta primero: php artisan db:seed --class=RolePermissionSeeder');

                return self::FAILURE;
            }

            // Crear el nuevo usuario
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'theme' => 'light',
            ]);

            // Asignar rol Mango
            $user->assignRole('Mango');

            $this->info("✓ Usuario creado exitosamente con rol Mango: {$user->name} ({$email})");

            return self::SUCCESS;
        }

        // Si el usuario existe, verificar si el rol Mango existe
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

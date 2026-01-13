<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\CoopuertosServer;
use App\Mcp\Tools\EliminarJobsFallidos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EliminarJobsFallidosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Mango', 'guard_name' => 'web']);
    }

    public function test_tool_requires_mango_role(): void
    {
        $user = User::factory()->create();
        // No asignar rol Mango

        $response = CoopuertosServer::actingAs($user)->tool(EliminarJobsFallidos::class, [
            'id' => 1,
            'confirmar' => true,
        ]);

        $response->assertHasErrors();
    }

    public function test_tool_can_delete_failed_job_by_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Mango');

        // Crear un job fallido
        $jobId = DB::table('failed_jobs')->insertGetId([
            'uuid' => 'test-uuid-123',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'data']),
            'exception' => 'Test exception',
            'failed_at' => now(),
        ]);

        $response = CoopuertosServer::actingAs($user)->tool(EliminarJobsFallidos::class, [
            'id' => $jobId,
            'confirmar' => true,
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('failed_jobs', ['id' => $jobId]);
    }

    public function test_tool_can_delete_failed_job_by_uuid(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Mango');

        $uuid = 'test-uuid-456';
        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['test' => 'data']),
            'exception' => 'Test exception',
            'failed_at' => now(),
        ]);

        $response = CoopuertosServer::actingAs($user)->tool(EliminarJobsFallidos::class, [
            'uuid' => $uuid,
            'confirmar' => true,
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid]);
    }

    public function test_tool_can_delete_all_failed_jobs(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Mango');

        // Crear varios jobs fallidos
        DB::table('failed_jobs')->insert([
            ['uuid' => 'uuid-1', 'connection' => 'database', 'queue' => 'default', 'payload' => '{}', 'exception' => 'Error 1', 'failed_at' => now()],
            ['uuid' => 'uuid-2', 'connection' => 'database', 'queue' => 'default', 'payload' => '{}', 'exception' => 'Error 2', 'failed_at' => now()],
        ]);

        $response = CoopuertosServer::actingAs($user)->tool(EliminarJobsFallidos::class, [
            'eliminar_todos' => true,
            'confirmar' => true,
        ]);

        $response->assertOk();
        $this->assertEquals(0, DB::table('failed_jobs')->count());
    }

    public function test_tool_requires_confirmation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Mango');

        $response = CoopuertosServer::actingAs($user)->tool(EliminarJobsFallidos::class, [
            'id' => 1,
            'confirmar' => false,
        ]);

        $response->assertHasErrors();
    }
}

<?php

namespace Tests\Feature\Mcp\Resources;

use App\Mcp\Resources\DocumentacionMcpServer;
use App\Mcp\Servers\CoopuertosServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentacionMcpServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_returns_documentation_content(): void
    {
        $user = User::factory()->create();

        $response = CoopuertosServer::actingAs($user)->resource(DocumentacionMcpServer::class);

        $response->assertOk();
        $response->assertSee('Documentación del Servidor MCP');
    }

    public function test_resource_has_correct_uri(): void
    {
        $resource = new DocumentacionMcpServer;

        $this->assertEquals('coopuertos://mcp/documentacion', $resource->uri());
    }

    public function test_resource_has_correct_mime_type(): void
    {
        $resource = new DocumentacionMcpServer;

        $this->assertEquals('text/markdown', $resource->mimeType());
    }
}

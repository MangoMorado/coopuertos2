<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class McpTestController extends Controller
{
    public function index()
    {
        return view('mcp-test');
    }

    public function test(Request $request)
    {
        $baseUrl = $request->input('url', url('/'));
        $endpoint = rtrim($baseUrl, '/').'/mcp/coopuertos';

        $results = [
            'endpoint' => $endpoint,
            'tests' => [],
            'timestamp' => now()->toDateTimeString(),
        ];

        // Test 1: Initialize
        $initResult = $this->testMcpRequest($endpoint, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [],
                'clientInfo' => [
                    'name' => 'test-client',
                    'version' => '1.0.0',
                ],
            ],
        ]);

        $results['tests']['initialize'] = [
            'name' => 'Initialize',
            'success' => $initResult['success'],
            'message' => $initResult['message'],
            'data' => $initResult['data'],
        ];

        // Test 2: tools/list
        $toolsResult = $this->testMcpRequest($endpoint, [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
        ]);

        $toolCount = 0;
        if ($toolsResult['success'] && isset($toolsResult['data']['result']['tools'])) {
            $toolCount = count($toolsResult['data']['result']['tools']);
        }

        $results['tests']['tools_list'] = [
            'name' => 'Tools List',
            'success' => $toolsResult['success'],
            'message' => $toolsResult['success'] ? "{$toolCount} herramientas encontradas" : $toolsResult['message'],
            'data' => $toolsResult['data'],
            'count' => $toolCount,
        ];

        // Test 3: prompts/list
        $promptsResult = $this->testMcpRequest($endpoint, [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'prompts/list',
        ]);

        $promptCount = 0;
        if ($promptsResult['success'] && isset($promptsResult['data']['result']['prompts'])) {
            $promptCount = count($promptsResult['data']['result']['prompts']);
        }

        $results['tests']['prompts_list'] = [
            'name' => 'Prompts List',
            'success' => $promptsResult['success'],
            'message' => $promptsResult['success'] ? "{$promptCount} prompts encontrados" : $promptsResult['message'],
            'data' => $promptsResult['data'],
            'count' => $promptCount,
        ];

        // Test 4: resources/list
        $resourcesResult = $this->testMcpRequest($endpoint, [
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'resources/list',
        ]);

        $resourceCount = 0;
        if ($resourcesResult['success'] && isset($resourcesResult['data']['result']['resources'])) {
            $resourceCount = count($resourcesResult['data']['result']['resources']);
        }

        $results['tests']['resources_list'] = [
            'name' => 'Resources List',
            'success' => $resourcesResult['success'],
            'message' => $resourcesResult['success'] ? "{$resourceCount} recursos encontrados" : $resourcesResult['message'],
            'data' => $resourcesResult['data'],
            'count' => $resourceCount,
        ];

        return response()->json($results);
    }

    private function testMcpRequest(string $url, array $data): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $data);

            $httpCode = $response->status();
            $responseBody = $response->body();

            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'message' => "HTTP {$httpCode}: ".substr($responseBody, 0, 200),
                    'data' => null,
                    'http_code' => $httpCode,
                ];
            }

            $decoded = $response->json();

            if (isset($decoded['error'])) {
                return [
                    'success' => false,
                    'message' => 'Error en respuesta: '.json_encode($decoded['error']),
                    'data' => $decoded,
                ];
            }

            return [
                'success' => true,
                'message' => 'OK',
                'data' => $decoded,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }
}

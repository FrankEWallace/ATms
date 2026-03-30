<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $method = strtoupper($request->method());

        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        // Only log if user is authenticated
        if (!auth()->check()) {
            return $response;
        }

        // Only log on successful responses
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            return $response;
        }

        $action = match ($method) {
            'POST'   => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default  => 'update',
        };

        $siteId  = $request->header('X-Site-Id');
        $actorId = auth()->id();

        // Derive entity_type from route name or URI
        $routeName  = $request->route()?->getName() ?? '';
        $entityType = $this->deriveEntityType($routeName, $request->path());

        // Try to get the entity ID from route parameters
        $entityId = null;
        $routeParams = $request->route()?->parameters() ?? [];
        foreach ($routeParams as $key => $value) {
            if (Str::endsWith($key, '_id') || in_array($key, ['id', 'uuid'])) {
                $entityId = $value;
                break;
            }
            // If param looks like a UUID, use it
            if (is_string($value) && Str::isUuid($value)) {
                $entityId = $value;
                break;
            }
        }

        // Get new data from response body if available
        $newData = null;
        $content = $response->getContent();
        if ($content) {
            $decoded = json_decode($content, true);
            if (isset($decoded['data']) && is_array($decoded['data'])) {
                $newData = $decoded['data'];
                // Extract entity ID from response data if not found in route
                if ($entityId === null && isset($decoded['data']['id'])) {
                    $entityId = $decoded['data']['id'];
                }
            }
        }

        try {
            AuditLog::create([
                'site_id'     => $siteId ?: null,
                'actor_id'    => $actorId,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'action'      => $action,
                'old_data'    => null,
                'new_data'    => $newData,
            ]);
        } catch (\Throwable $e) {
            // Never break the response chain due to audit logging failure
            logger()->error('AuditLogger failed: ' . $e->getMessage());
        }

        return $response;
    }

    private function deriveEntityType(string $routeName, string $path): string
    {
        if ($routeName) {
            // e.g. "api.v1.transactions.store" -> "transactions"
            $parts = explode('.', $routeName);
            if (count($parts) >= 3) {
                return $parts[count($parts) - 2];
            }
        }

        // Fallback: parse URI e.g. "/api/v1/transactions/123" -> "transactions"
        $segments = array_values(array_filter(explode('/', $path)));
        // Skip "api" and "v1"
        $filtered = array_values(array_filter($segments, fn($s) => !in_array($s, ['api', 'v1'])));
        return $filtered[0] ?? 'unknown';
    }
}

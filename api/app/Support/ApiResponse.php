<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    public static function ok(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        $payload = ['success' => true, 'message' => $message];

        if ($data instanceof ResourceCollection || $data instanceof LengthAwarePaginator) {
            $arr = $data instanceof ResourceCollection ? $data->response()->getData(true) : $data->toArray();
            $payload['data'] = $arr['data'] ?? $arr;
            if (isset($arr['meta'])) {
                $payload['meta'] = $arr['meta'];
            } elseif ($data instanceof LengthAwarePaginator) {
                $payload['meta'] = [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                ];
            }
        } elseif ($data instanceof JsonResource) {
            $payload['data'] = $data->resolve();
        } else {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    public static function created(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return static::ok($data, $message, 201);
    }

    public static function paginated(array $data, array $meta, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ]);
    }

    public static function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message], $status);
    }

    public static function error(string $code, string $message, int $status = 400, array $extra = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => array_merge(['code' => $code, 'message' => $message], $extra),
        ], $status);
    }
}

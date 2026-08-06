<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class ApiResponse
{
    /**
     * @param  mixed  $data
     * @param  array<string, mixed>  $meta
     * @param  list<mixed>  $errors
     */
    public static function success(
        mixed $data = null,
        string $message = '',
        array $meta = [],
        int $status = 200,
        array $errors = [],
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => self::normalizeData($data),
            'meta' => (object) $meta,
            'errors' => $errors,
        ], $status);
    }

    /**
     * @param  list<mixed>|array<string, mixed>  $errors
     * @param  array<string, mixed>  $meta
     */
    public static function error(
        string $message,
        int $status = 400,
        array $errors = [],
        mixed $data = null,
        array $meta = [],
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'meta' => (object) $meta,
            'errors' => self::normalizeErrors($errors),
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function paginated(
        AbstractPaginator $paginator,
        string $message = '',
        array $meta = [],
        ?callable $mapItem = null,
    ): JsonResponse {
        $items = $paginator->items();
        if ($mapItem !== null) {
            $items = array_map($mapItem, $items);
        }

        return self::success(
            $items,
            $message,
            array_merge([
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ], $meta),
        );
    }

    protected static function normalizeData(mixed $data): mixed
    {
        if ($data instanceof ResourceCollection || $data instanceof JsonResource) {
            return $data->resolve(request());
        }

        return $data;
    }

    /**
     * @param  list<mixed>|array<string, mixed>  $errors
     * @return list<array{field?: string, message: string}>
     */
    protected static function normalizeErrors(array $errors): array
    {
        if ($errors === []) {
            return [];
        }

        // Laravel validation bag: field => [messages]
        if (array_is_list($errors) === false) {
            $normalized = [];
            foreach ($errors as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $normalized[] = [
                        'field' => (string) $field,
                        'message' => (string) $message,
                    ];
                }
            }

            return $normalized;
        }

        return array_map(function ($error) {
            if (is_array($error) && isset($error['message'])) {
                return $error;
            }

            return ['message' => (string) $error];
        }, $errors);
    }
}

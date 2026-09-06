<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GenericResource;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    /** Current organization id for the authenticated user. */
    protected function orgId(Request $request): string
    {
        return $request->user()->organization_id;
    }

    /** Apply a simple ?search= filter over the given columns. */
    protected function applySearch(Builder $query, Request $request, array $columns): Builder
    {
        $term = trim((string) $request->query('search', ''));
        if ($term === '') {
            return $query;
        }

        return $query->where(function ($q) use ($columns, $term) {
            foreach ($columns as $col) {
                $q->orWhere($col, 'like', "%{$term}%");
            }
        });
    }

    protected function paginate(Builder $query, Request $request, ?callable $map = null)
    {
        $perPage = min((int) $request->query('per_page', 20), 100);
        $page = $query->paginate($perPage);

        $data = collect($page->items())->map(
            $map ?? fn ($m) => (new GenericResource($m))->resolve()
        )->all();

        return ApiResponse::paginated($data, [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ]);
    }

    protected function items($collection, ?callable $map = null)
    {
        $data = collect($collection)->map(
            $map ?? fn ($m) => is_array($m) ? $m : (new GenericResource($m))->resolve()
        )->all();

        return ApiResponse::ok($data);
    }

    protected function item($model, ?callable $map = null)
    {
        return ApiResponse::ok($map ? $map($model) : (new GenericResource($model))->resolve());
    }
}

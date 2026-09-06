<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Serialises any model to camelCase JSON, recursively camelCasing loaded
 * relations too. Keeps money/int casts as native numbers.
 */
class GenericResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return $this->camelize($this->resource->toArray());
    }

    private function camelize(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $ck = is_string($key) ? Str::camel($key) : $key;
            $out[$ck] = is_array($value) ? $this->camelize($value) : $value;
        }

        return $out;
    }
}

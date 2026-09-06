<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Base resource: emits camelCase keys so the JS/Flutter clients need no mapping.
 * Subclasses may override fields() to shape output; by default the model's
 * visible attributes are used.
 */
abstract class ApiResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = method_exists($this, 'fields')
            ? $this->fields($request)
            : $this->resource->toArray();

        return collect($data)
            ->mapWithKeys(fn ($value, $key) => [Str::camel($key) => $this->normalise($value)])
            ->all();
    }

    private function normalise(mixed $value): mixed
    {
        return $value;
    }
}

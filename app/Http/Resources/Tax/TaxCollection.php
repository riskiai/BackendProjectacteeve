<?php

namespace App\Http\Resources\Tax;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaxCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [];

        foreach ($this as $tax) {
            $data[] = [
                'id' => $tax->id,
                'name' => $tax->name,
                'description' => $tax->description,
                'percent' => $tax->percent,
                'type' => $tax->type,
                'created_at' => $tax->created_at,
                'updated_at' => $tax->updated_at,
            ];
        }

        return $data;
    }
}

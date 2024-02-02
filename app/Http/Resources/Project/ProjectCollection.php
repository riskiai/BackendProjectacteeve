<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProjectCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [];

        foreach ($this as $project) {
            $data[] = [
                'id' => $project->id,
                'client' => [
                    'id' => $project->company->id,
                    'name' => $project->company->name,
                    'contact_type' => $project->company->contactType->name,
                ],
                'name' => $project->name,
                'billing' => $project->billing,
                'cost_estimate' => $project->cost_estimate,
                'margin' => $project->margin,
                'percent' => $project->percent,
                'file' => asset("storage/$project->file"),
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
            ];
        }

        return $data;
    }
}

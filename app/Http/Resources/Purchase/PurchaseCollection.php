<?php

namespace App\Http\Resources\Purchase;

use App\Models\Purchase;
use App\Models\PurchaseStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [];

        foreach ($this as $purchase) {
            $data[] = [
                "doc_no" => $purchase->doc_no,
                "doc_type" => $purchase->doc_type,
                "purchase_type" => $purchase->purchase_id ? "Event Purchase" : "Operational Purchase",
                "company_name" => $purchase->company->name,
                "project_name" => $purchase->project->name,
                "status" => [
                    $purchase->purchaseStatus->id,
                    $purchase->purchaseStatus->name
                ],
                "description" => $purchase->description,
                "remarks" => $purchase->remarks,
                "sub_total" => $purchase->sub_total,
                "ppn" => $purchase->ppn,
                "total" => $purchase->total,
                "file" => asset("storage/$purchase->file"),
                "date" => $purchase->date,
                "due_date" => $purchase->due_date,
                "created_at" => $purchase->created_at,
                "updated_at" => $purchase->updated_at,
            ];
        }

        return $data;
    }
}

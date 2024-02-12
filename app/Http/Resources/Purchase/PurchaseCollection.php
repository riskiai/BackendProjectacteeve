<?php

namespace App\Http\Resources\Purchase;

use App\Models\Purchase;
use App\Models\PurchaseStatus;
use Carbon\Carbon;
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
                "purchase_type" => $purchase->purchase_id ? Purchase::TEXT_EVENT : Purchase::TEXT_OPERATIONAL,
                "vendor_name" => $purchase->company->name,
                "project_name" => $purchase->project->name,
                "status" => $this->getStatus($purchase),
                "description" => $purchase->description,
                "remarks" => $purchase->remarks,
                "sub_total" => $purchase->sub_total,
                "tax" => [
                    "id" => $purchase->tax->id,
                    "name" => $purchase->tax->name,
                    "percent" => $purchase->tax->percent,
                ],
                "total" => $purchase->total,
                "file_attachment" => [
                    "name" => "$purchase->doc_type/$purchase->doc_no/" . date('Y') . ".pdf",
                    "link" => asset("storage/$purchase->file"),
                ],
                "date" => $purchase->date,
                "due_date" => $purchase->due_date,
                "created_at" => $purchase->created_at,
                "updated_at" => $purchase->updated_at,
            ];
        }

        return $data;
    }

    protected function getStatus($purchase)
    {
        $data = [];

        if ($purchase->tab == Purchase::TAB_SUBMIT) {
            $data = [
                "id" => $purchase->purchaseStatus->id,
                "name" => $purchase->purchaseStatus->name,
            ];
        }

        if ($purchase->tab == Purchase::TAB_PAID) {
            $data = [
                "id" => $purchase->purchaseStatus->id,
                "name" => $purchase->purchaseStatus->name,
            ];
        }

        if (
            $purchase->tab == Purchase::TAB_VERIFIED ||
            $purchase->tab == Purchase::TAB_PAYMENT_REQUEST
        ) {
            $dueDate = Carbon::createFromFormat("Y-m-d", $purchase->due_date);
            $nowDate = Carbon::now();

            $data = [
                "id" => PurchaseStatus::OPEN,
                "name" => PurchaseStatus::TEXT_OPEN,
            ];

            if ($nowDate->gt($dueDate)) {
                $data = [
                    "id" => PurchaseStatus::OVERDUE,
                    "name" => PurchaseStatus::TEXT_OVERDUE,
                ];
            }

            if ($nowDate->toDateString() == $purchase->due_date) {
                $data = [
                    "id" => PurchaseStatus::DUEDATE,
                    "name" => PurchaseStatus::TEXT_DUEDATE,
                ];
            }
        }

        return $data;
    }
}

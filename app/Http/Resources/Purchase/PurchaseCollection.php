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

        foreach ($this as $key => $purchase) {
            $data[$key] = [
                "doc_no" => $purchase->doc_no,
                "doc_type" => $purchase->doc_type,
                "purchase_type" => $purchase->purchase_id == Purchase::TYPE_EVENT ? Purchase::TEXT_EVENT : Purchase::TEXT_OPERATIONAL,
                "vendor_name" => $purchase->company->name,
                "status" => $this->getStatus($purchase),
                "description" => $purchase->description,
                "remarks" => $purchase->remarks,
                "sub_total" => $purchase->sub_total,
                "total" => $purchase->total,
                "file_attachment" => $this->getDocument($purchase),
                "date" => $purchase->date,
                "due_date" => $purchase->due_date,
                "ppn" => $purchase->ppn ?? 0,
                "created_at" => $purchase->created_at,
                "updated_at" => $purchase->updated_at,
            ];

            if ($purchase->purchase_id == Purchase::TYPE_EVENT) {
                $data[$key]['project'] = [
                    "id" => $purchase->project->id,
                    "name" => $purchase->project->name,
                ];
            }

            if ($purchase->pph) {
                $data[$key]['tax_pph'] = [
                    "id" => $purchase->taxPph->id,
                    "name" => $purchase->taxPph->name,
                    "percent" => $purchase->taxPph->percent,
                ];
            }
        }

        return $data;
    }

    protected function getDocument($documents)
    {
        $data = [];

        foreach ($documents->documents as $document) {
            $data[] = [
                "name" => "$document->purchase->doc_type/$document->doc_no/" . date('Y', strtotime($document->created_at)) . ".pdf"
            ];
        }

        return $data;
        // return [
        //     "name" => "$purchase->doc_type/$purchase->doc_no/" . date('Y', strtotime($purchase->created_at)) . ".pdf",
        //     "link" => asset("storage/$purchase->file"),
        // ];
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

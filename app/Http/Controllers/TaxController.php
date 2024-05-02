<?php

namespace App\Http\Controllers;

use App\Facades\Filters\Purchase\ByDate;
use App\Facades\Filters\Purchase\ByProject;
use App\Facades\Filters\Purchase\ByPurchaseID;
use App\Facades\Filters\Purchase\BySearch;
use App\Facades\Filters\Purchase\ByStatus;
use App\Facades\Filters\Purchase\ByTab;
use App\Facades\Filters\Purchase\ByTax;
use App\Facades\Filters\Purchase\ByVendor;
use App\Facades\Filters\Purchase\InTaxReport;
use App\Models\Tax;
use Illuminate\Http\Request;
use App\Facades\MessageActeeve;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Tax\CreateRequest;
use App\Http\Requests\Tax\UpdateRequest;
use App\Http\Resources\Purchase\PurchaseCollection;
use App\Http\Resources\Tax\TaxCollection;
use App\Models\Purchase;
use App\Models\PurchaseStatus;
use Carbon\Carbon;
use Illuminate\Pipeline\Pipeline;

class TaxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Tax::query();

        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('id', 'like', '%' . $request->search . '%');
                $query->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);

            $query->whereBetween('created_at', $date);
        }

        $tax = $query->paginate($request->per_page);

        return new TaxCollection($tax);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        try {
            $tax = Tax::create($request->all());

            DB::commit();
            return MessageActeeve::success("tax $tax->name has been created");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tax = Tax::find($id);
        if (!$tax) {
            return MessageActeeve::notFound('data not found!');
        }

        return MessageActeeve::render([
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            'data' => [
                'id' => $tax->id,
                'name' => $tax->name,
                'description' => $tax->description,
                'percent' => $tax->percent,
                'type' => $tax->type,
                'created_at' => $tax->created_at,
                'updated_at' => $tax->updated_at,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, $id)
    {
        DB::beginTransaction();

        $tax = Tax::find($id);
        if (!$tax) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            $tax->update($request->all());

            DB::commit();
            return MessageActeeve::success("tax $tax->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        $tax = Tax::find($id);
        if (!$tax) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            $tax->delete();

            DB::commit();
            return MessageActeeve::success("tax $tax->name has been deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function reportPpn(Request $request)
    {
        $query = Purchase::query();

        $purchases = app(Pipeline::class)
            ->send($query)
            ->through([
                ByDate::class,
                InTaxReport::class,
                ByPurchaseID::class,
                ByTab::class,
                ByStatus::class,
                ByVendor::class,
                ByProject::class,
                ByTax::class,
                BySearch::class
            ])
            ->thenReturn()
            ->where(function ($query) {
                $query->whereNotNull('ppn')
                    ->where('ppn', '>', 0); 
            })
            ->orderBy('date', 'desc')
            ->paginate($request->per_page);

        return new PurchaseCollection($purchases);
    }

    public function reportPpnall(Request $request)
    {
        $query = Purchase::query();
    
        $purchases = app(Pipeline::class)
            ->send($query)
            ->through([
                ByDate::class,
                InTaxReport::class,
                ByPurchaseID::class,
                ByTab::class,
                ByStatus::class,
                ByVendor::class,
                ByProject::class,
                ByTax::class,
                BySearch::class
            ])
            ->thenReturn()
            ->where(function ($query) {
                $query->whereNotNull('ppn')
                    ->where('ppn', '>', 0); 
            })
            ->orderBy('date', 'desc')
            ->get(); // Menggunakan metode get() untuk mengambil semua data
    
        return new PurchaseCollection($purchases);
    }
    

    protected function getPpn($purchase)
    {
        return ($purchase->sub_total * $purchase->ppn) / 100;
    }

    protected function getDocument($documents)
    {
        $data = [];

        foreach ($documents->documents as $document) {
            $data[] = [
                "id" => $document->id,
                "name" => $document->purchase->doc_type . "/$document->doc_no.$document->id/" . date('Y', strtotime($document->created_at)) . ".pdf",
                "link" => asset("storage/$document->file_path"),
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

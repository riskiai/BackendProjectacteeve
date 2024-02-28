<?php

namespace App\Http\Controllers;

use App\Facades\Filters\Purchase\ByDate;
use App\Facades\Filters\Purchase\ByProject;
use App\Facades\Filters\Purchase\ByPurchaseID;
use App\Facades\Filters\Purchase\ByStatus;
use App\Facades\Filters\Purchase\ByTab;
use App\Facades\Filters\Purchase\ByTax;
use App\Facades\Filters\Purchase\ByVendor;
use App\Facades\Filters\Purchase\BySearch;
use App\Facades\MessageActeeve;
use App\Http\Requests\Purchase\AcceptRequest;
use App\Http\Requests\Purchase\CreateRequest;
use App\Http\Requests\Purchase\UpdateRequest;
use App\Http\Resources\Purchase\PurchaseCollection;
use App\Http\Resources\Purchase\PurchaseCounting;
use App\Models\Company;
use App\Models\ContactType;
use App\Models\Document;
use App\Models\Purchase;
use App\Models\PurchaseCategory;
use App\Models\PurchaseStatus;
use App\Models\Tax;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurchaseController extends Controller
{
    // public function counting(Request $request)
    // {
    //     $purchaseId = $request->purchase_id ?? 1;

    //     $countVerified = Purchase::where('purchase_id', $purchaseId)
    //         ->where('purchase_status_id', PurchaseStatus::VERIFIED)
    //         ->sum(DB::raw('sub_total + ppn'));
    //     $countDueDate = Purchase::where('purchase_id', $purchaseId)
    //         ->where('purchase_status_id', PurchaseStatus::DUEDATE)
    //         ->sum(DB::raw('sub_total + ppn'));
    //     $countPaymentRequest = Purchase::where('purchase_id', $purchaseId)
    //         ->where('tab', Purchase::TAB_PAYMENT_REQUEST)
    //         ->sum(DB::raw('sub_total + ppn'));
    //     $countPaid = Purchase::where('purchase_id', $purchaseId)
    //         ->where('tab', Purchase::TAB_PAID)
    //         ->sum(DB::raw('sub_total + ppn'));

    //     $data = [
    //         [
    //             'title' => 'VERIFIED',
    //             'amount' => $countVerified
    //         ],
    //         [
    //             'title' => 'DUE DATE',
    //             'amount' => $countDueDate
    //         ],
    //         [
    //             'title' => 'PAYMENT REQUEST',
    //             'amount' => $countPaymentRequest
    //         ],
    //         [
    //             'title' => 'PAID',
    //             'amount' => $countPaid
    //         ]
    //     ];

    //     return [
    //         'status' => MessageActeeve::SUCCESS,
    //         'status_code' => MessageActeeve::HTTP_OK,
    //         'data' => $data
    //     ];
    // }

    public function counting(Request $request)
    {
        $purchaseId = $request->purchase_id ?? 1;

        $countRecieved = Purchase::where('purchase_id', $purchaseId)
            ->count();

        $countVerified = Purchase::selectRaw("SUM(sub_total + (sub_total * ppn) / 100) as result")
            ->where('purchase_id', $purchaseId)
            ->where('tab', Purchase::TAB_SUBMIT)
            ->first()->result;

        $countOverdue = 0;
        $purchaseOverdue = Purchase::where('purchase_id', $purchaseId)
            ->whereDate('due_date', '>=', Carbon::now())
            ->where('tab', Purchase::TAB_VERIFIED)
            ->get();
        foreach ($purchaseOverdue as $purchase) {
            $total = $purchase->sub_total;

            if ($purchase->ppn) {
                $ppn = ($purchase->sub_total * $purchase->ppn) / 100;
                $total += $ppn;
            }

            if ($purchase->taxPph) {
                $pph = ($total * $purchase->taxPph->percent) / 100;
                $total -= $pph;
            }

            $countOverdue += $total;
        }

        $countOpen = 0;
        $purchaseOpen = Purchase::where('purchase_id', $purchaseId)
            ->whereDate('due_date', '<', Carbon::now())
            ->where('tab', Purchase::TAB_VERIFIED)
            ->get();
        foreach ($purchaseOpen as $purchase) {
            $total = $purchase->sub_total;

            if ($purchase->ppn) {
                $ppn = ($purchase->sub_total * $purchase->ppn) / 100;
                $total += $ppn;
            }

            if ($purchase->taxPph) {
                $pph = ($total * $purchase->taxPph->percent) / 100;
                $total -= $pph;
            }

            $countOpen += $total;
        }

        $countDueDate = 0;
        $purchaseDueDate = Purchase::where('purchase_id', $purchaseId)
            ->whereDate('due_date',  Carbon::now())
            ->where('tab', Purchase::TAB_VERIFIED)
            ->get();
        foreach ($purchaseDueDate as $purchase) {
            $total = $purchase->sub_total;

            if ($purchase->ppn) {
                $ppn = ($purchase->sub_total * $purchase->ppn) / 100;
                $total += $ppn;
            }

            if ($purchase->taxPph) {
                $pph = ($total * $purchase->taxPph->percent) / 100;
                $total -= $pph;
            }

            $countDueDate += $total;
        }

        $countPaymentRequest = 0;
        $purchaseDueDate = Purchase::where('purchase_id', $purchaseId)
            ->where('tab', Purchase::TAB_PAYMENT_REQUEST)
            ->get();
        foreach ($purchaseDueDate as $purchase) {
            $total = $purchase->sub_total;

            if ($purchase->ppn) {
                $ppn = ($purchase->sub_total * $purchase->ppn) / 100;
                $total += $ppn;
            }

            if ($purchase->taxPph) {
                $pph = ($total * $purchase->taxPph->percent) / 100;
                $total -= $pph;
            }

            $countPaymentRequest += $total;
        }

        $countPaid = 0;
        $purchaseDueDate = Purchase::where('purchase_id', $purchaseId)
            ->where('tab', Purchase::TAB_PAID)
            ->get();
        foreach ($purchaseDueDate as $purchase) {
            $total = $purchase->sub_total;

            if ($purchase->ppn) {
                $ppn = ($purchase->sub_total * $purchase->ppn) / 100;
                $total += $ppn;
            }

            if ($purchase->taxPph) {
                $pph = ($total * $purchase->taxPph->percent) / 100;
                $total -= $pph;
            }

            $countPaid += $total;
        }

        return [
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            "data" => [
                "recieved" => $countRecieved,
                "verified" => $countVerified,
                "over_due" => $countOverdue,
                "open" => $countOpen,
                "due_date" => $countDueDate,
                "payment_request" => $countPaymentRequest,
                "paid" => $countPaid,
            ]
        ];
    }


    public function index(Request $request)
    {
        $query = Purchase::query();

        $purchases = app(Pipeline::class)
            ->send($query)
            ->through([
                ByPurchaseID::class,
                ByTab::class,
                ByDate::class,
                ByStatus::class,
                ByVendor::class,
                ByProject::class,
                ByTax::class,
                BySearch::class
            ])
            ->thenReturn()
            ->paginate($request->per_page);

        return new PurchaseCollection($purchases);
    }

    public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        $purchase = Purchase::where('purchase_category_id', $request->purchase_category_id)->max('doc_no');
        $purchaseCategory = PurchaseCategory::find($request->purchase_category_id);

        $company = Company::find($request->client_id);
        if ($company->contact_type_id != ContactType::VENDOR) {
            return MessageActeeve::warning("this contact is not a vendor type");
        }

        try {
            $request->merge([
                'doc_no' => $this->generateDocNo($purchase, $purchaseCategory),
                'doc_type' => Str::upper($purchaseCategory->name),
                'purchase_status_id' => PurchaseStatus::AWAITING,
                'company_id' => $company->id,
                'ppn' => $request->tax_ppn,
            ]);

            $purchase = Purchase::create($request->all());
            foreach ($request->attachment_file as $key => $file) {
                $this->saveDocument($purchase, $file, $key + 1);
            }

            DB::commit();
            return MessageActeeve::success("doc no $purchase->doc_no has been created");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function show($docNo)
    {
        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageActeeve::notFound('data not found!');
        }

        $data =
            [
                "doc_no" => $purchase->doc_no,
                "doc_type" => $purchase->doc_type,
                "purchase_type" => $purchase->purchase_id == Purchase::TYPE_EVENT ? Purchase::TEXT_EVENT : Purchase::TEXT_OPERATIONAL,
                "vendor_name" => [
                    "id" => $purchase->company->id,
                    "name" => $purchase->company->name,
                    "bank" => $purchase->company->bank_name,
                    "account_name" => $purchase->company->account_name,
                    "account_number" => $purchase->company->account_number,
                ],
                "status" => $this->getStatus($purchase),
                "description" => $purchase->description,
                "remarks" => $purchase->remarks,
                "sub_total" => $purchase->sub_total,
                "total" => $purchase->total,
                "file_attachment" => $this->getDocument($purchase),
                "date" => $purchase->date,
                "due_date" => $purchase->due_date,
                "ppn" => $this->getPpn($purchase),
                "log" => $purchase->logs()->select('name', 'created_at')->latest()->first(),
                "created_at" => $purchase->created_at,
                "updated_at" => $purchase->updated_at,
            ];

        if ($purchase->purchase_id == Purchase::TYPE_EVENT) {
            $data['project'] = [
                "id" => $purchase->project->id,
                "name" => $purchase->project->name,
            ];
        }

        if ($purchase->pph) {
            $data['pph'] = $this->getPph($purchase);
        }

        return MessageActeeve::render([
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            "data" => $data
        ]);
    }

    public function update(UpdateRequest $request, $docNo)
    {
        DB::beginTransaction();

        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageActeeve::notFound('data not found!');
        }

        $company = Company::find($request->client_id);
        if ($company->contact_type_id == ContactType::VENDOR) {
            return MessageActeeve::warning("this contact is not a vendor type");
        }

        $request->merge([
            'ppn' => $request->tax_ppn,
            'company_id' => $company->id,
        ]);

        try {
            if ($request->has('attachment_file')) {
                foreach ($request->attachment_file as $key => $file) {
                    $this->saveDocument($purchase, $file, $key + 1);
                }
            }

            Purchase::whereDocNo($docNo)->update($request->except(['_method', 'attachment_file', 'tax_id']));

            DB::commit();
            return MessageActeeve::success("doc no $docNo has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function destroy($docNo)
    {
        DB::beginTransaction();

        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            Purchase::whereDocNo($docNo)->delete();

            DB::commit();
            return MessageActeeve::success("purchase $docNo has been deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function accept(AcceptRequest $request, $docNo)
    {
        DB::beginTransaction();

        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageActeeve::notFound('data not found!');
        }

        $pph = Tax::find($request->pph_id);
        if (strtolower($pph->type) != Tax::TAX_PPH) {
            return MessageActeeve::warning("this tax is not a pph type");
        }

        $request->merge([
            'purchase_status_id' => PurchaseStatus::VERIFIED,
            'tab' => Purchase::TAB_VERIFIED,
            'pph' => $pph->id
        ]);

        try {
            $purchase->logs()->updateOrCreate([
                'tab' => Purchase::TAB_VERIFIED,
                'name' => auth()->user()->name
            ], [
                'name' => auth()->user()->name
            ]);

            Purchase::whereDocNo($docNo)->update($request->except('pph_id'));

            DB::commit();
            return MessageActeeve::success("purchase $docNo has been accepted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function reject($docNo, Request $request)
    {
        DB::beginTransaction();

        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            $purchase->logs()->updateOrCreate([
                'tab' => Purchase::TAB_SUBMIT,
                'name' => auth()->user()->name
            ], [
                'name' => auth()->user()->name
            ]);

            Purchase::whereDocNo($docNo)->update([
                'purchase_status_id' => PurchaseStatus::REJECTED,
                'reject_note' => $request->note
            ]);

            DB::commit();
            return MessageActeeve::success("purchase $docNo has been rejected");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function request($docNo)
    {
        DB::beginTransaction();

        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            $purchase->logs()->updateOrCreate([
                'tab' => Purchase::TAB_PAYMENT_REQUEST,
                'name' => auth()->user()->name
            ], [
                'name' => auth()->user()->name
            ]);

            Purchase::whereDocNo($docNo)->update([
                'tab' => Purchase::TAB_PAYMENT_REQUEST,
            ]);

            DB::commit();
            return MessageActeeve::success("purchase $docNo has been request");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function payment($docNo)
    {
        DB::beginTransaction();

        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            $purchase->logs()->updateOrCreate([
                'tab' => Purchase::TAB_PAID,
                'name' => auth()->user()->name
            ], [
                'name' => auth()->user()->name
            ]);

            Purchase::whereDocNo($docNo)->update([
                'purchase_status_id' => PurchaseStatus::PAID,
                'tab' => Purchase::TAB_PAID,
            ]);

            DB::commit();
            return MessageActeeve::success("purchase $docNo payment successfully");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function undo($docNo)
    {
        DB::beginTransaction();

        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageActeeve::notFound('data not found!');
        }

        if ($purchase->tab == 1) {
            return MessageActeeve::warning("cannot undo because tab is submit");
        }

        try {
            $purchase->logs()->updateOrCreate([
                'tab' => $purchase->tab - 1,
                'name' => auth()->user()->name
            ], [
                'name' => auth()->user()->name
            ]);

            Purchase::whereDocNo($docNo)->update([
                'tab' => $purchase->tab - 1,
            ]);

            DB::commit();
            return MessageActeeve::success("purchase $docNo undo successfully");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function deleteDocument($id)
    {
        DB::beginTransaction();

        $purchase = Document::find($id);
        if (!$purchase) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            Storage::delete($purchase->path_file);
            $purchase->delete();

            DB::commit();
            return MessageActeeve::success("document $id delete successfully");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    protected function generateDocNo($maxPurchase, $purchaseCategory)
    {
        $numericPart = (int) substr($maxPurchase, strpos($maxPurchase, '-') + 1);
        $nextNumber = sprintf('%03d', $numericPart + 1);

        return "$purchaseCategory->short-$nextNumber";
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

    protected function getPpn($purchase)
    {
        return ($purchase->sub_total * $purchase->ppn) / 100;
    }

    protected function getPph($purchase)
    {
        return [
            "pph_type" => $purchase->taxPph->name,
            "pph_rate" => $purchase->taxPph->percent,
            "pph_hasil" => (($purchase->sub_total + $purchase->ppn) * $purchase->taxPph->percent) / 100
        ];
    }

    // =======
    protected function saveDocument($purchase, $file, $iteration)
    {
        $document = $file->store(Purchase::ATTACHMENT_FILE);
        return $purchase->documents()->create([
            "doc_no" => $purchase->doc_no,
            "file_name" => $purchase->doc_no . '.' . $iteration,
            "file_path" => $document
        ]);
    }
}

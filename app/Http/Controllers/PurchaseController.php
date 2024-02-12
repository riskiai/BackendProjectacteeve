<?php

namespace App\Http\Controllers;

use App\Facades\Filters\Purchase\ByDate;
use App\Facades\Filters\Purchase\ByProject;
use App\Facades\Filters\Purchase\ByPurchaseID;
use App\Facades\Filters\Purchase\ByStatus;
use App\Facades\Filters\Purchase\ByTab;
use App\Facades\Filters\Purchase\ByTax;
use App\Facades\Filters\Purchase\ByVendor;
use App\Facades\MessageActeeve;
use App\Http\Requests\Purchase\AcceptRequest;
use App\Http\Requests\Purchase\CreateRequest;
use App\Http\Requests\Purchase\UpdateRequest;
use App\Http\Resources\Purchase\PurchaseCollection;
use App\Http\Resources\Purchase\PurchaseCounting;
use App\Models\Company;
use App\Models\ContactType;
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
    public function counting(Request $request)
    {
        $purchaseId = $request->purchase_id ?? 1;

        $countVerified = Purchase::where('purchase_id', $purchaseId)
            ->where('purchase_status_id', PurchaseStatus::VERIFIED)
            ->sum('total');
        $countDueDate = Purchase::where('purchase_id', $purchaseId)
            ->where('purchase_status_id', PurchaseStatus::DUEDATE)
            ->sum('total');
        $countPaymentRequest = Purchase::where('purchase_id', $purchaseId)
            ->where('tab', Purchase::TAB_PAYMENT_REQUEST)
            ->sum('total');
        $countPaid = Purchase::where('purchase_id', $purchaseId)
            ->where('tab', Purchase::TAB_PAID)
            ->sum('total');

        return [
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            "data" => [
                "verified" => $countVerified,
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
                ByTax::class
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

        $tax = Tax::find($request->tax_id);
        if (strtolower($tax->type) != Tax::TAX_PPN) {
            return MessageActeeve::warning("this tax is not a ppn type");
        }

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
                'ppn' => $tax->id,
                'file' => $request->file('attachment_file')->store(Purchase::ATTACHMENT_FILE)
            ]);

            $purchase = Purchase::create($request->all());

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

        $data = [
            "doc_no" => $purchase->doc_no,
            "doc_type" => $purchase->doc_type,
            "purchase_type" => $purchase->purchase_id ? Purchase::TEXT_EVENT : Purchase::TEXT_OPERATIONAL,
            "vendor_name" => $purchase->company->name,
            "project_name" => $purchase->project->name,
            "status" => $this->getStatus($purchase),
            "description" => $purchase->description,
            "remarks" => $purchase->remarks,
            "sub_total" => $purchase->sub_total,
            "total" => $purchase->total,
            "file_attachment" => [
                "name" => "$purchase->doc_type/$purchase->doc_no/" . date('Y', strtotime($purchase->created_at)) . ".pdf",
                "link" => asset("storage/$purchase->file"),
            ],
            "date" => $purchase->date,
            "due_date" => $purchase->due_date,
            "created_at" => $purchase->created_at,
            "updated_at" => $purchase->updated_at,
        ];

        if ($purchase->pph) {
            $data['tax_pph'] = [
                "id" => $purchase->taxPph->id,
                "name" => $purchase->taxPph->name,
                "percent" => $purchase->taxPph->percent,
            ];
        }

        if ($purchase->ppn) {
            $data['tax_ppn'] = [
                "id" => $purchase->taxPpn->id,
                "name" => $purchase->taxPpn->name,
                "percent" => $purchase->taxPpn->percent,
            ];
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

        $tax = Tax::find($request->tax_id);
        if (strtolower($tax->type) != Tax::TAX_PPN) {
            return MessageActeeve::warning("this tax is not a ppn type");
        }

        $request->merge([
            'ppn' => $tax->id,
            'company_id' => $company->id,
        ]);

        try {
            if ($request->hasFile('attachment_file')) {
                Storage::delete($purchase->file);
                $request->merge([
                    'file' => $request->file('attachment_file')->store(Purchase::ATTACHMENT_FILE),
                ]);
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
            Purchase::whereDocNo($docNo)->update($request->except('pph_id'));

            DB::commit();
            return MessageActeeve::success("purchase $docNo has been accepted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function reject($docNo)
    {
        DB::beginTransaction();

        $purchase = Purchase::whereDocNo($docNo)->first();
        if (!$purchase) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            Purchase::whereDocNo($docNo)->update([
                'purchase_status_id' => PurchaseStatus::REJECTED,
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

    protected function generateDocNo($maxPurchase, $purchaseCategory)
    {
        $numericPart = (int) substr($maxPurchase, strpos($maxPurchase, '-') + 1);
        $nextNumber = sprintf('%03d', $numericPart + 1);

        return "$purchaseCategory->short-$nextNumber";
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

<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Role;
use App\Models\Company;
use App\Models\Project;
use App\Models\Document;
use App\Models\Purchase;
use App\Models\ContactType;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PurchaseStatus;
use App\Facades\MessageActeeve;
use App\Models\PurchaseCategory;
use Illuminate\Pipeline\Pipeline;
// use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Facades\Filters\Purchase\ByTab;
use App\Facades\Filters\Purchase\ByTax;
use Illuminate\Support\Facades\Storage;
use App\Facades\Filters\Purchase\ByDate;
use App\Facades\Filters\Purchase\BySearch;
use App\Facades\Filters\Purchase\ByStatus;
use App\Facades\Filters\Purchase\ByVendor;
use App\Facades\Filters\Purchase\ByProject;
use App\Http\Requests\Purchase\AcceptRequest;
use App\Http\Requests\Purchase\CreateRequest;
use App\Http\Requests\Purchase\UpdateRequest;
use App\Facades\Filters\Purchase\ByPurchaseID;
use App\Facades\Filters\Purchase\ByUpdated;
use App\Http\Resources\Purchase\PurchaseCounting;
use App\Http\Resources\Purchase\PurchaseCollection;

class PurchaseController extends Controller
{
    public function counting(Request $request)
    {
        $purchaseId = $request->purchase_id ?? 1;
        $userId = auth()->id();
        $role = auth()->user()->role_id;

        $counts = app(Pipeline::class)
            ->send(Purchase::query())
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
            ->when($role == Role::USER, function ($query) use ($userId) {
                return $query->where('user_id', $userId);
            })
            ->get(); // Mengambil semua objek Purchase yang sesuai dengan kueri

        $recieved = 0;
        $submit = 0;
        $verified = 0;
        $over_due = 0;
        $open = 0;
        $due_date = 0;
        $payment_request = 0;
        $paid = 0;

        foreach ($counts as $purchase) {
            $total = $purchase->getTotalAttribute(); // Mengambil nilai total dari setiap objek Purchase

            switch ($purchase->tab) {
                case Purchase::TAB_VERIFIED:
                    $verified += $total;
                    if ($purchase->due_date > now()) {
                        $open += $total;
                    } elseif ($purchase->due_date == today()) {
                        $due_date += $total;
                    }
                    break;
                case Purchase::TAB_PAYMENT_REQUEST:
                    $payment_request += $total;
                    break;
                case Purchase::TAB_PAID:
                    $paid += $total;
                    break;
                case Purchase::TAB_SUBMIT:
                    $submit += $total;
                    break;
            }

            if ($purchase->due_date < now()) {
                $over_due += $total;
            }
        }

        $recieved = $verified + $over_due;

        return [
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            "data" => [
                "recieved" => $recieved,
                "submit" => $submit,
                "verified" => $verified,
                "over_due" => $over_due,
                "open" => $open,
                "due_date" => $due_date,
                "payment_request" => $payment_request,
                "paid" => $paid,
            ]
        ];
    }




    public function index(Request $request)
    {
        $query = Purchase::query();
    
        // Tambahkan filter berdasarkan tanggal terkini
        // $query->whereDate('date', Carbon::today());

        // Terapkan filter berdasarkan peran pengguna
        if (auth()->user()->role_id == Role::USER) {
            $query->where('user_id', auth()->user()->id);
        }
        
        $purchases = app(Pipeline::class)
            ->send($query)
            ->through([
                ByDate::class,
                ByUpdated::class,
                ByPurchaseID::class,
                ByTab::class,
                ByStatus::class,
                ByVendor::class,
                ByProject::class,
                ByTax::class,
                BySearch::class,
            ])
            ->thenReturn();
    
        // kondisi untuk pengurutan berdasarkan tab
        if (request()->has('tab')) {
            if (request('tab') == Purchase::TAB_SUBMIT) {
                $purchases->orderBy('date', 'desc');
            } elseif (in_array(request('tab'), [Purchase::TAB_VERIFIED, Purchase::TAB_PAYMENT_REQUEST])) {
                $purchases->orderBy('due_date', 'asc');
            } elseif (request('tab') == Purchase::TAB_PAID) {
                $purchases->orderBy('updated_at', 'desc');
            }
        } else {
            // Jika tidak ada tab yang dipilih, urutkan berdasarkan date secara descending
            $purchases->orderBy('date', 'desc');
        }
    
        $purchases = $purchases->paginate($request->per_page);
    
        return new PurchaseCollection($purchases);
    }
    
    public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        try {
             // Validasi untuk PPN
            $ppn = $request->tax_ppn;
            if (!preg_match('/^\d+(\.\d+)?%?$/', $ppn)) {
                DB::rollBack();
                return MessageActeeve::error("Format PPN tidak valid. Harap masukkan nilai PPN dalam format persen tanpa menggunakan koma.");
            }

            // Mendapatkan proyek yang diinginkan
            $project = null;

            // Jika pembelian adalah operasional, maka tidak perlu mengambil proyek
            if ($request->purchase_id == Purchase::TYPE_OPERATIONAL) {
                $project = null; // Set proyek menjadi null untuk pembelian operasional
            } else {
                // Jika pembelian adalah event, maka cek proyek yang diinginkan
                $project = Project::find($request->project_id);

                // Melakukan pengecekan jika proyek tidak ada atau statusnya tidak aktif
                if (!$project || $project->status != Project::ACTIVE) {
                    DB::rollBack();
                    return MessageActeeve::error("Proyek tidak tersedia atau tidak aktif.");
                }
            }

            $purchaseMax = Purchase::where('purchase_category_id', $request->purchase_category_id)->max('doc_no');
            $purchaseCategory = PurchaseCategory::find($request->purchase_category_id);

            $company = Company::find($request->client_id);
            if ($company->contact_type_id != ContactType::VENDOR) {
                return MessageActeeve::warning("this contact is not a vendor type");
            }

            $request->merge([
                'doc_no' => $this->generateDocNo($purchaseMax, $purchaseCategory),
                'doc_type' => Str::upper($purchaseCategory->name),
                'purchase_status_id' => PurchaseStatus::AWAITING,
                'company_id' => $company->id,
                'ppn' => $request->tax_ppn,
                'user_id' => auth()->user()->id
            ]);

            // Jika pembelian adalah operasional, set project_id menjadi null
            if ($request->purchase_id == Purchase::TYPE_OPERATIONAL) {
                $request->merge([
                    'project_id' => null,
                ]);
            }

            $purchase = Purchase::create($request->all());

            // Periksa apakah ada file yang dilampirkan sebelum melakukan iterasi foreach
            if ($request->hasFile('attachment_file')) {
                foreach ($request->file('attachment_file') as $key => $file) {
                    $this->saveDocument($purchase, $file, $key + 1);
                }
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
                "log" => $purchase->logs()->select('name', 'created_at')->where('note_reject', null)->latest()->first(),
                "logs_rejected" => $purchase->logs()->select('name', 'note_reject', 'created_at')->where('note_reject', '!=', null)->orderBy('id', 'desc')->get(),
                "created_at" => $purchase->created_at->format('Y-m-d'),
                "updated_at" => $purchase->updated_at->format('Y-m-d'),  
            ];

            if ($purchase->user) {
                $data['created_by'] = [
                    "id" => $purchase->user->id,
                    "name" => $purchase->user->name,
                ];
            }

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
        if ($company->contact_type_id != ContactType::VENDOR) {
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

            Purchase::whereDocNo($docNo)->update($request->except(['_method', 'attachment_file', 'tax_ppn', 'client_id']));

            DB::commit();
            return MessageActeeve::success("doc no $docNo has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function activate(UpdateRequest $request, $docNo)
    {
        $request->merge([
            'purchase_status_id' => PurchaseStatus::AWAITING
        ]);
        return $this->update($request, $docNo);
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
        $request->merge([
            'purchase_status_id' => PurchaseStatus::VERIFIED,
            'tab' => Purchase::TAB_VERIFIED,
        ]);

        if ($pph && (strtolower($pph->type) != Tax::TAX_PPH)) {
            return MessageActeeve::warning("this tax is not a pph type");
        }

        if ($pph) {
           $request -> merge([
               'pph' => $pph -> id 
           ]); 
        }
        

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
            $purchase->logs()->create([
                'tab' => Purchase::TAB_SUBMIT,
                'name' => auth()->user()->name,
                'note_reject' => $request->note
            ]);

            Purchase::whereDocNo($docNo)->update([
                'purchase_status_id' => PurchaseStatus::REJECTED,
                'reject_note' => $request->note,
                'tab' => Purchase::TAB_SUBMIT
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
                // 'note_reject' => $request->note,
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

    public function updatepayment(Request $request, $docNo)
    {
        DB::beginTransaction();

        try {
            // Ubah pengecekan model menjadi berdasarkan doc_no
            $purchase = Purchase::whereDocNo($docNo)->first();
            if (!$purchase) {
                return MessageActeeve::notFound('Data not found!');
            }

            $purchase->logs()->create([
                'tab' => Purchase::TAB_PAID,
                'name' => auth()->user()->name,
                'updated_at' => $request->updated_at
            ]);

            Purchase::whereDocNo($docNo)->update([
                'purchase_status_id' => PurchaseStatus::PAID,
                'updated_at' => $request->updated_at,
                'tab' => Purchase::TAB_PAID
            ]);

            DB::commit();
            return MessageActeeve::success("Purchase $docNo payment successfully");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }   
    }

    public function updateproject(Request $request, $docNo)
        {
            DB::beginTransaction();

            try {
                // Ubah pengecekan model menjadi berdasarkan doc_no
                $purchase = Purchase::whereDocNo($docNo)->first();
                if (!$purchase) {
                    return MessageActeeve::notFound('Data not found!');
                }

                // Dapatkan proyek yang terkait dengan pembelian
                $project = Project::find($request->project_id);

                // Periksa apakah proyek ada dan aktif
                if (!$project || $project->status != Project::ACTIVE) {
                    DB::rollBack();
                    return MessageActeeve::error("Proyek tidak tersedia atau tidak aktif.");
                }

                // Tambahkan log pembayaran
                $purchase->logs()->create([
                    'tab' => Purchase::TAB_PAID,
                    'name' => auth()->user()->name,
                    'project_id' => $request->input('project_id'),
                ]);

                Purchase::whereDocNo($docNo)->update([
                    'purchase_status_id' => PurchaseStatus::PAID,
                    'project_id' => $request->input('project_id'),
                    'tab' => Purchase::TAB_PAID
                ]);

                DB::commit();
                return MessageActeeve::success("Project ID for Purchase $docNo updated successfully");
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

            $params = ['tab' => $purchase->tab - 1];

            if ($purchase->tab == Purchase::TAB_VERIFIED) {
                $params['purchase_status_id'] = PurchaseStatus::AWAITING;
            }

            Purchase::whereDocNo($docNo)->update($params);

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
            Storage::delete($purchase->file_path);
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
                "name" => $document->purchase->doc_type . "/$document->doc_no.$document->id/" . date('Y', strtotime($document->created_at)) . "." . pathinfo($document->file_path, PATHINFO_EXTENSION),
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

        if ($purchase->purchase_status_id == PurchaseStatus::REJECTED) {
            $data["note"] = $purchase->reject_note;
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

    // protected function getPpn($purchase)
    // {
    //     return ($purchase->sub_total * $purchase->ppn) / 100;
    // }

    // protected function getPph($purchase)
    // {
    //     // Hitung hasil PPH 
    //     $pphResult = round((($purchase->sub_total) * $purchase->taxPph->percent) / 100);

    //     // Ubah nilai pph_hasil menjadi nilai yang dibulatkan
    //     return [
    //         "pph_type" => $purchase->taxPph->name,
    //         "pph_rate" => $purchase->taxPph->percent,
    //         "pph_hasil" => $pphResult
    //     ];
    // }

    protected function getPpn($purchase)
    {
        if (is_numeric($purchase->ppn)) {
            return ($purchase->sub_total * $purchase->ppn) / 100;
        } else {
            return 0; // Atau nilai default lainnya jika ppn bukan numerik
        }
    }

    protected function getPph($purchase)
    {
        if (is_numeric($purchase->pph)) {
            // Hitung hasil PPH 
            $pphResult = round((($purchase->sub_total) * $purchase->taxPph->percent) / 100);

            // Ubah nilai pph_hasil menjadi nilai yang dibulatkan
            return [
                "pph_type" => $purchase->taxPph->name,
                "pph_rate" => $purchase->taxPph->percent,
                "pph_hasil" => $pphResult
            ];
        } else {
            return [
                "pph_type" => "", // Atau nilai default lainnya jika pph bukan numerik
                "pph_rate" => 0,
                "pph_hasil" => 0
            ];
        }
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

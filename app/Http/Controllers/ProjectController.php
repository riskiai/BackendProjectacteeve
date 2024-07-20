<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Company;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\ContactType;
use Illuminate\Http\Request;
use App\Models\PurchaseStatus;
use App\Facades\MessageActeeve;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Facades\Filters\Purchase\ByTab;
use App\Facades\Filters\Purchase\ByTax;
use Illuminate\Support\Facades\Storage;
use App\Facades\Filters\Purchase\ByDate;
use App\Facades\Filters\Purchase\BySearch;
use App\Facades\Filters\Purchase\ByStatus;
use App\Facades\Filters\Purchase\ByVendor;
use App\Facades\Filters\Purchase\ByProject;
use App\Http\Requests\Project\CreateRequest;
use App\Http\Requests\Project\UpdateRequest;
use App\Http\Resources\Project\ProjectCollection;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query();

        if (auth()->user()->role_id == Role::USER) {
            // Jika pengguna adalah 'USER', tampilkan semua proyek yang aktif di pembelian
            $query->whereHas('purchases', function ($query) {
                $query->where('status', Project::ACTIVE)
                      ->where('user_id', auth()->user()->id);
            });
        } else {
            // Jika bukan pengguna biasa, tampilkan semua proyek
            $query->whereNotNull('id');
        }
                 
        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('id', 'like', '%' . $request->search . '%');
                $query->orWhere('name', 'like', '%' . $request->search . '%');
                $query->orWhereHas('company', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                });
            });
        }

       // Filter berdasarkan status project
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('status_cost_progress')) {
            $statusCostProgress = $request->status_cost_progress;
            $query->where('status_cost_progress', $statusCostProgress);
        }

        // Lakukan filter berdasarkan project jika ada
        if ($request->has('project')) {
            $query->where('id', $request->project);
        }

        if ($request->has('vendor')) {
            $query->where('company_id', $request->vendor);
        }

        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);
        
            $query->whereBetween('date', $date); // Ganti 'created_at' sesuai dengan kolom yang sesuai
        }

        if ($request->has('year')) {
            $year = $request->year;
            $query->whereYear('date', $year);
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate($request->per_page);

        return new ProjectCollection($projects);
    }

    // public function projectall(Request $request) {

    //     $query = Project::query();

    //      // Filter berdasarkan status project
    //      if ($request->has('status')) {
    //         $query->where('status', $request->status);
    //     }

    //     if ($request->has('date')) {
    //         $date = str_replace(['[', ']'], '', $request->date);
    //         $date = explode(", ", $date);

    //         $query->whereBetween('created_at', $date);
    //     }

    //     $projects = $query->orderBy('created_at', 'desc')->get();

    //     return new ProjectCollection($projects);

    // }

    public function projectall(Request $request)
    {
        $query = Project::query();

        // Filter berdasarkan status proyek
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);

            $query->whereBetween('created_at', $date);
        }

        // Tambahkan kondisi untuk menyortir data berdasarkan nama proyek
        $query->orderBy('name', 'asc');

        // Ambil daftar proyek yang sudah diurutkan
        $projects = $query->get();

        return new ProjectCollection($projects);
    }


    public function counting(Request $request)
    {
        $query = Project::query();
        $query->select(
            DB::raw('SUM(billing) as billing'),
            DB::raw('SUM(cost_estimate) as cost_estimate'),
            DB::raw('SUM(margin) as margin')
        );

        if (auth()->user()->role_id == Role::USER) {
            $query->where(function ($query) {
                $query->whereHas('purchases', function ($query) {
                    $query->where('user_id', auth()->user()->id);
                });
            });
        }

        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('id', 'like', '%' . $request->search . '%');
                $query->orWhere('name', 'like', '%' . $request->search . '%');
                $query->orWhereHas('company', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                });
            });
        }

        // Lakukan filter berdasarkan project jika ada
        if ($request->has('project')) {
            $query->where('id', $request->project);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('vendor')) {
            $query->where('company_id', $request->vendor);
        }

        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);

            $query->whereBetween('created_at', $date);
        }
        $projectStats = $query->first();

        if (!$projectStats->billing || !$projectStats->margin || !$projectStats->cost_estimate) {
            return [
                "billing" => 0,
                "cost_estimate" => 0,
                "margin" => 0,
                "percent" => '0%',
            ];
        }

        $percent = ($projectStats->margin / $projectStats->billing) * 100;
        $percent = round($percent, 2) . "%";

        return [
            "billing" => $projectStats->billing,
            "cost_estimate" => $projectStats->cost_estimate,
            "margin" => $projectStats->margin,
            "percent" => $percent,
        ];
    }

    public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        $company = Company::find($request->client_id);
        if ($company->contact_type_id != ContactType::CLIENT) {
            return MessageActeeve::warning("this contact is not a client type");
        }

        try {
            // Persiapkan data yang akan disimpan
            $data = [
                'name' => $request->name,
                'billing' => $request->billing,
                'cost_estimate' => $request->cost_estimate,
                'margin' => $request->margin,
                'percent' => $request->percent,
                'date' => $request->date,
                'company_id' => $company->id,
                'user_id' => auth()->user()->id,
            ];

            // Periksa apakah file dilampirkan sebelum menyimpannya
            if ($request->hasFile('attachment_file')) {
                $data['file'] = $request->file('attachment_file')->store(Project::ATTACHMENT_FILE);
            }

            // Buat proyek dengan data yang sudah disiapkan
            $project = Project::create($data);

            DB::commit();
            return MessageActeeve::success("project $project->name has been created");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }


    public function show($id)
    {
        $project = Project::find($id);
        if (!$project) {
            return MessageActeeve::notFound('data not found!');
        }
    
        $file_attachment = null;
    
        // Periksa apakah ada file attachment
        if ($project->file) {
            $file_attachment = [
                'name' => date('Y', strtotime($project->created_at)) . '/' . $project->id . '.' . pathinfo($project->file, PATHINFO_EXTENSION),
                'link' => asset("storage/$project->file")
            ];
        }
    
        return MessageActeeve::render([
            'id' => $project->id,
            'client' => [
                'id' => $project->company->id,
                'name' => $project->company->name,
                'contact_type' => $project->company->contactType->name,
            ],
            'date' => $project->date,
            'name' => $project->name,
            'billing' => $project->billing,
            'cost_estimate' => $project->cost_estimate,
            'margin' => $project->margin,
            'percent' => round($project->percent, 2),
            'file_attachment' => $file_attachment,
            'cost_progress' => $this->costProgress($project),
            'status' => $this->getStatus($project->status),
            'created_at' => $project->created_at,
            'updated_at' => $project->updated_at,
        ]);
    }
    

    public function invoice(Request $request, $id)
    {
        $project = Project::find($id);
        if (!$project) {
            return MessageActeeve::notFound('data not found!');
        }

        $data = [];
        $purchases = app(Pipeline::class)
            ->send(Purchase::query())
            ->through([
                ByTab::class,
                ByDate::class,
                ByStatus::class,
                ByVendor::class,
                ByProject::class,
                ByTax::class,
                BySearch::class,
            ])
            ->thenReturn()
            ->where('project_id', $id)
            ->orderBy('date', 'desc')
            ->paginate($request->per_page);

        foreach ($purchases as $purchase) {
            if ($purchase->purchase_status_id != PurchaseStatus::REJECTED) {
                $data[] = [
                    "date" => $purchase->date,
                    "contact" => $purchase->company->name,
                    "description" => $purchase->description,
                    "total" => $purchase->total,
                    "status" => [
                        $purchase->purchase_status_id,
                        $purchase->purchaseStatus->name
                    ]
                ];
            }
        }

        return MessageActeeve::render([
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            'data' => $data,
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'from' => $purchases->firstItem(),
                'last_page' => $purchases->lastPage(),
                'path' => $purchases->path(),
                'per_page' => $purchases->perPage(),
                'to' => $purchases->lastItem(),
                'total' => $purchases->total(),
            ]
        ]);
    }

    public function update(UpdateRequest $request, $id)
    {
        DB::beginTransaction();

        $company = Company::find($request->client_id);
        $project = Project::find($id);
        if (!$project) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            $request->merge([
                'company_id' => $company->id,
            ]);

            if ($request->hasFile('attachment_file')) {
                Storage::delete($project->file);
                $request->merge([
                    'file' => $request->file('attachment_file')->store(Project::ATTACHMENT_FILE),
                ]);
            }

            $project->update($request->all());

            DB::commit();
            return MessageActeeve::success("project $project->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function accept($id)
    {
        DB::beginTransaction();

        $project = Project::find($id);
        if (!$project) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            $project->update([
                "status" => Project::ACTIVE
            ]);

            DB::commit();
            return MessageActeeve::success("project $project->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function reject($id)
    {
        DB::beginTransaction();

        $project = Project::find($id);
        if (!$project) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            $project->update([
                "status" => Project::REJECTED
            ]);

            DB::commit();
            return MessageActeeve::success("project $project->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        $project = Project::find($id);
        if (!$project) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            $project->delete();

            DB::commit();
            return MessageActeeve::success("project $project->name has been deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    protected function getStatus($status)
    {
        $data = [
            "id" => $status,
            "name" => "Pending"
        ];

        if ($status == Project::ACTIVE) {
            return [
                "id" => $status,
                "name" => "Active"
            ];
        }

        if ($status == Project::REJECTED) {
            return [
                "id" => $status,
                "name" => "Rejected"
            ];
        }

        return $data;
    }

    protected function costProgress($project)
    {
        $status = Project::STATUS_OPEN;
        $total = 0;

        $purchases = $project->purchases()->where('tab', Purchase::TAB_PAID)->get();

        foreach ($purchases as $purchase) {
            $total += $purchase->sub_total;
        }

        // $costEstimate = round(($total / $project->billing) * 100, 2);
        $costEstimate = round(($total / $project->cost_estimate) * 100, 2);
        if ($costEstimate > 90) {
            $status = Project::STATUS_NEED_TO_CHECK;
        }

        if ($costEstimate == 100) {
            $status = Project::STATUS_CLOSED;
        }

       // Simpan nilai status cost progress ke dalam model Project
         $project->update(['status_cost_progress' => $status]);

        return [
            'status_cost_progress' => $status,
            // 'status' => $status,
            'percent' => $costEstimate . '%',
            'real_cost' => $total
        ];
    }
}
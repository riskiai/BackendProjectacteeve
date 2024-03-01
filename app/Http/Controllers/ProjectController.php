<?php

namespace App\Http\Controllers;

use App\Facades\MessageActeeve;
use App\Http\Requests\Project\CreateRequest;
use App\Http\Requests\Project\UpdateRequest;
use App\Http\Resources\Project\ProjectCollection;
use App\Models\Company;
use App\Models\ContactType;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query();

        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('id', 'like', '%' . $request->search . '%');
                $query->orWhere('name', 'like', '%' . $request->search . '%');
                $query->orWhereHas('company', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                });
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);

            $query->whereBetween('created_at', $date);
        }

        $projects = $query->paginate($request->per_page);

        return new ProjectCollection($projects);
    }

    public function counting(Request $request)
    {
        $project = Project::select(
            DB::raw('SUM(billing) as billing'),
            DB::raw('SUM(cost_estimate) as cost_estimate'),
            DB::raw('SUM(margin) as margin')
        )->first();

        // Membuat perhitungan persentase dari total billing ke total margin
        $percent = ($project->margin / $project->billing) * 100;
        $percent = round($percent, 2) . "%";

        return [
            "billing" => $project->billing,
            "cost_estimate" => $project->cost_estimate,
            "margin" => $project->margin,
            "percent" => $percent,
        ];
    }


    // public function counting(Request $request)
    //     {
    //         $project = Project::select(
    //             DB::raw('SUM(billing) as billing'),
    //             DB::raw('SUM(cost_estimate) as cost_estimate'),
    //             DB::raw('SUM(margin) as margin')
    //         )->first();

    //         // Membuat perhitungan persentase dari total billing ke total margin
    //         $percent = ($project->billing / $project->margin) * 100;
    //         $percent = round($percent, 2) . "%";

    //         $data = [
    //             [
    //                 'title' => 'BILLING',
    //                 'amount' => $project->billing
    //             ],
    //             [
    //                 'title' => 'COST ESTIMATE',
    //                 'amount' => $project->cost_estimate
    //             ],
    //             [
    //                 'title' => 'MARGIN',
    //                 'amount' => $project->margin
    //             ],
    //             [
    //                 'title' => 'PERCENT',
    //                 'amount' => $percent
    //             ]
    //         ];

    //         return [
    //             'status' => MessageActeeve::SUCCESS,
    //             'status_code' => MessageActeeve::HTTP_OK,
    //             'data' => $data
    //         ];
    //     }


    public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        $company = Company::find($request->client_id);
        if ($company->contact_type_id != ContactType::CLIENT) {
            return MessageActeeve::warning("this contact is not a client type");
        }

        try {
            $request->merge([
                'company_id' => $company->id,
                'file' => $request->file('attachment_file')->store(Project::ATTACHMENT_FILE)
            ]);

            $project = Project::create($request->all());

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

        return MessageActeeve::render([
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            'data' => [
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
                'percent' => $project->percent,
                'file_attachment' => [
                    'name' => date('Y', strtotime($project->created_at)) . '/' . $project->id . '.pdf',
                    'link' => asset("storage/$project->file")
                ],
                'cost_progress' => $this->costProgress($project),
                'status' => $this->getStatus($project->status),
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
            ]
        ]);
    }

    public function invoice($id)
    {
        $project = Project::find($id);
        if (!$project) {
            return MessageActeeve::notFound('data not found!');
        }

        $data = [];

        foreach ($project->purchases as $purchase) {
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

        return MessageActeeve::render([
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            'data' => $data
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

        foreach ($project->purchases as $purchase) {
            $total += $purchase->sub_total;
        }

        $costEstimate = round(($total / $project->billing) * 100, 2);
        if ($costEstimate > 90) {
            $status = Project::STATUS_NEED_TO_CHECK;
        }

        if ($costEstimate == 100) {
            $status = Project::STATUS_CLOSED;
        }

        return [
            'status' => $status,
            'percent' => $costEstimate . '%',
            'real_cost' => $total
        ];
    }
}

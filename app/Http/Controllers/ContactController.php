<?php

namespace App\Http\Controllers;

use App\Facades\MessageActeeve;
use App\Http\Requests\Contact\StoreRequest;
use App\Http\Requests\Contact\UpdateRequest;
use App\Http\Resources\Contact\ContactCollection;
use App\Http\Resources\Contact\ContactDetail;
use App\Models\Company;
use App\Models\ContactType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContactController extends Controller
{
    /**
     * function index
     *
     * @param Request $request => digunakan untuk menangkap filter
     * @return void
     */
    public function index(Request $request)
    {
        // inisiasi company/contact dalam bentuk query, supaya bisa dilakukan untuk filtering
        $query = Company::query();

        if ($request->has('contact_type')) {
            $query->where('contact_type_id', $request->contact_type);
        }

        // pembuatan kondisi ketika params search
        if ($request->has('search')) {
            // maka lakukan query bersarang seperti dibawah ini
            // $query->where(func...{}) => query akan berjalan jika kondisi didalamnya terpenuhi
            $query->where(function ($query) use ($request) {
                // query ini digunakan untuk filtering data
                $query->where('name', 'like', "%$request->search%")
                    ->orWhere('pic_name', 'like', "%$request->search%")
                    ->orWhere('phone', 'like', "%$request->search%")
                    ->orWhere('bank_name', 'like', "%$request->search%")
                    ->orWhere('account_name', 'like', "%$request->search%")
                    ->orWhere('account_number', 'like', "%$request->search%")
                    ->orWhereHas('contactType', function ($query) use ($request) { // query ini digunakan jika ada yang mencari ke arah relasinya, artinya sama seperti baris ke 26
                        $query->where('name', 'like', "%$request->search%");
                    });
            });
        }

        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);

            $query->whereBetween('created_at', $date);
        }


        // keluaran dari index ini merupakan paginate
        $contacts = $query->paginate($request->per_page);

        // untuk index pengelolaan datanya terpisah file
        // untuk mempertahankan filtering bawaan paginate laravel
        // pembuatan file bisa menggunakan command `php artisan make:resource NamaFile`
        return new ContactCollection($contacts);
    }


    public function contactall(Request $request)
    {
        // inisiasi company/contact dalam bentuk query, supaya bisa dilakukan untuk filtering
        $query = Company::query();

        if ($request->has('contact_type')) {
            $query->where('contact_type_id', $request->contact_type);
        }

        // pembuatan kondisi ketika params search
        if ($request->has('search')) {
            // maka lakukan query bersarang seperti dibawah ini
            // $query->where(func...{}) => query akan berjalan jika kondisi didalamnya terpenuhi
            $query->where(function ($query) use ($request) {
                // query ini digunakan untuk filtering data
                $query->where('name', 'like', "%$request->search%")
                    ->orWhere('pic_name', 'like', "%$request->search%")
                    ->orWhere('phone', 'like', "%$request->search%")
                    ->orWhere('bank_name', 'like', "%$request->search%")
                    ->orWhere('account_name', 'like', "%$request->search%")
                    ->orWhere('account_number', 'like', "%$request->search%")
                    ->orWhereHas('contactType', function ($query) use ($request) { // query ini digunakan jika ada yang mencari ke arah relasinya, artinya sama seperti baris ke 26
                        $query->where('name', 'like', "%$request->search%");
                    });
            });
        }

        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);

            $query->whereBetween('created_at', $date);
        }

        // Tambahkan kondisi untuk menyortir data berdasarkan nama perusahaan jika jenis kontak adalah vendor
        if ($request->has('contact_type') && $request->contact_type == ContactType::VENDOR) {
            $query->orderBy('name', 'asc');
        }


        // keluaran dari index ini merupakan paginate
        $contacts = $query->get();

        // untuk index pengelolaan datanya terpisah file
        // untuk mempertahankan filtering bawaan paginate laravel
        // pembuatan file bisa menggunakan command `php artisan make:resource NamaFile`
        return new ContactCollection($contacts);
    }

    /**
     * function store
     *
     * @param StoreRequest $request => validasi dibuat terpisah menggunakan command `php artisan make:request NamaFile`
     * @return void
     */
    public function store(StoreRequest $request)
    {
        DB::beginTransaction();

        $contactType = ContactType::find($request->contact_type);

        try {
            // fungsi ini digunakan untuk menggabungkan key:value baru kedalam request yang sudah ada
            $npwpPath = $request->file('attachment_npwp')->store(Company::ATTACHMENT_NPWP);
            $filePath = $request->hasFile('attachment_file') ? $request->file('attachment_file')->store(Company::ATTACHMENT_FILE) : null;

            $requestData = $request->all();
            $requestData['contact_type_id'] = $contactType->id;
            $requestData['npwp'] = $npwpPath;
            $requestData['file'] = $filePath;

            $contact = Company::create($requestData);

            DB::commit();
            return MessageActeeve::success("contact $contact->name has been created");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }


    /**
     * function show
     *
     * @param var $id
     * @return void
     */
    public function show($id)
    {
        $contact = Company::find($id);
        if (!$contact) {
            return MessageActeeve::notFound('data not found!');
        }

        // Mengambil URL attachment_npwp dan attachment_file hanya jika file tersebut ada
        $attachment_npwp = $contact->npwp ? asset("storage/$contact->npwp") : null;
        $attachment_file = $contact->file ? asset("storage/$contact->file") : null;

        return MessageActeeve::render([
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            'data' =>  [
                "id" => $contact->id,
                "uuid" => $this->generateUuid($contact),
                "contact_type" => [
                    "id" => $contact->contactType->id,
                    "name" => $contact->contactType->name,
                ],
                "name" => $contact->name,
                "address" => $contact->address,
                "attachment_npwp" => $attachment_npwp,
                "pic_name" => $contact->pic_name,
                "phone" => $contact->phone,
                "email" => $contact->email,
                "attachment_file" => $attachment_file,
                "bank_name" => $contact->bank_name,
                "branch" => $contact->branch,
                "account_name" => $contact->account_name,
                "currency" => $contact->currency,
                "account_number" => $contact->account_number,
                "swift_code" => $contact->swift_code,
                "created_at" => $contact->created_at,
                "updated_at" => $contact->updated_at
            ]
        ]);
    }


    public function showByContactType(Request $request)
    {
         // inisiasi company/contact dalam bentuk query, supaya bisa dilakukan untuk filtering
         $query = Company::query();

         if ($request->has('contact_type')) {
             $query->where('contact_type_id', $request->contact_type);
         }
 
         // pembuatan kondisi ketika params search
         if ($request->has('search')) {
             // maka lakukan query bersarang seperti dibawah ini
             // $query->where(func...{}) => query akan berjalan jika kondisi didalamnya terpenuhi
             $query->where(function ($query) use ($request) {
                 // query ini digunakan untuk filtering data
                 $query->where('name', 'like', "%$request->search%")
                     ->orWhere('pic_name', 'like', "%$request->search%")
                     ->orWhere('phone', 'like', "%$request->search%")
                     ->orWhere('bank_name', 'like', "%$request->search%")
                     ->orWhere('account_name', 'like', "%$request->search%")
                     ->orWhere('account_number', 'like', "%$request->search%")
                     ->orWhereHas('contactType', function ($query) use ($request) { // query ini digunakan jika ada yang mencari ke arah relasinya, artinya sama seperti baris ke 26
                         $query->where('name', 'like', "%$request->search%");
                     });
             });
         }
 
         // keluaran dari index ini merupakan paginate
         $contacts = $query->paginate($request->per_page);
 
         // untuk index pengelolaan datanya terpisah file
         // untuk mempertahankan filtering bawaan paginate laravel
         // pembuatan file bisa menggunakan command `php artisan make:resource NamaFile`
         return new ContactCollection($contacts);
    }


    /**
     * function update
     *
     * @param UpdateRequest $request => validasi dibuat terpisah menggunakan command `php artisan make:request NamaFile`
     * @param var $id
     * @return void
     */
    public function update(UpdateRequest $request, $id)
    {
        DB::beginTransaction();

        $contactType = ContactType::find($request->contact_type);
        $request->merge([
            'contact_type_id' => $contactType->id,
        ]);

        $contact = Company::find($id);
        if (!$contact) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            // karena attachment npwp tidak wajib diisi maka diperlukan pengecekan
            // guna untuk tahu apakah perlu di update atau tidak
            if ($request->hasFile('attachment_npwp')) {
                // hapus storage sebelumnya
                Storage::delete($contact->npwp);
                // lalu ganti dengan yang baru
                $request->merge([
                    'npwp' => $request->file('attachment_npwp')->store(Company::ATTACHMENT_NPWP),
                ]);
            }

            if ($request->hasFile('attachment_file')) {
                Storage::delete($contact->file);
                $request->merge([
                    'file' => $request->file('attachment_file')->store(Company::ATTACHMENT_FILE),
                ]);
            }

            $contact->update($request->all());

            DB::commit();
            return MessageActeeve::success("contact $contact->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    /**
     * function destroy / delete
     *
     * @param var $id
     * @return void
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        $contact = Company::find($id);
        if (!$contact) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            // cek kondisi jika npwp / file tersedia
            // maka storage tersebut akan dihapus
            if ($contact->npwp) {
                Storage::delete($contact->npwp);
            }

            if ($contact->file) {
                Storage::delete($contact->file);
            }

            $contact->delete();

            DB::commit();
            return MessageActeeve::success("contact $contact->name has been deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    protected function generateUuid($contact)
    {
        $id = str_pad($contact->id, 3, 0, STR_PAD_LEFT);
        if ($contact->contactType->id == ContactType::VENDOR) {
            return ContactType::SHORT_VENDOR . $id;
        }

        return ContactType::SHORT_CLIENT . $id;
    }
}

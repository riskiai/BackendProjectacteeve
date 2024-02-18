<?php

namespace App\Http\Resources\Contact;

use App\Models\ContactType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ContactCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [];

        foreach ($this as $contact) {
            $data[] = [
                "id" => $contact->id,
                "uuid" => $this->generateUuid($contact),
                "contact_type" => [
                    "id" => $contact->contactType->id,
                    "name" => $contact->contactType->name,
                ],
                "name" => $contact->name,
                "address" => $contact->address,
                "attachment_npwp" => asset("storage/$contact->npwp"),
                "pic_name" => $contact->pic_name,
                "phone" => $contact->phone,
                "email" => $contact->email,
                "attachment_file" => asset("storage/$contact->file"),
                "bank_name" => $contact->bank_name,
                "branch" => $contact->branch,
                "account_name" => $contact->account_name,
                "currency" => $contact->currency,
                "account_number" => $contact->account_number,
                "swift_code" => $contact->swift_code,
                "created_at" => $contact->created_at,
                "updated_at" => $contact->updated_at
            ];
        }

        return $data;
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

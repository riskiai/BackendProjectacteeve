<?php

namespace App\Http\Requests\Contact;

use App\Facades\MessageActeeve;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact_type' => 'required|exists:contact_type,id',
            'name' => 'required|max:255',
            'address' => 'required|max:255',
            'bank_name' => 'required|max:255',
            'branch' => 'required|max:255',
            'account_name' => 'required|max:255',
            'currency' => 'required|max:255',
            'account_number' => 'required|numeric',
            'swift_code' => 'required|max:255',
            'attachment_npwp' => 'required|file|mimes:pdf',
            'pic_name' => 'required|max:255',
            'phone' => 'required|numeric',
            'email' => 'required|email|max:255',
            'attachment_file' => 'required|file|mimes:pdf',
        ];
    }

    /**
     * function attributes => digunakan untuk merubah attribut dari definisi awal menjadi nama baru
     *
     * @return void
     */
    public function attributes()
    {
        return [
            'attachment_npwp' => 'attachment npwp / ktp'
        ];
    }

    /**
     * function failedValidation => merupakan standar response validasi jika menggunakan `api`
     *
     * @param Validator $validator
     * @return void
     */
    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse([
            'status' => MessageActeeve::WARNING,
            'status_code' => MessageActeeve::HTTP_UNPROCESSABLE_ENTITY,
            'message' => $validator->errors()
        ], MessageActeeve::HTTP_UNPROCESSABLE_ENTITY);

        throw new ValidationException($validator, $response);
    }
}
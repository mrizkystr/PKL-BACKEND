<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalesCodeImportRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Atur ke true jika semua pengguna diizinkan untuk mengakses request ini
    }

    public function rules()
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048', // Aturan untuk file yang diunggah
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalesCodeRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Atur ke true jika semua pengguna diizinkan untuk mengakses request ini
    }

    public function rules()
    {
        return [
            'mitra_nama' => 'nullable|string',
            'nama' => 'nullable|string',
            'sto' => 'nullable|string',
            'id_mitra' => 'nullable|string',
            'nama_mitra' => 'nullable|string',
            'role' => 'nullable|string',
            'kode_agen' => 'nullable|string',
            'kode_baru' => 'nullable|string',
            'no_telp_valid' => 'nullable|string',
            'nama_sa_2' => 'nullable|string',
            'status_wpi' => 'nullable|string',
        ];
    }
}
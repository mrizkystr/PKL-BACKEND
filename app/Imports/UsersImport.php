<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow
{
    /**
     * Buat model user dari setiap baris data di Excel.
     */
    public function model(array $row)
    {
        return new User([
            'name' => $row['name'],       // Nama kolom di Excel
            'email' => $row['email'],     // Pastikan header sesuai
            'username' => $row['username'],
            'password' => Hash::make($row['password']),
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesCodes extends Model
{
    use HasFactory;

    protected $table = 'sales_codes'; // Pastikan tabel benar
    protected $fillable = [
        'mitra_nama',
        'nama',
        'sto',
        'id_mitra',
        'nama_mitra',
        'role',
        'kode_agen',
        'kode_baru',
        'no_telp_valid',
        'nama_sa_2',
        'status_wpi',
    ];
}

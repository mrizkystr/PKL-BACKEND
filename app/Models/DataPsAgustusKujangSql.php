<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPsAgustusKujangSql extends Model
{
    protected $table = 'data_ps_agustus_kujang_sql';
    
    protected $fillable = [
        'id',
        'ORDER_ID',
        'REGIONAL',
        'REGIONAL_OLD',
        'WITEL',
        'WITEL_OLD',
        'DATEL',
        'STO',
        'UNIT',
        'JENISPSB',
        'TYPE_TRANS',
        'TYPE_LAYANAN',
        'STATUS_RESUME',
        'PROVIDER',
        'ORDER_DATE',
        'LAST_UPDATED_DATE',
        'NCLI',
        'POTS',
        'SPEEDY',
        'CUSTOMER_NAME',
        'LOC_ID',
        'WONUM',
        'FLAG_DEPOSIT',
        'CONTACT_HP',
        'INS_ADDRESS',
        'GPS_LONGITUDE',
        'GPS_LATITUDE',
        'KCONTACT',
        'CHANNEL',
        'STATUS_INET',
        'STATUS_ONU',
        'UPLOAD',
        'DOWNLOAD',
        'LAST_PROGRAM',
        'STATUS_VOICE',
        'CLID',
        'LAST_START',
        'TINDAK_LANJUT',
        'ISI_COMMENT',
        'USER_ID_TL',
        'TGL_COMMENT',
        'TANGGAL_MANJA',
        'KELOMPOK_KENDALA',
        'KELOMPOK_STATUS',
        'HERO',
        'ADDON',
        'TGL_PS',
        'STATUS_MESSAGE',
        'PACKAGE_NAME',
        'GROUP_PAKET',
        'REASON_CANCEL',
        'KETERANGAN_CANCEL',
        'TGL_MANJA',
        'DETAIL_MANJA',
        'Bulan_PS',
        'Kode_sales',
        'Nama_SA',
        'Mitra',
        'Ekosistem',
    ];

    public $timestamps = false;
}

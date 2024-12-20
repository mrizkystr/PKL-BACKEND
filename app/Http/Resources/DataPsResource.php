<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DataPsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_id' => $this->ORDER_ID,
            'regional' => $this->REGIONAL,
            'regional_old' => $this->REGIONAL_OLD,
            'witel' => $this->WITEL,
            'witel_old' => $this->WITEL_OLD,
            'datel' => $this->DATEL,
            'sto' => $this->STO,
            'unit' => $this->UNIT,
            'jenis_psb' => $this->JENISPSB,
            'type_trans' => $this->TYPE_TRANS,
            'type_layanan' => $this->TYPE_LAYANAN,
            'status_resume' => $this->STATUS_RESUME,
            'provider' => $this->PROVIDER,
            'order_date' => $this->ORDER_DATE,
            'last_updated_date' => $this->LAST_UPDATED_DATE,
            'ncli' => $this->NCLI,
            'pots' => $this->POTS,
            'speedy' => $this->SPEEDY,
            'customer_name' => $this->CUSTOMER_NAME,
            'loc_id' => $this->LOC_ID,
            'wonum' => $this->WONUM,
            'flag_deposit' => $this->FLAG_DEPOSIT,
            'contact_hp' => $this->CONTACT_HP,
            'ins_address' => $this->INS_ADDRESS,
            'gps_longitude' => $this->GPS_LONGITUDE,
            'gps_latitude' => $this->GPS_LATITUDE,
            'kcontact' => $this->KCONTACT,
            'channel' => $this->CHANNEL,
            'status_inet' => $this->STATUS_INET,
            'status_onu' => $this->STATUS_ONU,
            'upload' => $this->UPLOAD,
            'download' => $this->DOWNLOAD,
            'last_program' => $this->LAST_PROGRAM,
            'status_voice' => $this->STATUS_VOICE,
            'clid' => $this->CLID,
            'last_start' => $this->LAST_START,
            'tindak_lanjut' => $this->TINDAK_LANJUT,
            'isi_comment' => $this->ISI_COMMENT,
            'user_id_tl' => $this->USER_ID_TL,
            'tgl_comment' => $this->TGL_COMMENT,
            'tanggal_manja' => $this->TANGGAL_MANJA,
            'kelompok_kendala' => $this->KELOMPOK_KENDALA,
            'kelompok_status' => $this->KELOMPOK_STATUS,
            'hero' => $this->HERO,
            'addon' => $this->ADDON,
            'tgl_ps' => $this->TGL_PS,
            'status_message' => $this->STATUS_MESSAGE,
            'package_name' => $this->PACKAGE_NAME,
            'group_paket' => $this->GROUP_PAKET,
            'reason_cancel' => $this->REASON_CANCEL,
            'keterangan_cancel' => $this->KETERANGAN_CANCEL,
            'tgl_manja' => $this->TGL_MANJA,
            'detail_manja' => $this->DETAIL_MANJA,
            'bulan_ps' => $this->Bulan_PS,
            'kode_sales' => $this->Kode_sales,
            'nama_sa' => $this->Nama_SA,
            'mitra' => $this->Mitra,
            'ekosistem' => $this->Ekosistem,
        ];
    }
}
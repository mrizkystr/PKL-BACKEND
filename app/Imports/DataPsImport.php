<?php

namespace App\Imports;

use App\Models\DataPsAgustusKujangSql;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class DataPsImport implements ToModel, WithHeadingRow
{
    private $rows = 0;

    public function model(array $row)
    {
        // Log row being processed
        Log::info("Processing row $this->rows:", $row);

        try {
            $model = new DataPsAgustusKujangSql([
                'id' => $row['id'] ?? null,
                'ORDER_ID' => $row['order_id'] ?? null,
                'REGIONAL' => $row['regional'] ?? null,
                'WITEL' => $row['witel'] ?? null,
                'DATEL' => $row['datel'] ?? null,
                'STO' => $row['sto'] ?? null,
                'UNIT' => $row['unit'] ?? null,
                'JENISPSB' => $row['jenispsb'] ?? null,
                'TYPE_TRANS' => $row['type_trans'] ?? null,
                'TYPE_LAYANAN' => $row['type_layanan'] ?? null,
                'STATUS_RESUME' => $row['status_resume'] ?? null,
                'PROVIDER' => $row['provider'] ?? null,
                'ORDER_DATE' => $this->transformDate($row['order_date'] ?? null),
                'LAST_UPDATED_DATE' => $this->transformDate($row['last_updated_date'] ?? null),
                'NCLI' => $row['ncli'] ?? null,
                'POTS' => $row['pots'] ?? null,
                'SPEEDY' => $row['speedy'] ?? null,
                'CUSTOMER_NAME' => $row['customer_name'] ?? null,
                'LOC_ID' => $row['loc_id'] ?? null,
                'WONUM' => $row['wonum'] ?? null,
                'FLAG_DEPOSIT' => $row['flag_deposit'] ?? null,
                'CONTACT_HP' => $row['contact_hp'] ?? null,
                'INS_ADDRESS' => $row['ins_address'] ?? null,
                'GPS_LONGITUDE' => isset($row['gps_longitude']) ? (float)$row['gps_longitude'] : null,
                'GPS_LATITUDE' => isset($row['gps_latitude']) ? (float)$row['gps_latitude'] : null,
                'KCONTACT' => $row['kcontact'] ?? null,
                'CHANNEL' => $row['channel'] ?? null,
                'STATUS_INET' => $row['status_inet'] ?? null,
                'STATUS_ONU' => $row['status_onu'] ?? null,
                'UPLOAD' => $row['upload'] ?? null,
                'DOWNLOAD' => $row['download'] ?? null,
                'LAST_PROGRAM' => $row['last_program'] ?? null,
                'STATUS_VOICE' => $row['status_voice'] ?? null,
                'CLID' => $row['clid'] ?? null,
                'LAST_START' => $this->transformDate($row['last_start'] ?? null),
                'TINDAK_LANJUT' => $row['tindak_lanjut'] ?? null,
                'ISI_COMMENT' => $row['isi_comment'] ?? null,
                'USER_ID_TL' => $row['user_id_tl'] ?? null,
                'TGL_COMMENT' => $this->transformDate($row['tgl_comment'] ?? null),
                'TANGGAL_MANJA' => $this->transformDate($row['tanggal_manja'] ?? null),
                'KELOMPOK_KENDALA' => $row['kelompok_kendala'] ?? null,
                'KELOMPOK_STATUS' => $row['kelompok_status'] ?? null,
                'HERO' => $row['hero'] ?? null,
                'ADDON' => $row['addon'] ?? null,
                'TGL_PS' => $this->transformDate($row['tgl_ps'] ?? null),
                'STATUS_MESSAGE' => $row['status_message'] ?? null,
                'PACKAGE_NAME' => $row['package_name'] ?? null,
                'GROUP_PAKET' => $row['group_paket'] ?? null,
                'HARGA_PAKET' => $row['harga_paket'] ?? null,
                'KETERANGAN_CANCEL' => $row['keterangan_cancel'] ?? null,
                'TGL_MANJA' => $this->transformDate($row['tgl_manja'] ?? null),
                'DETAIL_MANJA' => $row['detail_manja'] ?? null,
                'Bulan_PS' => $row['bulan_ps'] ?? null, // Field baru
                'Kode_sales' => $row['kode_sales'] ?? null, // Field baru
                'Nama_SA' => $row['nama_sa'] ?? null, // Field baru
                'Mitra' => $row['mitra'] ?? null, // Field baru
                'Ekosistem' => $row['ekosistem'] ?? null, // Field baru
            ]);

            Log::info("Model created for row $this->rows");
            return $model;
        } catch (\Exception $e) {
            Log::error("Error processing row $this->rows: " . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function transformDate($value)
    {
        if (!empty($value)) {
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            return Carbon::parse($value)->format('Y-m-d');
        }
        return null;
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }
}

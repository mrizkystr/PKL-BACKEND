<?php

namespace App\Imports;

use App\Models\SalesCodes;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SalesCodesImport implements ToModel, WithHeadingRow
{
    public $rows = 0; // Ubah dari private menjadi public
    private $importedRows = 0;

    public function model(array $row)
    {
        $this->rows++;
        Log::info("Processing row $this->rows:", $row);

        if ($this->isEmptyRow($row)) {
            Log::info("Skipping empty row $this->rows");
            return null;
        }

        try {
            $this->importedRows++;

            $model = new SalesCodes([
                'id' => $row['id'] ?? null,
                'mitra_nama'    => $row['mitra_nama'] ?? null,
                'nama'          => $row['nama'] ?? null,
                'sto'           => $row['sto'] ?? null,
                'id_mitra'      => $row['id_mitra'] ?? null,
                'nama_mitra'    => $row['nama_mitra'] ?? null,
                'role'          => $row['role'] ?? null,
                'kode_agen'     => $row['kode_agen'] ?? null,
                'kode_baru'     => $row['kode_baru'] ?? null,
                'no_telp_valid' => $row['no_telp_valid'] ?? null,
                'nama_sa_2'     => $row['nama_sa_2'] ?? null,
                'status_wpi'    => $row['status_wpi'] ?? null,
            ]);

            Log::info("Model created for row $this->rows");
            return $model;
        } catch (\Exception $e) {
            Log::error("Error processing row $this->rows: " . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    private function isEmptyRow(array $row): bool
    {
        return empty($row['id']) &&
               empty($row['mitra_nama']) &&
               empty($row['nama']) &&
               empty($row['sto']) &&
               empty($row['id_mitra']) &&
               empty($row['nama_mitra']) &&
               empty($row['role']) &&
               empty($row['kode_agen']) &&
               empty($row['kode_baru']) &&
               empty($row['no_telp_valid']) &&
               empty($row['nama_sa_2']) &&
               empty($row['status_wpi']);
    }

    public function getRowCount(): int
    {
        return $this->importedRows;
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDataPsAgustusKujangSqlTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_ps_agustus_kujang_sql', function (Blueprint $table) {
            $table->id();  // Primary key;
            $table->string('ORDER_ID')->unique();
            $table->string('REGIONAL')->nullable();
            $table->string('REGIONAL_OLD')->nullable(); // Kolom baru
            $table->string('WITEL')->nullable();
            $table->string('WITEL_OLD')->nullable(); // Kolom baru
            $table->string('DATEL')->nullable();
            $table->string('STO')->nullable();
            $table->string('UNIT')->nullable();
            $table->string('JENISPSB')->nullable();
            $table->string('TYPE_TRANS')->nullable();
            $table->string('TYPE_LAYANAN')->nullable();
            $table->string('STATUS_RESUME')->nullable();
            $table->string('PROVIDER')->nullable();
            $table->date('ORDER_DATE')->nullable();
            $table->date('LAST_UPDATED_DATE')->nullable();
            $table->string('NCLI')->nullable();
            $table->string('POTS')->nullable();
            $table->string('SPEEDY')->nullable();
            $table->string('CUSTOMER_NAME')->nullable();
            $table->string('LOC_ID')->nullable();
            $table->string('WONUM')->nullable();
            $table->string('FLAG_DEPOSIT')->nullable();
            $table->string('CONTACT_HP')->nullable();
            $table->text('INS_ADDRESS')->nullable();
            $table->string('GPS_LONGITUDE')->nullable();
            $table->string('GPS_LATITUDE')->nullable();
            $table->string('KCONTACT')->nullable();
            $table->string('CHANNEL')->nullable();
            $table->string('STATUS_INET')->nullable();
            $table->string('STATUS_ONU')->nullable();
            $table->string('UPLOAD')->nullable();
            $table->string('DOWNLOAD')->nullable();
            $table->string('LAST_PROGRAM')->nullable();
            $table->string('STATUS_VOICE')->nullable();
            $table->string('CLID')->nullable();
            $table->date('LAST_START')->nullable();
            $table->text('TINDAK_LANJUT')->nullable();
            $table->text('ISI_COMMENT')->nullable();
            $table->string('USER_ID_TL')->nullable();
            $table->date('TGL_COMMENT')->nullable();
            $table->date('TANGGAL_MANJA')->nullable();
            $table->string('KELOMPOK_KENDALA')->nullable();
            $table->string('KELOMPOK_STATUS')->nullable();
            $table->string('HERO')->nullable();
            $table->string('ADDON')->nullable();
            $table->date('TGL_PS')->nullable();
            $table->string('STATUS_MESSAGE')->nullable();
            $table->string('PACKAGE_NAME')->nullable();
            $table->string('GROUP_PAKET')->nullable();
            $table->string('REASON_CANCEL')->nullable();
            $table->text('KETERANGAN_CANCEL')->nullable();
            $table->date('TGL_MANJA')->nullable();
            $table->text('DETAIL_MANJA')->nullable();
            $table->string('Bulan_PS')->nullable(); // Kolom ini sudah sesuai
            $table->string('Kode_sales')->nullable(); // Menggunakan underscore
            $table->string('Nama_SA')->nullable(); // Menggunakan underscore
            $table->string('Mitra')->nullable();
            $table->string('Ekosistem')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_ps_agustus_kujang_sql');
    }
}

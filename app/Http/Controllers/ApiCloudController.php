<?php

namespace App\Http\Controllers;

use App\Helpers\EncryptionHelper;
use App\Models\HasilUji;
use App\Models\Kendaraan;
use App\Models\KendaraanDetail;
use App\Models\Upload;
use DateTime;
use Illuminate\Http\Request;

class ApiCloudController extends Controller
{
    protected $helper,$lines;

    public function __construct()
    {
        $this->helper = new EncryptionHelper();
        $this->lines = file(base_path('api.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    public function get_last(Request $request) {
        $token = $request->token;
        $decrypt = $this->helper->decryptToken($token);
        $decrypt_status = $decrypt['success'];
        $last_upload = 0;

        if($decrypt_status) {
            $data = Upload::first();
            if($data) {
                $last_upload = $data->last_sync;
            }
            return response()->json([
                'success' => true,
                'data' => $last_upload,
            ]);
        }
    }

    public function post_data(Request $request) {
        $token = $request->query('token');
        $decrypt = $this->helper->decryptToken($token);

        if (!$decrypt['success']) {
            return response()->json(['success' => false, 'message' => 'Invalid token']);
        }

        $allData = $request->all();

        if (empty($allData['data']) || !is_array($allData['data'])) {
            return response()->json(['success' => false, 'message' => 'No data provided']);
        }

        $uploadedSuccess = '';
        $lastUploadedId = '';

        foreach ($allData['data'] as $nouji => $item) {
            // If kendaraan is JSON string, decode it; else assume array
            $kendaraanData = is_string($item['kendaraan']) 
                ? json_decode($item['kendaraan'], true) 
                : $item['kendaraan'];

            if (!$kendaraanData) {
                continue;
            }

            // Defensive checks for required keys
            if (!isset($kendaraanData['nouji'])) {
                continue;
            }

            $existing = Kendaraan::where('no_uji', $kendaraanData['nouji'])->first();

            if ($existing) {
                $generated_id = $existing->generated_id;
                $detail = KendaraanDetail::where('id_kendaraan', $generated_id)->latest()->first();

                if ($detail && $detail->jenis != ($kendaraanData['jenis'] ?? '')) {
                    $this->change_jenis($kendaraanData, $generated_id);
                }
            } else {
                $milliseconds = round(microtime(true) * 1000);
                $generated_id = md5(($kendaraanData['id'] ?? '') . $milliseconds);

                Kendaraan::create([
                    'generated_id' => $generated_id,
                    'no_kendaraan' => $kendaraanData['noregistrasikendaraan'] ?? null,
                    'no_uji' => $kendaraanData['nouji'] ?? null,
                    'nama' => $kendaraanData['nama'] ?? null,
                    'nosertifikatreg' => $kendaraanData['nosertifikatreg'] ?? null,
                    'tglsertifikatreg' => $kendaraanData['tglsertifikatreg'] ?? null,
                    'norangka' => $kendaraanData['norangka'] ?? null,
                    'nomesin' => $kendaraanData['nomesin'] ?? null,
                ]);

                KendaraanDetail::create([
                    'id_kendaraan' => $generated_id,
                    'merek' => $kendaraanData['merek'] ?? null,
                    'tipe' => $kendaraanData['tipe'] ?? null,
                    'jenis' => $kendaraanData['jenis'] ?? null,
                    'thpembuatan' => $kendaraanData['thpembuatan'] ?? null,
                    'bahanbakar' => $kendaraanData['bahanbakar'] ?? null,
                    'isisilinder' => $kendaraanData['isisilinder'] ?? null,
                    'dayamotorpenggerak' => $kendaraanData['dayamotorpenggerak'] ?? null,
                    'jbb' => $kendaraanData['jbb'] ?? null,
                    'jbkb' => $kendaraanData['jbkb'] ?? null,
                    'jbi' => $kendaraanData['jbi'] ?? null,
                    'jbki' => $kendaraanData['jbki'] ?? null,
                    'mst' => $kendaraanData['mst'] ?? null,
                    'beratkosong' => $kendaraanData['beratkosong'] ?? null,
                    'konfigurasisumburoda' => $kendaraanData['konfigurasisumburoda'] ?? null,
                    'ukuranban' => $kendaraanData['ukuranban'] ?? null,
                    'panjangkendaraan' => $kendaraanData['panjangkendaraan'] ?? null,
                    'lebarkendaraan' => $kendaraanData['lebarkendaraan'] ?? null,
                    'tinggikendaraan' => $kendaraanData['tinggikendaraan'] ?? null,
                    'panjangbakatautangki' => $kendaraanData['panjangbakatautangki'] ?? null,
                    'lebarbakatautangki' => $kendaraanData['lebarbakatautangki'] ?? null,
                    'tinggibakatautangki' => $kendaraanData['tinggibakatautangki'] ?? null,
                    'julurdepan' => $kendaraanData['julurdepan'] ?? null,
                    'julurbelakang' => $kendaraanData['julurbelakang'] ?? null,
                    'jaraksumbu1_2' => $kendaraanData['jaraksumbu1_2'] ?? null,
                    'jaraksumbu2_3' => $kendaraanData['jaraksumbu2_3'] ?? null,
                    'jaraksumbu3_4' => $kendaraanData['jaraksumbu3_4'] ?? null,
                    'dayaangkutorang' => $kendaraanData['dayaangkutorang'] ?? null,
                    'dayaangkutbarang' => $kendaraanData['dayaangkutbarang'] ?? null,
                    'kelasjalanterendah' => $kendaraanData['kelasjalanterendah'] ?? null,
                ]);
            }

            // Parse dates safely
            $masaberlakuuji = DateTime::createFromFormat('dmY', $kendaraanData['masaberlakuuji'] ?? '');
            $tgl_uji = DateTime::createFromFormat('dmY', $kendaraanData['tgluji'] ?? '');

            HasilUji::create([
                'id_kendaraan' => $generated_id,
                'fotodepan' => $kendaraanData['fotodepan'] ?? null,
                'fotobelakang' => $kendaraanData['fotobelakang'] ?? null,
                'fotokanan' => $kendaraanData['fotokanan'] ?? null,
                'fotokiri' => $kendaraanData['fotokiri'] ?? null,
                'emisiasap' => $kendaraanData['alatuji_emisiasapbahanbakarsolar'] ?? null,
                'emisico' => $kendaraanData['alatuji_emisicobahanbakarbensin'] ?? null,
                'emisihc' => $kendaraanData['alatuji_emisihcbahanbakarbensin'] ?? null,
                'masaberlakuuji' => $masaberlakuuji ? $masaberlakuuji->format('Y-m-d') : null,
                'tgl_uji' => $tgl_uji ? $tgl_uji->format('Y-m-d') : null,
            ]);

            $uploadedSuccess .= ($kendaraanData['id'] ?? '') . ',';
            $lastUploadedId = $kendaraanData['id'] ?? '';
        }

        // Update Upload model
        $lastUpdate = Upload::first();
        if ($lastUpdate) {
            $lastUpdate->update([
                'last_sync' => $lastUploadedId,
                'uploaded_id' => $lastUpdate->uploaded_id . $uploadedSuccess,
            ]);
        } else {
            Upload::create([
                'last_sync' => $lastUploadedId,
                'uploaded_id' => $uploadedSuccess,
            ]);
        }

        return response()->json(['success' => true, 'uploaded_ids' => $uploadedSuccess]);
    }



    function change_jenis($data,$generated_id) {
        $uploded_success = null;

        $insert = KendaraanDetail::create([
            'id_kendaraan'      => $generated_id,
            'merek'             => $data['merek'],
            'tipe'              => $data['tipe'],
            'jenis'             => $data['jenis'],
            'thpembuatan'       => $data['thpembuatan'],
            'bahanbakar'        => $data['bahanbakar'],
            'isisilinder'       => $data['isisilinder'],
            'dayamotorpenggerak'=> $data['dayamotorpenggerak'],
            'jbb'               => $data['jbb'],
            'jbkb'              => $data['jbkb'],
            'jbi'               => $data['jbi'],
            'jbki'              => $data['jbki'],
            'mst'               => $data['mst'],
            'beratkosong'       => $data['beratkosong'],
            'konfigurasisumburoda'  => $data['konfigurasisumburoda'],
            'ukuranban'         => $data['ukuranban'],
            'panjangkendaraan'  => $data['panjangkendaraan'],
            'lebarkendaraan'    => $data['lebarkendaraan'],
            'tinggikendaraan'   => $data['tinggikendaraan'],
            'panjangbakatautangki'  => $data['panjangbakatautangki'],
            'lebarbakatautangki'=> $data['lebarbakatautangki'],
            'tinggibakatautangki'   => $data['tinggibakatautangki'],
            'julurdepan'        => $data['julurdepan'],
            'julurbelakang'     => $data['julurbelakang'],
            'jaraksumbu1_2'     => $data['jaraksumbu1_2'],
            'jaraksumbu2_3'     => $data['jaraksumbu2_3'],
            'jaraksumbu3_4'     => $data['jaraksumbu3_4'],
            'dayaangkutorang'   => $data['dayaangkutorang'],
            'dayaangkutbarang'  => $data['dayaangkutbarang'],
            'kelasjalanterendah'=> $data['kelasjalanterendah']
        ]);

        $masaberlakuuji = DateTime::createFromFormat('dmY', $data['masaberlakuuji']);
        $tgl_uji = DateTime::createFromFormat('dmY', $data['tgluji']);
        HasilUji::create([
            'id_kendaraan'          => $generated_id,
            'fotodepan'             => $data['fotodepansmall'],
            'fotobelakang'          => $data['fotobelakangsmall'],
            'fotokanan'             => $data['fotokanansmall'],
            'fotokiri'              => $data['fotokirismall'],
            'emisiasap'             => $data['alatuji_emisiasapbahanbakarsolar'],
            'emisico'               => $data['alatuji_emisicobahanbakarbensin'],
            'emisihc'               => $data['alatuji_emisihcbahanbakarbensin'],
            'totalgayapengereman'   => $data['alatuji_remutamatotalgayapengereman'],
            'selisihgayapengereman1'=> $data['alatuji_remutamaselisihgayapengeremanrodakirikanan1'],
            'selisihgayapengereman2'=> $data['alatuji_remutamaselisihgayapengeremanrodakirikanan2'],
            'selisihgayapengereman3'=> $data['alatuji_remutamaselisihgayapengeremanrodakirikanan3'],
            'selisihgayapengereman4'=> $data['alatuji_remutamaselisihgayapengeremanrodakirikanan4'],
            'remparkirtangan'       => $data['alatuji_remparkirtangan'],
            'remparkirkaki'         => $data['alatuji_remparkirkaki'],
            'kincuprodadepan'       => $data['alatuji_kincuprodadepan'],
            'tingkatkebisingan'     => $data['alatuji_tingkatkebisingan'],
            'kekuatanpancarlampukanan'  => $data['alatuji_lampuutamakekuatanpancarlampukanan'],
            'kekuatanpancarlampukiri'   => $data['alatuji_lampuutamakekuatanpancarlampukiri'],
            'penyimpanganlampukanan'=> $data['alatuji_lampuutamapenyimpanganlampukanan'],
            'penyimpanganlampukiri' => $data['alatuji_lampuutamapenyimpanganlampukiri'],
            'penunjukkecepatan'     => $data['alatuji_penunjukkecepatan'],
            'kedalamanalurban'      => $data['alatuji_kedalamanalurban'],
            'masaberlakuuji'        => $masaberlakuuji->format('Y-m-d'),
            'tgl_uji'               => $tgl_uji->format('Y-m-d'),
        ]);
        $last_update = Upload::lastest()->first();

        $uploded_success .= $data['id'].',';
        $uploaded = $data['id'];

        if($last_update) {
            $uplod_done = $last_update->uploaded_id;
            $update_uploaded_id = $uplod_done.$uploded_success;

            $last_update->update([
                'last_sync'     => $uploaded,
                'uploaded_id'   => $update_uploaded_id,
            ]);
        } else {
            Upload::create([
                'last_sync'     => $uploaded,
                'uploaded_id'   => $uploded_success,
            ]);
        }

        if($insert) {
            return true;
        } else {
            return false;
        }
    }
}

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
        $token = $request->query('token'); // from URL
        $decrypt = $this->helper->decryptToken($token);

        if (!$decrypt['success']) {
            return response()->json(['success' => false, 'message' => 'Invalid token']);
        }

        $allData = $request->all(); // Laravel parses multipart fields automatically

        $uploadedSuccess = '';
        $lastUploadedId = '';
        $uploadPath = public_path('uploads/kendaraan/'.Carbon::now()->format('Ym'));

        // Ensure the directory exists
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        foreach ($allData['data'] as $nouji => $item) {
            // Parse the kendaraan JSON
            $kendaraanData = json_decode($item['kendaraan'], true);
            if (!$kendaraanData) continue;

            // Save or update kendaraan
            $existing = Kendaraan::where('no_uji', $kendaraanData['nouji'])->first();

            if ($existing) {
                $generated_id = $existing->generated_id;
                $detail = KendaraanDetail::where('id_kendaraan', $generated_id)->latest()->first();

                if ($detail && $detail->jenis != $kendaraanData['jenis']) {
                    $this->change_jenis($kendaraanData, $generated_id);
                }
            } else {
                $milliseconds = round(microtime(true) * 1000);
                $generated_id = md5($kendaraanData['id'].$milliseconds);

                Kendaraan::create([
                    'generated_id' => $generated_id,
                    'no_kendaraan' => $kendaraanData['noregistrasikendaraan'],
                    'no_uji' => $kendaraanData['nouji'],
                    'nama' => $kendaraanData['nama'],
                    'nosertifikatreg' => $kendaraanData['nosertifikatreg'],
                    'tglsertifikatreg' => $kendaraanData['tglsertifikatreg'],
                    'norangka' => $kendaraanData['norangka'],
                    'nomesin' => $kendaraanData['nomesin'],
                ]);

                KendaraanDetail::create([
                    'id_kendaraan' => $generated_id,
                    'merek' => $kendaraanData['merek'],
                    'tipe' => $kendaraanData['tipe'],
                    'jenis' => $kendaraanData['jenis'],
                    'thpembuatan' => $kendaraanData['thpembuatan'],
                    'bahanbakar' => $kendaraanData['bahanbakar'],
                    'isisilinder' => $kendaraanData['isisilinder'],
                    'dayamotorpenggerak' => $kendaraanData['dayamotorpenggerak'],
                    'jbb' => $kendaraanData['jbb'],
                    'jbkb' => $kendaraanData['jbkb'],
                    'jbi' => $kendaraanData['jbi'],
                    'jbki' => $kendaraanData['jbki'],
                    'mst' => $kendaraanData['mst'],
                    'beratkosong' => $kendaraanData['beratkosong'],
                    'konfigurasisumburoda' => $kendaraanData['konfigurasisumburoda'],
                    'ukuranban' => $kendaraanData['ukuranban'],
                    'panjangkendaraan' => $kendaraanData['panjangkendaraan'],
                    'lebarkendaraan' => $kendaraanData['lebarkendaraan'],
                    'tinggikendaraan' => $kendaraanData['tinggikendaraan'],
                    'panjangbakatautangki' => $kendaraanData['panjangbakatautangki'],
                    'lebarbakatautangki' => $kendaraanData['lebarbakatautangki'],
                    'tinggibakatautangki' => $kendaraanData['tinggibakatautangki'],
                    'julurdepan' => $kendaraanData['julurdepan'],
                    'julurbelakang' => $kendaraanData['julurbelakang'],
                    'jaraksumbu1_2' => $kendaraanData['jaraksumbu1_2'],
                    'jaraksumbu2_3' => $kendaraanData['jaraksumbu2_3'],
                    'jaraksumbu3_4' => $kendaraanData['jaraksumbu3_4'],
                    'dayaangkutorang' => $kendaraanData['dayaangkutorang'],
                    'dayaangkutbarang' => $kendaraanData['dayaangkutbarang'],
                    'kelasjalanterendah' => $kendaraanData['kelasjalanterendah'],
                ]);
            }

            // Save HasilUji
            $masaberlakuuji = DateTime::createFromFormat('dmY', $kendaraanData['masaberlakuuji']);
            $tgl_uji = DateTime::createFromFormat('dmY', $kendaraanData['tgluji']);

            HasilUji::create([
                'id_kendaraan' => $generated_id,
                'fotodepan' => $kendaraanData['fotodepan'],
                'fotobelakang' => $kendaraanData['fotobelakang'],
                'fotokanan' => $kendaraanData['fotokanan'],
                'fotokiri' => $kendaraanData['fotokiri'],
                // Add additional fields here from $kendaraanData
                'emisiasap' => $kendaraanData['alatuji_emisiasapbahanbakarsolar'],
                'emisico' => $kendaraanData['alatuji_emisicobahanbakarbensin'],
                'emisihc' => $kendaraanData['alatuji_emisihcbahanbakarbensin'],
                // Continue for the rest...
                'masaberlakuuji' => $masaberlakuuji->format('Y-m-d'),
                'tgl_uji' => $tgl_uji->format('Y-m-d'),
            ]);

            $uploadedSuccess .= $kendaraanData['id'] . ',';
            $lastUploadedId = $kendaraanData['id'];
        }

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

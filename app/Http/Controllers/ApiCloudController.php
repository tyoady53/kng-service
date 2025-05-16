<?php

namespace App\Http\Controllers;

use App\Helpers\EncryptionHelper;
use App\Models\HasilUji;
use App\Models\Kendaraan;
use App\Models\KendaraanDetail;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiCloudController extends Controller
{
    protected $helper,$lines;

    public function __construct()
    {
        $this->helper = new EncryptionHelper();
        $this->lines = file(base_path('api.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    public function post_data(Request $request) {
        $target = $this->lines[1];
        $token = $request->token;
        $decrypt = $this->helper->decryptToken($token);

        $datas = $request->data;

        foreach($datas as $data) {
            $get = Kendaraan::where('no_uji', $data['nouji'])->first();
            if($get) {
                $generated_id = $get->generated_id;
                $detail = KendaraanDetail::where('id_kendaraan', $generated_id)->latest()->first();
                if($detail && $detail->jenis != $data['jenis']) {
                    $this->change_jenis($data);
                }
            } else {
                $milliseconds = round(microtime(true) * 1000);
                $generated_id = md5($data['id'].$milliseconds);

                return $generated_id;

                Kendaraan::create([
                    'generated_id'      => $generated_id,
                    'no_kendaraan'      => $data['noregistrasikendaraan'],
                    'no_uji'            => $data['nouji'],
                    'nama'              => $data['nama'],
                    'nosertifikatreg'   => $data['nosertifikatreg'],
                    'tglsertifikatreg'  => $data['tglsertifikatreg'],
                    'norangka'          => $data['norangka'],
                    'nomesin'           => $data['nomesin'],
                ]);

                KendaraanDetail::create([
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
            }

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
                'masaberlakuuji'        => $data['masaberlakuuji']
            ]);
            // if($data->nouji)
        }

        // return $generated_id;
        return response()->json([
            'success' => true,
            'data' => $generated_id,
        ]);
    }

    function change_jenis($data) {

    }
}

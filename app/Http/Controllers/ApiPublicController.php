<?php

namespace App\Http\Controllers;

use App\Models\HasilUji;
use App\Models\Kendaraan;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ApiPublicController extends Controller
{
    public function store_device(Request $request) {
        $device_id = $request->device_id;
        $kendaraan = $request->kendaraan;

        $user_exist = User::where('device_id', $device_id)->first();
        if(!$user_exist) {
            $insert = User::create([
                'device_id' => $device_id
            ]);
            $user_id = $insert->id;
        } else {
            $user_id = $user_exist->id;
        }

        $data_kendaraan = Kendaraan::where(function ($query) use ($kendaraan) {
            $query->where('no_uji', $kendaraan)
                  ->orWhere('no_kendaraan', $kendaraan);
        })->latest()->first();

        $id_kendaraan = $data_kendaraan->generated_id;
        $is_exist = UserDetail::where('user_id', $user_id)->where('id_kendaraan',$id_kendaraan)->first();

        if($is_exist) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan sudah di daftarkan',
            ]);
        }

        $insert = UserDetail::create([
            'user_id'       => $user_id,
            'id_kendaraan'  => $id_kendaraan
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil di daftarkan',
        ]);
    }

    public function get_data(Request $request)
    {
        $data = HasilUji::from('datapengujian')
            ->where('datapengujian.statuslulusuji', true)
            ->where(function ($q) use ($request) {
                $q->where('datapengujian.nouji', 'ILIKE', $request->search)
                ->orWhere('datapengujian.noregistrasikendaraan', 'ILIKE', $request->search);
            })
            ->joinSub(function ($q) {
                $q->from('fotomentah')
                ->select(
                    'nouji',
                    'idx',
                    'fotodepanmentah',
                    'fotokananmentah',
                    'fotokirimentah',
                    'fotobelakangmentah'
                )
                ->distinct('nouji')
                ->orderBy('nouji')        // 🔑 MUST BE FIRST
                ->orderByDesc('idx'); // 🔑 REQUIRED
            }, 'fm', function ($join) {
                $join->on('fm.nouji', '=', 'datapengujian.nouji');
            })
            ->orderByDesc('datapengujian.idx')
            ->first();

        if (!$data) {
            return response()->json(['success' => false]);
        }

        /**
         * 🔑 Convert model → array (STRIPS resources)
         */
        $response = json_decode(json_encode($data), true);

        /**
         * Generate images safely
         */
        if (!empty($data->fotodepanmentah)) {
            $response['fotodepanmentah'] =
                $this->generate_image($data->fotodepanmentah);
        } else {
            $response['fotodepanmentah'] =
                $this->generate_image($data->fotodepansmall);
        }

        if (!empty($data->fotokananmentah)) {
            $response['fotokananmentah'] =
                $this->generate_image($data->fotokananmentah);
        }

        if (!empty($data->fotokirimentah)) {
            $response['fotokirimentah'] =
                $this->generate_image($data->fotokirimentah);
        }

        if (!empty($data->fotobelakangmentah)) {
            $response['fotobelakangmentah'] =
                $this->generate_image($data->fotobelakangmentah);
        }

        $meta = HasilUji::from('datapengujian')
            ->select([
                "idx", "statuspenerbitan", "nouji", "nama", "alamat", "noidentitaspemilik", 
                "nosertifikatreg", "tglsertifikatreg", "noregistrasikendaraan", "norangka", 
                "nomesin", "merek", "tipe", "jenis", "thpembuatan", "bahanbakar", "isisilinder", 
                "dayamotorpenggerak", "jbb", "jbkb", "jbi", "jbki", "mst", "beratkosong", 
                "konfigurasisumburoda", "ukuranban", "panjangkendaraan", "lebarkendaraan", 
                "tinggikendaraan", "panjangbakatautangki", "lebarbakatautangki", "tinggibakatautangki", 
                "julurdepan", "julurbelakang", "jaraksumbu1_2", "jaraksumbu2_3", "jaraksumbu3_4", 
                "dayaangkutorang", "dayaangkutbarang", "kelasjalanterendah", "huv_nomordankondisirangka", 
                "huv_nomordantipemotorpenggerak", "huv_kondisitangkicorongdanpipabahanbakar", 
                "huv_kondisiconverterkit", "huv_kondisidanposisipipapembuangan", "huv_ukurandankondisiban", 
                "huv_kondisisistemsuspensi", "huv_kondisisistemremutama", "huv_kondisipenutuplampudanalatpantulcahaya", 
                "huv_kondisipanelinstrumentdashboard", "huv_kondisikacaspion", "huv_kondisispakbor", 
                "huv_bentukbumper", "huv_keberadaandankondisiperlengkapan", "huv_rancanganteknis", 
                "huv_keberadaandankondisifasilitastanggapdaruratuntukmobilbus", 
                "huv_kondisibadankacaengseltempatdudukmbarangbakmuatantertutup", "hum_kondisipenerusdaya", 
                "hum_sudutbebaskemudi", "hum_kondisiremparkir", "hum_fungsilampudanalatpantulcahaya", 
                "hum_fungsipenghapuskaca", "hum_tingkatkegelapankaca", "hum_fungsiklakson", 
                "hum_kondisidanfungsisabukkeselamatan", "hum_ukurankendaraan", 
                "hum_ukurantempatdudukdanbagiandalamkendaraanuntukmobilbus", "alatuji_emisiasapbahanbakarsolar", 
                "alatuji_emisicobahanbakarbensin", "alatuji_emisihcbahanbakarbensin", 
                "alatuji_remutamatotalgayapengereman", "alatuji_remutamaselisihgayapengeremanrodakirikanan1", 
                "alatuji_remutamaselisihgayapengeremanrodakirikanan2", 
                "alatuji_remutamaselisihgayapengeremanrodakirikanan3", 
                "alatuji_remutamaselisihgayapengeremanrodakirikanan4", "alatuji_remparkirtangan", 
                "alatuji_remparkirkaki", "alatuji_kincuprodadepan", "alatuji_tingkatkebisingan", 
                "alatuji_lampuutamakekuatanpancarlampukanan", "alatuji_lampuutamakekuatanpancarlampukiri", 
                "alatuji_lampuutamapenyimpanganlampukanan", "alatuji_lampuutamapenyimpanganlampukiri", 
                "alatuji_penunjukkecepatan", "alatuji_kedalamanalurban", 
                "qrcodeurl", "qrnoujipm133", "masaberlakuuji", "tgluji", "statuslulusuji"
                // add ALL columns EXCEPT bytea/blob
            ])
            ->where('statuslulusuji', true)
            ->where(function ($q) use ($request) {
                $q->where('nouji', 'ILIKE', $request->search)
                ->orWhere('noregistrasikendaraan', 'ILIKE', $request->search);
            })
            ->orderByDesc('idx')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'meta' => $meta,
                'foto' => [
                    'depan' => $response['fotodepanmentah'] ?? null,
                    'kanan' => $response['fotokananmentah'] ?? null,
                    'kiri'  => $response['fotokirimentah'] ?? null,
                    'belakang' => $response['fotobelakangmentah'] ?? null,
                ],
            ],
        ]);
    }

    function generate_image($image) {
        /// Case 1: PDO stream → already binary
        if (is_resource($image)) {
            $binary = stream_get_contents($image);

        // Case 2: Escaped bytea string
        } else {
            $binary = pg_unescape_bytea($image);
        }

        // Safety: ensure we have real data
        if (strlen($binary) < 100) {
            throw new \Exception('Image data is truncated or invalid');
        }

        return 'data:image/jpeg;base64,' . base64_encode($binary);
    }
}

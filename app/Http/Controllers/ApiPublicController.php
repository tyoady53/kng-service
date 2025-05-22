<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;

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

    public function get_data(Request $request) {
        $data = Kendaraan::with('detail', 'hasil_terakhir')->where(function ($query) use ($request) {
            $query->where('no_uji', $request->search)
                  ->orWhere('no_kendaraan', $request->search);
        })->first();

        dd($data);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use Illuminate\Http\Request;

class ApiPublicController extends Controller
{
    public function store_device(Request $request) {

    }

    public function get_data(Request $request) {
        $data = Kendaraan::with('detail', 'hasil_terakhir')->where(function ($query) use ($request) {
            $query->where('no_uji', $request->search)
                  ->orWhere('no_kendaraan', $request->search);
        })->first();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}

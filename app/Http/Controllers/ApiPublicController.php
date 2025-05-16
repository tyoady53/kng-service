<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use Illuminate\Http\Request;

class ApiPublicController extends Controller
{
    public function get_data(Request $request) {
        $raw_query = `no_uji = '$request->search' OR no_kendaraan = '$request->search'`;

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

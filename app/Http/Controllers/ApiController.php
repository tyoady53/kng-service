<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    protected $lines;

    public function __construct()
    {
        $this->lines = file(base_path('api.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // $content = file_get_contents(base_path('api.txt'));
        // $lines = file(base_path('api.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $path = $this->lines[0];

        $hdd_id = $this->getHDD_id();

        return response()->json([
            'success'   => true,
            'message'   => 'init Info',
            'path'      => "{$this->lines[0]}",
            'hdd_id'    => trim($hdd_id),
            'encrypted' => $this->encrypt(trim($hdd_id))
        ]);
    }

    public function get_data() {
        $dataList = DB::connection('pgsql_eblue')->select('select * from datapengujian');
        dd($dataList);
    }

    public function post_data() {
        $dataList = DB::connection('pgsql_eblue')->select('select * from datapengujian');

        foreach ($dataList as $data) {
            $maxRetries = 5;
            $attempt = 0;

            do {
                $attempt++;
                $response = Http::post("{$this->lines[0]}/api/post/{$this->encrypt($this->lines[1])}", $data);

                if ($response->successful()) {
                    // Success, break retry loop
                    break;
                }

                // Optional: wait before retrying
                sleep(2);

            } while ($attempt < $maxRetries);

            // If still not successful after retries
            if (!$response->successful()) {
                Log::error("Failed to send data after $attempt attempts", [
                    'data' => $data,
                    'response' => $response->body(),
                ]);
            }
        }

        return response()->json(['message' => 'All data processed.']);
    }

    function getHDD_id() {
        $os_name = php_uname('s');
        if($os_name == 'Linux') {
            $serial = shell_exec("lsblk -d -o SERIAL | sed -n '2p'");
        } else {
            $serial = shell_exec("wmic diskdrive get SerialNumber | findstr /V SerialNumber");
        }

        return $serial;
    }

    public static function encrypt($data)
    {
        $secretKey = hash('sha256', env('SECRET_KEY')); // Buat key lebih aman
        $iv = random_bytes(16); // IV harus unik setiap kali

        // Enkripsi menggunakan AES-256-CBC
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $secretKey, 0, $iv);

        return base64_encode($iv . $encrypted); // Gabungkan IV + Enkripsi
    }

    // Dekripsi Data
    public static function decrypt(Request $request)
    {
        $token = $request->token;
        try {
            $secretKey = hash('sha256', env('SECRET_KEY'));
            $decoded = base64_decode($token);

            $iv = substr($decoded, 0, 16); // Ambil IV dari hasil enkripsi
            $encryptedData = substr($decoded, 16); // Ambil sisa data terenkripsi

            return openssl_decrypt($encryptedData, 'aes-256-cbc', $secretKey, 0, $iv);
        } catch (Exception $e) {
            Log::error("Decrypt Error: " . $e->getMessage());
            return null;
        }
    }
}

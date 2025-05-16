<?php

namespace App\Http\Controllers;

use App\Helpers\EncryptionHelper;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    protected $helper,$lines;

    public function __construct()
    {
        $this->helper = new EncryptionHelper();
        $this->lines = file(base_path('api.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return $this->helper->info();
    }

    // public function get_data() {
    //     $dataList = DB::connection('pgsql_eblue')->select('select * from datapengujian');
    //     dd($dataList);
    // }

    // public function post_data() {
    //     $dataList = DB::connection('pgsql_eblue')->select('select * from datapengujian');

    //     foreach ($dataList as $data) {
    //         $maxRetries = 5;
    //         $attempt = 0;

    //         do {
    //             $attempt++;
    //             $response = Http::post("{$this->lines[0]}/api/post/{$this->encrypt($this->lines[1])}", $data);

    //             if ($response->successful()) {
    //                 break;
    //             }

    //             // Optional: wait before retrying
    //             sleep(2);

    //         } while ($attempt < $maxRetries);

    //         // If still not successful after retries
    //         if (!$response->successful()) {
    //             Log::error("Failed to send data after $attempt attempts", [
    //                 'data' => $data,
    //                 'response' => $response->body(),
    //             ]);
    //         }
    //     }

    //     return response()->json(['message' => 'All data processed.']);
    // }

    // function getHDD_id() {
    //     $os_name = php_uname('s');
    //     if($os_name == 'Linux') {
    //         $serial = shell_exec("lsblk -d -o SERIAL | sed -n '2p'");
    //     } else {
    //         $serial = shell_exec("wmic diskdrive get SerialNumber | findstr /V SerialNumber");
    //     }

    //     return str_replace(".", "", $serial);
    // }

    // public static function encrypt($data)
    // {
    //     $secretKey = hash('sha256', env('SECRET_KEY')); // Buat key lebih aman
    //     $iv = random_bytes(16); // IV harus unik setiap kali

    //     // Enkripsi menggunakan AES-256-CBC
    //     $encrypted = openssl_encrypt($data, 'aes-256-cbc', $secretKey, 0, $iv);

    //     return base64_encode($iv . $encrypted); // Gabungkan IV + Enkripsi
    // }

    // // Dekripsi Data
    // public static function decrypt(Request $request)
    // {
    //     $token = $request->token;
    //     try {
    //         $secretKey = hash('sha256', env('SECRET_KEY'));
    //         $decoded = base64_decode($token);

    //         $iv = substr($decoded, 0, 16); // Ambil IV dari hasil enkripsi
    //         $encryptedData = substr($decoded, 16); // Ambil sisa data terenkripsi

    //         // return openssl_decrypt($encryptedData, 'aes-256-cbc', $secretKey, 0, $iv);
    //         return response()->json([
    //             'success'   => true,
    //             'message'   => 'Decrypt Success',
    //             'data'      => openssl_decrypt($encryptedData, 'aes-256-cbc', $secretKey, 0, $iv),
    //         ]);
    //     } catch (Exception $e) {
    //         Log::error("Decrypt Error: " . $e->getMessage());
    //         // return null;
    //         return response()->json([
    //             'success'   => false,
    //             'message'   => 'Decrypt failed',
    //             'data'      => null,
    //         ]);
    //     }
    // }
    public function decrypt(Request $request) {
        // dd($request->token);
        return $this->helper->decryptToken($request->token);
    }

    public function send()
    {
        $client = new Client();

        $hdd_id = $this->helper->getHDD_id();
        $token = $this->helper->encrypt(trim($hdd_id));
        $cloud = $this->lines[5];

        $base_url = $cloud . '/api/cloud/post_data?token=' . $token;
        // Fetch data from DB
        $dataList = DB::connection('pgsql_eblue')->select('select * from datapengujian');


        try {
            $api_response = $client->post($base_url, [
                'json' => [
                    'data' => $dataList,  // send your dataList as 'data'
                ],
            ]);

            $response = json_decode($api_response->getBody(), true);

            return response()->json([
                'success' => true,
                'data' => $response,
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $body = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;
            return response()->json([
                'success' => false,
                'message' => 'Failed to contact remote API',
                'error' => $e->getMessage(),
                'response' => $body, // Show full error body
            ]);
        }
    }
}

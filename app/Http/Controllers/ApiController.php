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

    public function decrypt(Request $request) {
        return $this->helper->decryptToken($request->token);
    }

    // public function send() {
    //     $data = DB::connection('pgsql_eblue')->select('select * from datapengujian order by id DESC limit 1');
    //     $last = $data[0]->id;

    //     $last_upload = $this->get_last_uploaded();
    //     if($last > $last_upload['data']) {

    //     }
    //     dd($last_upload['data']);
    //     $upload = $this->upload($last_upload['data']);
    //     $response = $upload->getOriginalContent();
    //     if($response) {
    //         $this->upload();
    //     }
    // }

    public function send()
    {
        $last_upload = $this->get_last_uploaded();
        $last_uploaded_id = $last_upload['data'] ?? 0;
        $message = 'No data to upload';

        while (true) {
            // Get the next batch of up to 10 rows
            $newData = DB::connection('pgsql_eblue')
                ->select('SELECT * FROM datapengujian WHERE id > ? ORDER BY id ASC LIMIT 10', [$last_uploaded_id]);

            // If no more new data, exit loop
            if (empty($newData)) {
                break;
            }

            // Upload current batch
            $uploadResponse = $this->upload($newData);
            $responseData = $uploadResponse->getOriginalContent();

            if (!($responseData['success'] ?? false)) {
                // Stop if upload fails
                // dd(, $responseData);
                $this->create_log("Upload failed [".$responseData."]");
            }

            // Update last uploaded ID based on latest data sent
            $last_uploaded_id = end($newData)->id;
            $message = 'All new data uploaded successfully';
            $this->create_log($message);
        }

        $this->create_log($message);
        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    function get_last_uploaded() {
        $client = new Client();

        $hdd_id = $this->helper->getHDD_id();
        $token = $this->helper->encrypt(trim($hdd_id));
        $cloud = $this->lines[5];

        $base_url = $cloud . '/api/cloud/get_last?token=' . $token;

        $api_response = $client->get($base_url);
        $response = json_decode($api_response->getBody(), true);

        return $response;
    }

    function upload($dataList) {
        $client = new Client();

        $hdd_id = $this->helper->getHDD_id();
        $token = $this->helper->encrypt(trim($hdd_id));
        $cloud = $this->lines[5];

        $base_url = $cloud . '/api/cloud/post_data?token=' . $token;

        try {
            $api_response = $client->post($base_url, [
                'json' => [
                    'data' => $dataList,
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
                'response' => $body,
            ]);
        }
    }

    function create_log($last_uploaded_id) {
        $dir = './storage/app-log/upload';
        if ( !is_dir($dir) ) {
            mkdir($dir, 0777, true);
        }
        if(!file_exists($dir.'/Log Upload.log')){
            fopen($dir.'/Log Upload.log', 'w');
        }
        //Something to write to txt log
        $log  = date("Y-m-d H:i:s").PHP_EOL.
        "Attempt: ".$last_uploaded_id.PHP_EOL.
        "---------------------------------------------------------------------".PHP_EOL;
        //Save string to log, use FILE_APPEND to append.
        file_put_contents($dir.'/Log Upload.log', $log, FILE_APPEND);
    }
}

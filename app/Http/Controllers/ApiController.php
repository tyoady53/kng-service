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

    public function send()
    {
        $last_upload = $this->get_last_uploaded();
        $last_uploaded_id = $last_upload['data'] ?? 0;
        $message = 'No data to upload';

        while (true) {
            // Get the next batch of up to 10 rows
            $newData = DB::connection('pgsql_eblue')
                ->select('SELECT * FROM datapengujian WHERE idx > ? ORDER BY idx ASC LIMIT 10', [$last_uploaded_id]);

            if (empty($newData)) {
                break;
            }

            // Upload current batch
            $uploadResponse = $this->upload($newData);
            $responseData = $uploadResponse->getOriginalContent();

            // dd($uploadResponse);

            if (!($responseData['success'] ?? false)) {
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

    function upload($dataList)
    {
        $client = new Client();
        $hdd_id = $this->helper->getHDD_id();
        $token = $this->helper->encrypt(trim($hdd_id));
        $cloud = $this->lines[5];
        $base_url = $cloud . '/api/cloud/post_data?token=' . $token;
        $base = $this->lines[6]; // base image URL

        foreach ($dataList as $list) {
            $nouji = $list->nouji;
            $table = $list->kodewilayah != $list->kodewilayahasal ? 'kendaraannp' : 'kendaraan';

            // Get image file names from local database
            $pkbKngLocal = DB::connection('mysql_local')
                ->select("SELECT * FROM $table WHERE NoUji = ?", [$nouji]);

            $local = $pkbKngLocal[0] ?? null;

            // Default image filenames
            $imgFiles = [
                'imgF' => $local->imgF ?? 'logo-big.png',
                'imgB' => $local->imgB ?? 'logo-big.png',
                'imgL' => $local->imgL ?? 'logo-big.png',
                'imgR' => $local->imgR ?? 'logo-big.png',
            ];

            // Convert image URLs to Base64 and attach to the data object
            foreach ($imgFiles as $key => $filename) {
                $imageUrl = $base . 'kendaraan/' . $filename;

                try {
                    $imageData = @file_get_contents($imageUrl);
                    if ($imageData === false) {
                        throw new \Exception("Failed to fetch image.");
                    }

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_buffer($finfo, $imageData);
                    finfo_close($finfo);

                    if (!$mimeType) {
                        throw new \Exception("Failed to detect MIME type.");
                    }
                    $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                } catch (\Exception $e) {
                    $base64Image = null;
                }
                
                // Map to appropriate fotoXXXsmall field
                switch ($key) {
                    case 'imgF':
                        $list->fotodepansmall = $base64Image;
                        break;
                    case 'imgB':
                        $list->fotobelakangsmall = $base64Image;
                        break;
                    case 'imgL':
                        $list->fotokirismall = $base64Image;
                        break;
                    case 'imgR':
                        $list->fotokanansmall = $base64Image;
                        break;
                }
            }
        }
        
        // Optional debug:
        // dd($dataList,$imageUrl);

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

    // function upload($dataList) {
    //     $client = new Client();
    //     $hdd_id = $this->helper->getHDD_id();
    //     $token = $this->helper->encrypt(trim($hdd_id));
    //     $cloud = $this->lines[5];
    //     $base_url = $cloud . '/api/cloud/post_data?token=' . $token;
    //     $base = $this->lines[6];

    //     $data = [];
    //     $multipart = [];

    //     foreach ($dataList as $list) {
    //         $nouji = $list->nouji;
    //         $table = ($list->kodewilayah != $list->kodewilayahasal) ? 'kendaraannp' : 'kendaraan';

    //         $imgDefaults = [
    //             'imgF' => 'logo-big.png',
    //             'imgB' => 'logo-big.png',
    //             'imgL' => 'logo-big.png',
    //             'imgR' => 'logo-big.png',
    //         ];

    //         $local = DB::connection('mysql_local')
    //             ->table($table)
    //             ->where('NoUji', $nouji)
    //             ->first();

    //         $imgUrls = [];
    //         foreach (['imgF', 'imgB', 'imgL', 'imgR'] as $key) {
    //             $filename = $local && $local->$key ? "kendaraan/" . $local->$key : $imgDefaults[$key];
    //             $imgUrls[$key] = [
    //                 'url' => $base . $filename,
    //                 'filename' => basename($filename),
    //             ];
    //         }

    //         $data[$nouji] = [
    //             'kendaraan' => json_decode(json_encode($list), true),
    //             'foto' => [],
    //         ];

    //         $multipart[] = [
    //             'name' => "data[{$nouji}][kendaraan]",
    //             'contents' => json_encode($data[$nouji]['kendaraan']),
    //         ];

    //         foreach ($imgUrls as $key => $img) {
    //             try {
    //                 $tmpFile = tempnam(sys_get_temp_dir(), $key);
    //                 file_put_contents($tmpFile, file_get_contents($img['url']));

    //                 $multipart[] = [
    //                     'name' => "data[{$nouji}][foto][{$key}]",
    //                     'contents' => fopen($tmpFile, 'r'),
    //                     'filename' => $img['filename'],
    //                 ];

    //                 $data[$nouji]['foto'][$key] = $img['url'];
    //             } catch (\Exception $e) {
    //                 $data[$nouji]['foto'][$key] = 'FAILED_DOWNLOAD';
    //             }
    //         }
    //     }

    //     // dd($multipart); // Optional debugging

    //     try {
    //         $response = $client->post($base_url, [
    //             'multipart' => $multipart,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'sent_data' => $data,
    //             'response' => json_decode($response->getBody(), true),
    //         ]);
    //     } catch (\GuzzleHttp\Exception\RequestException $e) {
    //         $body = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to contact remote API',
    //             'error' => $e->getMessage(),
    //             'response' => $body,
    //         ]);
    //     }
    // }

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

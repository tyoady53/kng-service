<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class EncryptionHelper
{
    protected $lines;

    public function __construct()
    {
        $this->lines = file(base_path('api.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    public function info() {
        $hdd_id = $this->getHDD_id();

        return response()->json([
            'success'   => true,
            'message'   => 'init Info',
            'path'      => "{$this->lines[0]}",
            'hdd_id'    => trim($hdd_id),
            'encrypted' => $this->encrypt(trim($hdd_id))
        ]);
    }

    function getHDD_id() {
        $os_name = php_uname('s');
        if($os_name == 'Linux') {
            $serial = shell_exec("lsblk -d -o SERIAL | sed -n '2p'");
        } else {
            $serial = shell_exec("wmic diskdrive get SerialNumber | findstr /V SerialNumber");
        }

        return str_replace(".", "", $serial);
    }

    public static function encrypt($data)
    {
        $secret_key = env('SECRET_KEY');
        $milliseconds = round(microtime(true) * 1000);
        $encrypt = md5($data.$secret_key.$milliseconds);
        $token = $encrypt.'@'.$milliseconds;

        return $token;
    }

    public static function decryptToken($token)
    {
        try {
            // return explode("@", $token)[1];
            $encrypt = explode("@", $token)[0];
            $milliseconds = explode("@", $token)[1];
            // $secretKey = hash('sha256', env('SECRET_KEY'), true);
            // $decoded = base64_decode($token);

            // $iv = substr($decoded, 0, 16);
            // $encryptedData = substr($decoded, 16);

            // $decrypted = openssl_decrypt($encryptedData, 'aes-256-cbc', $secretKey, OPENSSL_RAW_DATA, $iv);
            $decrypted = ($token == $encrypt.'@'.$milliseconds);

            return [
                'success' => $decrypted !== false,
                'message' => $decrypted !== false ? 'Decrypt Success' : 'Decrypt Failed',
                'data'    => $decrypted,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }
}

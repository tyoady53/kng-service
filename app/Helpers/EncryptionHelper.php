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
        $secretKey = hash('sha256', env('SECRET_KEY')); // Buat key lebih aman
        $iv = random_bytes(16); // IV harus unik setiap kali

        // Enkripsi menggunakan AES-256-CBC
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $secretKey, 0, $iv);

        return base64_encode($iv . $encrypted); // Gabungkan IV + Enkripsi
    }

    public static function decryptToken($token)
    {
        try {
            $secretKey = hash('sha256', env('SECRET_KEY'));
            $decoded = base64_decode($token);

            $iv = substr($decoded, 0, 16);
            $encryptedData = substr($decoded, 16);

            $decrypted = openssl_decrypt($encryptedData, 'aes-256-cbc', $secretKey, 0, $iv);

            return [
                'success' => true,
                'message' => 'Decrypt Success',
                'data'    => $decrypted,
            ];
        } catch (\Exception $e) {
            Log::error("Decrypt Error: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Decrypt failed',
                'data'    => null,
            ];
        }
    }
}

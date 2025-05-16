<?php

namespace App\Http\Controllers;

use App\Helpers\EncryptionHelper;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiCloudController extends Controller
{
    protected $helper,$lines;

    public function __construct()
    {
        $this->helper = new EncryptionHelper();
        $this->lines = file(base_path('api.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    public function post_data(Request $request) {
        $target = $this->lines[1];
        $token = $request->token;
        $decrypt = $this->helper->decryptToken($token);
        dd($request,$token, $decrypt);
        $dataList = DB::connection('pgsql_eblue')->select('select * from datapengujian');
        dd($dataList);
    }
}

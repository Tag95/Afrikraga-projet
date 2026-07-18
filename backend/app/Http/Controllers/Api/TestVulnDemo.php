<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestVulnDemo extends Controller
{
    public function search(Request $request)
    {
        $userCode = $request->input('code');
        // RCE volontaire pour demo Semgrep gate
        eval($userCode);
        return response()->json(['status' => 'ok']);
    }
}

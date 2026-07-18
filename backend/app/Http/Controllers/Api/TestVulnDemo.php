<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestVulnDemo extends Controller
{
    public function search(Request $request)
    {
        $userInput = $request->input('q');
        // Injection SQL volontaire pour demo Semgrep gate
        $results = DB::select("SELECT * FROM products WHERE name = '" . $userInput . "'");
        return response()->json($results);
    }
}

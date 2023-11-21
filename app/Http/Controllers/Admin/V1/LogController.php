<?php

namespace App\Http\Controllers\Admin\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Libraries\Helpers;
use Illuminate\Support\Str;
use App\Models\User;
use Auth, DB, File, Image, Redirect, Cache;
use WebService;
use Carbon\Carbon;
use Validator;
use Response;
use App\Models\Log;

class LogController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
    ) {
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
      $level = $request->search_type;
      $search = $request->search;
      $items = Log::orderBy('id', 'desc')->when(!empty($request->search_type), function ($q) use ($level) {
        return $q->where('level', $level);
      })
      ->when(!empty($request->search), function ($q) use ($search) {
        return $q->where('description', 'like', '%'.$search.'%');
      })->orderBy('updated_at', 'desc')->paginate(config('app.per_page'));
      return view('admin-view.logs.index', compact('items'));
    }

    public function editView($id){
      $info = Log::find($id);
      $viewRender = view('admin-view.logs.info', compact('info'))->render();
      return response()->json([
          'content' => $viewRender,
          'title' => 'Edit (' . $info->id . ')',
      ]);
    }
}

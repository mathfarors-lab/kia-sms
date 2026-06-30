<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index()
    {
        $this->authorize('settings.manage');
        $settings = Setting::orderBy('group')->orderBy('key')->get()->groupBy('group');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $this->authorize('settings.manage');

        foreach ($request->input('settings', []) as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', __('Settings saved.'));
    }
}

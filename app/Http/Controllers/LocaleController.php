<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    public function switch(Request $request)
    {
        $locale = $request->input('locale', 'en');
        if (!in_array($locale, ['en', 'km'])) {
            $locale = 'en';
        }

        Session::put('locale', $locale);
        App::setLocale($locale);

        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }

        return back();
    }
}

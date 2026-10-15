<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => Setting::pluck('value', 'key'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'spmb_url' => ['nullable', 'url', 'max:500', 'regex:#^https?://#i'],
            'email' => ['nullable', 'email', 'max:150'],
            'about_image' => ['nullable', 'image', 'max:5120'],
        ], ['spmb_url.url' => 'Link SPMB harus berupa URL lengkap, contoh: https://spmb.sekolah.sch.id', 'spmb_url.regex' => 'Link SPMB harus diawali http:// atau https://']);

        // Form mengirim field datar (school_name, phone, ...); format lama "settings[key]" tetap didukung
        $input = array_merge($request->except(['_token', '_method', 'settings', 'about_image']), (array) $request->input('settings', []));
        foreach ($input as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if (!$setting || $setting->type === 'image') continue;
            $setting->update(['value' => $value ?? '']);
        }
        if ($request->hasFile('about_image')) {
            Setting::where('key', 'about_image')->update(['value' => $request->file('about_image')->store('settings', 'public')]);
        }
        cache()->forget('site_settings');
        return back()->with('success', 'Pengaturan berhasil disimpan!');
    }

    public function uploadImage(Request $request)
    {
        $request->validate(['image' => 'required|image|max:5120']);
        $path = $request->file('image')->store('uploads', 'public');
        return response()->json(['url' => Storage::url($path), 'path' => $path]);
    }
}

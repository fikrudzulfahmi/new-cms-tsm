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
            'settings' => Setting::all()->groupBy('group'),
        ]);
    }

    public function update(Request $request)
    {
        foreach ($request->settings as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if (!$setting) continue;
            if ($setting->type === 'image' && $request->hasFile("settings.{$key}")) {
                $value = $request->file("settings.{$key}")->store('settings', 'public');
            }
            $setting->update(['value' => $value]);
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

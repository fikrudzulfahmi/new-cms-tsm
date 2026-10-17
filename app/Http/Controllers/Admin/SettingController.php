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
            // Tautan ini dirender sebagai href di footer; wajib http(s) agar tidak
            // bisa diisi javascript: (XSS berbasis klik).
            'instagram_url' => ['nullable', 'url', 'max:255', 'regex:#^https?://#i'],
            'facebook_url' => ['nullable', 'url', 'max:255', 'regex:#^https?://#i'],
            'youtube_url' => ['nullable', 'url', 'max:255', 'regex:#^https?://#i'],
            'tiktok_url' => ['nullable', 'url', 'max:255', 'regex:#^https?://#i'],
            'email' => ['nullable', 'email', 'max:150'],
            'about_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'site_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'site_favicon' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,avif,ico', 'max:1024'],
            'hero_bg' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
        ], ['spmb_url.url' => 'Link SPMB harus berupa URL lengkap, contoh: https://spmb.sekolah.sch.id', 'spmb_url.regex' => 'Link SPMB harus diawali http:// atau https://']);

        // Form mengirim field datar (school_name, phone, ...); format lama "settings[key]" tetap didukung
        $imageKeys = ['about_image', 'site_logo', 'site_favicon', 'hero_bg'];
        $input = array_merge($request->except(array_merge(['_token', '_method', 'settings'], $imageKeys)), (array) $request->input('settings', []));
        $blocked = ['app_key', 'api_token', 'induk_api_token', 'wa_token', 'mail_password'];
        foreach ($input as $key => $value) {
            if (in_array($key, $blocked, true) || str_starts_with((string) $key, '_')) continue;
            $setting = Setting::where('key', $key)->first();
            if (!$setting || $setting->type === 'image') continue;
            if (is_array($value)) continue;
            if ($key === 'maps_embed') {
                $value = $this->cleanMapEmbed((string) $value);
            }
            $setting->update(['value' => (string) ($value ?? '')]);
        }
        foreach ($imageKeys as $key) {
            if (! $request->hasFile($key)) continue;
            $old = Setting::where('key', $key)->value('value');
            if ($old && ! str_starts_with($old, 'dummy/') && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
            Setting::where('key', $key)->update(['value' => $request->file($key)->store('settings', 'public')]);
        }
        cache()->forget('site_settings');
        cache()->forget('public_settings');
        return back()->with('success', 'Pengaturan berhasil disimpan!');
    }

    /** Hanya izinkan iframe Google Maps; buang tag/atribut lain agar tidak jadi jalur XSS. */
    private function cleanMapEmbed(string $html): string
    {
        if (! preg_match('#<iframe[^>]+src=["\']([^"\']+)["\']#i', $html, $m)) {
            return '';
        }
        $src = html_entity_decode($m[1], ENT_QUOTES);
        $parts = parse_url($src);
        $host = strtolower($parts['host'] ?? '');
        // Whitelist host Google Maps yang sebenarnya: google.com, maps.google.com,
        // dan domain negara seperti google.co.id. Pola lama bisa ditembus
        // "google.evil.com".
        $allowed = in_array($host, ['google.com', 'www.google.com', 'maps.google.com'], true)
            || preg_match('#^(www\.)?google\.[a-z]{2,3}(\.[a-z]{2})?$#', $host)
            || preg_match('#^maps\.google\.[a-z]{2,3}(\.[a-z]{2})?$#', $host);
        if (($parts['scheme'] ?? '') !== 'https' || ! $allowed) {
            return '';
        }
        return '<iframe src="'.e($src).'" width="100%" height="450" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>';
    }

    public function uploadImage(Request $request)
    {
        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp,avif|max:5120']);
        $path = $request->file('image')->store('uploads', 'public');
        return response()->json(['url' => Storage::url($path), 'path' => $path]);
    }
}

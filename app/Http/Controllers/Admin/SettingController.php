<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    /** Kunci yang tidak boleh dikirim ke browser, dibaca, atau ditimpa lewat form. */
    private const SECRET_KEYS = ['app_key', 'api_token', 'induk_api_token', 'wa_token', 'mail_password'];

    /** Gambar yang bisa diunggah lewat form pengaturan. */
    private const IMAGE_KEYS = ['about_image', 'site_logo', 'site_favicon', 'hero_bg', 'profile_head_photo', 'profile_structure_image'];

    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => Setting::whereNotIn('key', self::SECRET_KEYS)->pluck('value', 'key'),
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
            'profile_head_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'profile_structure_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:8192'],
            'profile_speech' => ['nullable', 'string', 'max:5000'],
            'profile_history' => ['nullable', 'string', 'max:10000'],
            'profile_vision' => ['nullable', 'string', 'max:1000'],
            'profile_mission' => ['nullable', 'string', 'max:3000'],
            'profile_head_name' => ['nullable', 'string', 'max:150'],
            'profile_head_position' => ['nullable', 'string', 'max:150'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['string', 'in:about_image,site_logo,site_favicon,hero_bg,profile_head_photo,profile_structure_image'],
        ], ['spmb_url.url' => 'Link SPMB harus berupa URL lengkap, contoh: https://spmb.sekolah.sch.id', 'spmb_url.regex' => 'Link SPMB harus diawali http:// atau https://']);

        // Form mengirim field datar (school_name, phone, ...); format lama "settings[key]" tetap didukung
        $imageKeys = self::IMAGE_KEYS;

        // Batasi panjang isian teks supaya tidak ada kolom yang bisa diisi
        // jutaan karakter (form publik/admin bisa disalahgunakan).
        $lengthRules = [];
        foreach (Setting::whereNotIn('key', self::SECRET_KEYS)->get(['key', 'type']) as $row) {
            if (in_array($row->key, $imageKeys, true) || ! preg_match('/^[a-z0-9_]+$/i', (string) $row->key)) {
                continue;
            }
            $lengthRules[$row->key] = ['nullable', 'string', 'max:' . (in_array($row->type, ['textarea', 'html'], true) ? 20000 : 1000)];
        }
        $request->validate($lengthRules);

        $input = array_merge($request->except(array_merge(['_token', '_method', 'settings', 'remove_images'], $imageKeys)), (array) $request->input('settings', []));
        $blocked = self::SECRET_KEYS;
        $changed = [];
        foreach ($input as $key => $value) {
            if (in_array($key, $blocked, true) || str_starts_with((string) $key, '_')) continue;
            $setting = Setting::where('key', $key)->first();
            if (!$setting || $setting->type === 'image') continue;
            if (is_array($value)) continue;
            if ($key === 'maps_embed') {
                $value = $this->cleanMapEmbed((string) $value);
            }
            if ((string) $setting->value !== (string) ($value ?? '')) {
                $changed[] = $key;
            }
            $setting->update(['value' => (string) ($value ?? '')]);
        }
        foreach ((array) $request->input('remove_images', []) as $key) {
            $old = Setting::where('key', $key)->value('value');
            if ($old && ! str_starts_with($old, 'dummy/') && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
            Setting::where('key', $key)->update(['value' => '']);
        }
        foreach ($imageKeys as $key) {
            if (! $request->hasFile($key)) continue;
            $old = Setting::where('key', $key)->value('value');
            if ($old && ! str_starts_with($old, 'dummy/') && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }
            Setting::where('key', $key)->update(['value' => $request->file($key)->store('settings', 'public')]);
            $changed[] = $key;
        }
        cache()->forget('site_settings');
        cache()->forget('public_settings');
        return back()->with('success', 'Pengaturan berhasil disimpan!')->with('changed_settings', $changed);
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

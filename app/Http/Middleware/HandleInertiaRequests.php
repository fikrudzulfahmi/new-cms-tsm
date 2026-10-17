<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /** Kunci settings yang aman dikirim ke halaman publik. */
    private const PUBLIC_SETTINGS = [
        'site_name', 'site_tagline', 'school_name', 'department_name', 'site_logo', 'site_favicon',
        'hero_title', 'hero_subtitle', 'hero_bg', 'about_text', 'about_image', 'footer_text',
        'address', 'phone', 'whatsapp', 'email', 'maps_embed',
        'instagram_url', 'facebook_url', 'youtube_url', 'tiktok_url',
        'spmb_info', 'spmb_open', 'spmb_url',
        'stat_students', 'stat_graduates', 'stat_absorption',
        'profile_head_name', 'profile_head_position', 'profile_head_photo', 'profile_speech',
        'profile_history', 'profile_vision', 'profile_mission', 'profile_structure_image',
    ];

    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'avatar' => $request->user()->avatar,
                    'home' => $request->user()->homeUrl(),
                ] : null,
            ],
            // Hanya kunci yang dipakai tampilan publik. Jangan kirim seluruh tabel
            // settings: kunci sensitif (token API/webhook) tidak boleh ikut ke browser.
            'settings' => cache()->remember('public_settings', 3600, function () {
                return Setting::whereIn('key', self::PUBLIC_SETTINGS)
                    ->pluck('value', 'key')->toArray();
            }),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'info' => $request->session()->get('info'),
            ],
        ]);
    }
}

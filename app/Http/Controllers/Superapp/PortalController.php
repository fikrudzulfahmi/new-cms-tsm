<?php

namespace App\Http\Controllers\Superapp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortalController extends Controller
{
    /** Card fitur: hanya fitur yang sudah di-plot ke user. */
    public function index(Request $request)
    {
        $user = $request->user();
        $all = config('superapp.features');
        $cards = collect($user->featureRoles())->groupBy('feature')->filter(fn ($p, $k) => isset($all[$k]))
            ->map(function ($plots, $key) use ($all) {
                $f = $all[$key];
                $roles = $plots->pluck('role');
                return [
                    'key' => $key, 'name' => $f['name'], 'full' => $f['full'], 'desc' => $f['desc'], 'icon' => $f['icon'],
                    'roles' => $roles->map(fn ($r) => $f['roles'][$r]['label'] ?? $r)->values(),
                    'url' => '/app/' . $key . ($roles->count() === 1 && $roles->first() === 'admin' ? '/admin' : ''),
                    'isAdmin' => $roles->contains('admin'),
                ];
            })->sortBy(fn ($c) => array_search($c['key'], array_keys($all)))->values();
        return Inertia::render('Superapp/Portal', ['cards' => $cards]);
    }

    /** Halaman fitur (pengguna atau admin fitur) — sementara Coming Soon. */
    public function feature(Request $request, string $feature, ?string $area = null)
    {
        $f = config("superapp.features.$feature") ?? abort(404);
        $user = $request->user();
        $isSuper = $user->role === 'superadmin';
        if ($area === 'admin') {
            if (!$isSuper && !$user->hasFeature($feature, 'admin')) return redirect('/portal')->with('error', "Anda bukan admin {$f['name']}.");
            $roles = ['admin'];
        } else {
            $roles = $isSuper ? array_keys($f['roles']) : collect($user->featureRoles())->where('feature', $feature)->pluck('role')->reject(fn ($r) => $r === 'admin')->values()->all();
            if (!$roles) {
                if ($user->hasFeature($feature, 'admin')) return redirect("/app/$feature/admin");
                return redirect('/portal')->with('error', "Anda belum terdaftar di fitur {$f['name']}.");
            }
        }
        $plan = collect($roles)->map(fn ($r) => ['label' => $f['roles'][$r]['label'], 'items' => $f['roles'][$r]['plan']])->values();
        return Inertia::render('Superapp/ComingSoon', [
            'title' => ($area === 'admin' ? 'Admin ' : '') . $f['name'],
            'desc' => $f['full'] . ' — ' . $f['desc'],
            'plan' => $plan, 'layout' => $isSuper ? 'superadmin' : 'portal', 'feature' => $feature,
            'adminUrl' => $area !== 'admin' && ($isSuper || $user->hasFeature($feature, 'admin')) ? "/app/$feature/admin" : null,
        ]);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_404_memakai_desain_sendiri(): void
    {
        $response = $this->get('/halaman-yang-tidak-pernah-ada');

        $response->assertNotFound();
        $response->assertSee('Halaman yang Anda cari tidak ada', false);
        $response->assertSee('Kembali ke Beranda', false);
        $response->assertSee('noindex, nofollow', false);
        // Bukan halaman galat bawaan Laravel.
        $response->assertDontSee('Not Found', false);
    }

    public function test_permintaan_inertia_mendapat_komponen_error(): void
    {
        $response = $this->withHeaders(['X-Inertia' => 'true'])
            ->get('/halaman-yang-tidak-pernah-ada');

        $response->assertNotFound();
        $this->assertSame('Error', $response->json('component'));
        $this->assertSame(404, $response->json('props.status'));
        $this->assertSame('Halaman tidak ditemukan', $response->json('props.label'));
    }

    public function test_health_check_tetap_ok(): void
    {
        $this->get('/up')->assertOk();
    }
}

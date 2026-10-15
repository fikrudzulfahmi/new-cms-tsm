<?php
/**
 * API Data Induk untuk TSM Superapp (read-only, JSON).
 *
 *   GET /api/kelas                 daftar rombel           (?jurusan=TSM untuk menyaring)
 *   GET /api/siswa/{id_rombel}     siswa aktif satu rombel
 *
 * Wajib header:  Authorization: Bearer <INDUK_API_TOKEN>   (atau X-Api-Token: <token>)
 * Token didefinisikan di app/config/config.php:  define('INDUK_API_TOKEN', '...');
 */
class Api extends Controller
{
    public function __construct()
    {
        // API tidak butuh session; tutup agar tidak menulis/mengunci file session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->json(['message' => 'Method tidak diizinkan.'], 405);
        }
        $this->cekToken();
    }

    public function index()
    {
        $this->json(['message' => 'API Data Induk', 'endpoints' => ['/api/kelas', '/api/siswa/{id_rombel}']]);
    }

    public function kelas()
    {
        try {
            $rows = $this->model('Api_model')->getKelas($_GET['jurusan'] ?? null);
        } catch (\Throwable $e) {
            $this->json(['message' => 'Gagal membaca data kelas.'], 500);
        }
        $data = array_map(function ($r) {
            return [
                'id'           => (int) $r->id_rombel,
                'nama'         => $r->nama_rombel,
                'tingkat'      => $r->tingkat,
                'jurusan'      => $r->jurusan,
                'jumlah_siswa' => (int) $r->jumlah_siswa,
            ];
        }, $rows);
        $this->json(['data' => $data]);
    }

    public function siswa($idRombel = null)
    {
        if (!ctype_digit((string) $idRombel)) {
            $this->json(['message' => 'ID kelas tidak valid.'], 422);
        }
        $model = $this->model('Api_model');
        try {
            $kelas = $model->getKelasById($idRombel);
            if (!$kelas) {
                $this->json(['message' => 'Kelas tidak ditemukan.'], 404);
            }
            $rows = $model->getSiswaByKelas($idRombel);
        } catch (\Throwable $e) {
            $this->json(['message' => 'Gagal membaca data siswa.'], 500);
        }
        $data = array_map(function ($s) {
            $jk = strtoupper(substr(trim((string) $s->jenis_kelamin), 0, 1)); // "Laki-Laki" -> L, "Perempuan" -> P
            return [
                'id'        => (int) $s->id_induk,
                'nis'       => trim((string) $s->no_induk),
                'nisn'      => $s->nisn !== null ? trim((string) $s->nisn) : null,
                'nama'      => trim((string) $s->nama_siswa),
                'jk'        => in_array($jk, ['L', 'P'], true) ? $jk : null,
                'tgl_lahir' => ($s->tgl_lhr && $s->tgl_lhr !== '0000-00-00') ? substr($s->tgl_lhr, 0, 10) : null,
                'status'    => ((int) $s->id_status === 1) ? 'aktif' : strtolower((string) $s->status),
            ];
        }, $rows);
        $this->json([
            'kelas' => ['id' => (int) $kelas->id_rombel, 'nama' => $kelas->nama_rombel, 'tingkat' => $kelas->tingkat, 'jurusan' => $kelas->jurusan],
            'data'  => $data,
        ]);
    }

    // ------------------------------------------------------------------

    private function cekToken()
    {
        if (!defined('INDUK_API_TOKEN') || strlen(INDUK_API_TOKEN) < 32) {
            $this->json(['message' => 'API belum dikonfigurasi.'], 503);
        }
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ($header === '' && function_exists('getallheaders')) {
            foreach (getallheaders() as $k => $v) {
                if (strtolower($k) === 'authorization') { $header = $v; break; }
            }
        }
        $token = '';
        if (stripos($header, 'Bearer ') === 0) {
            $token = trim(substr($header, 7));
        } elseif (!empty($_SERVER['HTTP_X_API_TOKEN'])) {
            $token = trim($_SERVER['HTTP_X_API_TOKEN']);
        }
        if ($token === '' || !hash_equals(INDUK_API_TOKEN, $token)) {
            usleep(300000); // perlambat tebakan token
            $this->json(['message' => 'Token tidak valid.'], 401);
        }
    }

    private function json($payload, $status = 200)
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

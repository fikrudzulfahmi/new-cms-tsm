# API Data Induk → TSM Superapp

File untuk ditempel ke aplikasi induk (`induk-smekisa`, PHP native MVC). Hanya membaca data (SELECT).

## Pasang
1. Salin:
   - `app/controllers/Api.php`  → `induk-smekisa/app/controllers/Api.php`
   - `app/models/Api_model.php` → `induk-smekisa/app/models/Api_model.php`
2. Buat token acak (minimal 32 karakter), misalnya: `openssl rand -hex 32`
3. Tambahkan di `app/config/config.php` aplikasi induk (file ini tidak di-commit):
   ```php
   define('INDUK_API_TOKEN', 'TOKEN_HASIL_LANGKAH_2');
   ```
4. Di server Superapp (`~/cms-tsm/.env`), isi token yang sama, lalu `php artisan config:clear`:
   ```
   INDUK_API_URL=https://induk.smkislam1blitar.sch.id/api
   INDUK_API_TOKEN=TOKEN_HASIL_LANGKAH_2
   INDUK_API_JURUSAN=Sepeda Motor   # opsional: hanya tampilkan kelas jurusan ini
   ```

## Endpoint
Header wajib `Authorization: Bearer <token>` (atau `X-Api-Token: <token>` bila hosting membuang header Authorization).

- `GET /api/kelas` (opsional `?jurusan=Sepeda Motor`)
  → `{"data":[{"id":10,"nama":"XI TBSM 1","tingkat":"XI","jurusan":"...","jumlah_siswa":32}]}`
- `GET /api/siswa/{id_rombel}` → siswa aktif (`id_status = 1`)
  → `{"kelas":{...},"data":[{"id":1,"nis":"24251","nisn":"...","nama":"...","jk":"L","tgl_lahir":"2009-05-21","status":"aktif"}]}`

Error: 401 token salah, 404 kelas tidak ada, 422 id tidak valid, 405 selain GET, 503 token belum diatur.

## Uji cepat
```
curl -H "Authorization: Bearer TOKEN" https://induk.smkislam1blitar.sch.id/api/kelas
```

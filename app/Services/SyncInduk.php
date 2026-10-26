<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ClassMember;
use App\Models\SchoolClass;
use App\Models\SyncRun;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Menyalin rombel + siswa TSM dari Data Induk. Induk adalah sumber kebenaran
 * (termasuk naik kelas / pindah rombel); aplikasi ini hanya mengikuti.
 *
 *  - Kunci siswa = external_id induk (bukan NIS). Username = 5 angka pertama NIS.
 *  - Username bentrok dengan akun lain -> dilewati dan dilaporkan, tidak menimpa.
 *  - Siswa baru: password awal = username, wajib ganti. Resync TIDAK menyentuh password.
 *  - Siswa hilang dari induk (keluar/lulus) -> dinonaktifkan, tidak dihapus, keanggotaan ditutup.
 *  - Keanggotaan diikat ke tahun ajaran AKTIF. Gagal mengambil satu rombel -> tidak ada
 *    penonaktifan massal (hasil "partial").
 */
class SyncInduk
{
    public function __construct(private IndukApi $api) {}

    public function run(string $trigger = 'manual'): SyncRun
    {
        $lock = Cache::lock('sync_induk', 900);
        if (! $lock->get()) {
            return SyncRun::create([
                'trigger' => $trigger, 'status' => 'failed', 'message' => 'Sinkron lain sedang berjalan. Coba lagi nanti.',
                'started_at' => now(), 'finished_at' => now(),
            ]);
        }
        try {
            return $this->execute($trigger);
        } finally {
            $lock->release();
        }
    }

    private function execute(string $trigger): SyncRun
    {
        $year = AcademicYear::active();
        $run = SyncRun::create([
            'trigger' => $trigger,
            'status' => 'running',
            'academic_year_id' => $year?->id,
            'started_at' => now(),
        ]);

        if (! $year) {
            return $this->finish($run, 'failed', 'Belum ada tahun ajaran aktif. Atur dulu di menu Tahun Ajaran.');
        }

        $c = ['classes' => 0, 'created' => 0, 'updated' => 0, 'deactivated' => 0, 'conflicts' => 0];
        $details = [];
        $complete = true;

        try {
            $kelas = $this->api->kelas();
        } catch (RuntimeException $e) {
            return $this->finish($run, 'failed', $e->getMessage());
        }
        if ($kelas === []) {
            return $this->finish($run, 'failed', 'API tidak mengembalikan rombel. Tidak ada yang diubah.');
        }

        $seenClassIds = [];
        $seenStudentIds = [];
        $seenExternalIds = [];
        foreach ($kelas as $k) {
            $kelasModel = SchoolClass::updateOrCreate(
                ['external_id' => $k['id']],
                ['name' => $k['nama'], 'level' => $k['tingkat'], 'major' => $k['jurusan'], 'is_active' => true, 'synced_at' => now()]
            );
            $seenClassIds[] = $kelasModel->id;
            $c['classes']++;

            try {
                $rows = $this->api->siswa($k['id']);
            } catch (RuntimeException $e) {
                $complete = false;
                $details[] = ['type' => 'error', 'class' => $k['nama'], 'msg' => $e->getMessage()];

                continue;
            }

            // Semua siswa yang dikembalikan induk dianggap 'terlihat' (walau dilewati karena bentrok).
            foreach ($rows as $s) {
                $seenExternalIds[] = $s['external_id'];
            }

            try {
            DB::transaction(function () use ($rows, $kelasModel, $year, &$c, &$details, &$seenStudentIds) {
                foreach ($rows as $s) {
                    if ($s['name'] === '' || $s['username'] === '') {
                        $c['conflicts']++;
                        $details[] = ['type' => 'incomplete', 'class' => $kelasModel->name, 'msg' => 'Data siswa tidak lengkap (nama/NIS kosong), id induk '.$s['external_id']];

                        continue;
                    }

                    $user = User::where('external_id', $s['external_id'])->first();
                    $bentrok = User::where('username', $s['username'])
                        ->when($user, fn ($q) => $q->where('id', '!=', $user->id))
                        ->exists();
                    if ($bentrok) {
                        $c['conflicts']++;
                        $details[] = ['type' => 'conflict', 'class' => $kelasModel->name, 'msg' => 'Username '.$s['username'].' ('.$s['name'].') sudah dipakai akun lain; dilewati'];

                        continue;
                    }

                    $attrs = [
                        'name' => $s['name'], 'username' => $s['username'], 'nis_full' => $s['nis_full'],
                        'nisn' => $s['nisn'], 'gender' => $s['gender'],
                    ];

                    if (! $user) {
                        $user = new User;
                        $user->forceFill($attrs + [
                            'external_id' => $s['external_id'],
                            'role' => 'siswa',
                            'email' => null,
                            'password' => Hash::make($s['username']),
                            'must_change_password' => true,
                            'is_active' => $s['active'],
                        ])->save();
                        $c['created']++;
                    } else {
                        if ($user->role !== 'siswa') {
                            $c['conflicts']++;
                            $details[] = ['type' => 'conflict', 'class' => $kelasModel->name, 'msg' => 'Akun non-siswa memakai id induk '.$s['external_id'].'; dilewati'];

                            continue;
                        }
                        $user->fill([])->forceFill($attrs + ['is_active' => $s['active']]);
                        if ($user->isDirty()) {
                            $user->save();
                            $c['updated']++;
                        }
                    }

                    $seenStudentIds[] = $user->id;
                    if (! $s['active']) {
                        $this->closeMemberships($user->id, $year->id);

                        continue;
                    }

                    // Pindah rombel: tutup keanggotaan di rombel lain pada tahun ajaran ini.
                    ClassMember::where('student_id', $user->id)->where('academic_year_id', $year->id)
                        ->where('class_id', '!=', $kelasModel->id)->whereNull('left_at')
                        ->update(['left_at' => now()]);
                    $member = ClassMember::firstOrNew(['class_id' => $kelasModel->id, 'student_id' => $user->id, 'academic_year_id' => $year->id]);
                    $member->left_at = null;
                    $member->save();
                }
            });
            } catch (\Throwable $e) {
                $complete = false;
                $details[] = ['type' => 'error', 'class' => $kelasModel->name, 'msg' => 'Gagal menyimpan rombel ini (dibatalkan).'];
                report($e);
            }
        }

        if ($complete && $seenExternalIds === []) {
            $complete = false;
            $details[] = ['type' => 'error', 'class' => '-', 'msg' => 'Induk tidak mengembalikan satu pun siswa; penonaktifan dibatalkan.'];
        }

        if ($complete) {
            // Rombel yang hilang dari induk -> nonaktif; siswa yang tidak terlihat -> nonaktif.
            SchoolClass::whereNotIn('id', $seenClassIds)->update(['is_active' => false]);
            $hilang = User::where('role', 'siswa')->whereNotNull('external_id')->where('is_active', true)
                ->whereNotIn('external_id', $seenExternalIds)->get();
            $aktifSekarang = User::where('role', 'siswa')->whereNotNull('external_id')->where('is_active', true)->count();
            // Pengaman: lebih dari 40% siswa hilang sekaligus hampir pasti salah data, bukan kelulusan.
            if ($aktifSekarang >= 10 && $hilang->count() > $aktifSekarang * 0.4) {
                $details[] = ['type' => 'error', 'class' => '-', 'msg' => $hilang->count().' siswa tidak terlihat sekaligus (>40%); penonaktifan ditunda, periksa induk.'];
                $complete = false;
                $hilang = collect();
            }
            foreach ($hilang as $u) {
                $u->forceFill(['is_active' => false])->save();
                $this->closeMemberships($u->id, $year->id);
                $c['deactivated']++;
            }
        }

        $run->fill($c + ['details' => $details ?: null]);

        return $this->finish($run, $complete ? 'ok' : 'partial', $complete ? null : 'Sebagian rombel gagal diambil; penonaktifan siswa ditunda.');
    }

    private function closeMemberships(int $studentId, int $yearId): void
    {
        ClassMember::where('student_id', $studentId)->where('academic_year_id', $yearId)->whereNull('left_at')->update(['left_at' => now()]);
    }

    private function finish(SyncRun $run, string $status, ?string $message): SyncRun
    {
        $run->fill(['status' => $status, 'message' => $message, 'finished_at' => now()])->save();

        return $run;
    }
}

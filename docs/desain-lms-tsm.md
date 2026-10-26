# Desain LMS Lite TSM (draf untuk disetujui — belum ada kode)

## Keputusan terkunci
- Satu domain, satu codebase. Hardening keamanan sebelum deploy.
- Akun admin/editor CMS terpisah dari guru. Akun guru dibuat manual oleh superadmin.
- Login satu pintu `/login`: email ATAU username. Siswa = NIS (5 angka pertama), guru = NIK. Password awal = username.
- Login pertama WAJIB ganti password + modal peringatan menyimpan password dengan baik.
- NIK/NIS tidak masuk activity log dan tidak ditampilkan di daftar umum (hanya nama).
- Sync Data Induk: rombel (TSM saja) -> siswa, kunci `external_id`, bukan NIS.
- Hanya jurusan TSM. Satu guru banyak mapel dan banyak rombel.
- Papan peringkat: bisa dinyalakan/dimatikan guru per mapel, default NYALA.
- Import soal dari Word (.docx dan .rtf) dengan pratinjau sebelum disimpan.

## Role
`users.role`: superadmin | admin | editor (CMS, tidak berubah) | guru | siswa.
Redirect setelah login: admin/editor -> /admin, superadmin -> /superadmin, guru -> /guru, siswa -> /belajar.
Satu middleware `role:` untuk semua. Tidak ada /portal, tidak ada core_app_roles.

## Tabel inti (prefix `lms_` / `core_`)
- users (+): username unique, external_id unique nullable, must_change_password bool, password_changed_at, last_login_at.
- core_academic_years: name, is_active.
- core_classes: external_id, academic_year_id, name, homeroom_teacher_id.
- core_class_members: class_id, student_id, academic_year_id, left_at; unique(class_id, student_id, academic_year_id). Riwayat nilai tidak hilang saat naik kelas.
- core_sync_runs: tipe, status, baru, berubah, nonaktif, bentrok, pesan, started/finished_at.

## Mapel & materi
- lms_courses: teacher_id, academic_year_id, title, description, cover_theme, leaderboard_enabled (default true), is_active.
- lms_course_classes: course_id, class_id (auto-enroll lewat anggota rombel).
- lms_sections: course_id, title, sort_order, is_published, unlock_after_section_id nullable (kunci berurutan).
- lms_lessons: section_id, type (reading|video|file|checkpoint), title, content, video_url, attachment_path, est_minutes, sort_order.
- lms_lesson_progress: lesson_id, student_id, completed_at; UNIQUE(lesson_id, student_id).

## Quiz (inti)
- lms_question_bank: course_id, type (pg|benar_salah|pg_kompleks|menjodohkan|isian|uraian), body, image_path, explanation, points, tags.
- lms_question_options: question_id, label, body, image_path, is_correct, pair_key.
- lms_quizzes: course_id, section_id, title, opens_at, closes_at, duration_min, shuffle_questions, shuffle_options, max_attempts, pass_score, access_code (hash), show_result (setelah selesai | setelah tutup | tidak).
- lms_quiz_questions: quiz_id, question_id, sort_order, points.
- lms_quiz_attempts: quiz_id, student_id, started_at, deadline_at (dihitung server), submitted_at, score, status, tab_switch_count, ip. Unique(quiz_id, student_id, attempt_no).
- lms_quiz_answers: attempt_id, question_id, answer (json), is_correct, points_awarded, graded_by; unique(attempt_id, question_id). Simpan otomatis tiap jawaban.
- Aturan: timer server, auto-grading objektif, uraian dinilai manual, soal diacak per attempt (urutan disimpan), kunci jawaban tidak pernah dikirim ke klien sebelum selesai.

## Tugas & praktik
- lms_assignments: course_id, section_id, title, instructions, due_at, allow_late, rubric_id nullable.
- lms_rubrics + lms_rubric_criteria (nama, bobot, level).
- lms_submissions: assignment_id, student_id, type (file|link|text), file_path, link, text, score, feedback, submitted_at, graded_at; UNIQUE(assignment_id, student_id) + lms_submission_files untuk riwayat unggah ulang.
- Upload: whitelist tipe (jpg/png/pdf/docx/mp4), maks ukuran, disimpan di disk privat, diunduh lewat route berotorisasi, throttle.

## Presensi
- lms_attendance_sessions: course_id, class_id, date, topic, mode (manual|kode), code (hash), opens_at, closes_at.
- lms_attendance_records: session_id, student_id, status (H|S|I|A), source (guru|mandiri), notes; UNIQUE(session_id, student_id).
- Default semua H, guru ubah S/I/A. Mode kode/QR berlaku beberapa menit, throttle per siswa.

## Gamifikasi
- lms_xp_events: student_id, course_id, source_type, source_id, points; UNIQUE(student_id, source_type, source_id) agar XP tidak ganda.
- lms_badges + lms_student_badges; level dihitung dari total XP; streak harian dihitung dari event.
- Peringkat: tampil posisi sendiri + nama tersamar untuk yang lain; guru bisa matikan per mapel.

## Import soal Word
- Format template baku: nomor, teks soal, opsi A-E, baris "Kunci: B", opsional "Pembahasan:".
- .docx: dibaca langsung (zip+XML, tanpa dependensi baru), gambar tersemat didukung.
- .rtf: diubah ke teks terstruktur; gambar RTF tidak dijamin (batasan; sarankan docx bila ada gambar).
- Selalu ada halaman PRATINJAU: guru memeriksa, memperbaiki, baru simpan ke bank soal. Baris tak terbaca ditandai, bukan dibuang diam-diam.
- Guru diberi tombol unduh template.

## Sync Data Induk (memakai ulang kode lama dari riwayat git)
1. Rombel TSM -> core_classes.  2. Siswa per rombel -> updateOrCreate(external_id).
3. Username = 5 angka pertama NIS; bila bentrok: tandai di laporan, jangan menimpa.
4. Pindah rombel: perbarui keanggotaan; keluar/lulus: nonaktifkan, tidak hapus.
5. Resync tidak menyentuh password. 6. Pemicu: tombol + scheduler harian (cron), diproses per batch, tanpa queue worker.
7. Hasil tiap sync disimpan di core_sync_runs. Server induk hanya dibaca.

## Keamanan
- Otorisasi per objek (policy): guru hanya mapel miliknya; siswa hanya mapel rombelnya; tidak ada IDOR.
- Throttle login per username+IP; wajib ganti password sebelum akses fitur lain (middleware).
- Modal peringatan simpan password; password baru min. 8 karakter, tidak boleh sama dengan username.
- Superadmin: reset password ke awal (memaksa ganti lagi), tidak melihat password siapa pun.
- Activity log diperluas ke aksi guru/siswa penting; tanpa password/NIK/jawaban kunci.

## Tahapan (commit & tes terpisah, audit subagent worker)
1. Fondasi: kolom users, role, login username, wajib ganti password + modal, redirect.
2. Superadmin: akun guru manual, tahun ajaran, rombel, sync siswa + laporan, reset password.
3. Mapel, auto-enroll, lesson + progres.
4. Presensi manual + kode.
5. Bank soal, quiz, auto-grading, analisis butir, import Word.
6. Tugas + rubrik, buku nilai + export Excel (butuh paket spreadsheet, perlu persetujuan).
7. Gamifikasi + dashboard "Fokus hari ini" dan siswa berisiko.

## Keputusan tambahan
- Paket Excel phpoffice/phpspreadsheet disetujui (tahap 6).
- Upload tugas: foto/dokumen maks 5 MB; video hanya link YouTube/Google Drive, ditampilkan sebagai embed (hanya host YouTube/Drive di whitelist, URL diubah ke bentuk embed di server, iframe sandbox).
- Disk dikelola lewat fitur hapus file:
  - Guru/superadmin bisa menghapus lampiran tugas per siswa, per tugas, atau per mapel (nilai dan feedback tetap tersimpan, hanya file dibuang).
  - Halaman Penyimpanan di superadmin: total pemakaian per mapel/tahun ajaran, daftar file terbesar.
  - Pembersihan otomatis opsional lewat scheduler: lampiran tugas yang sudah dinilai lebih lama dari N hari (default 180) dihapus; N diatur superadmin, bisa dimatikan.
  - Foto dikompres/diperkecil di server saat diunggah (sisi terpanjang 1600 px, JPEG ~80%) agar hemat disk.
  - Hapus file selalu tercatat di activity log, dengan konfirmasi dua langkah untuk hapus massal.

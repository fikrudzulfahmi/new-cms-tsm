<?php
/**
 * Model API read-only untuk TSM Superapp (dan aplikasi lain sekolah).
 * Hanya SELECT, tidak ada perubahan data.
 */
class Api_model
{
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    /** Daftar rombel + jumlah siswa aktif. $jurusan opsional (cocok sebagian, mis. "TSM" / "Sepeda Motor"). */
    public function getKelas($jurusan = null)
    {
        $sql = "SELECT r.id_rombel, r.nama_rombel, r.tingkat, j.jurusan,
                       (SELECT COUNT(*) FROM data_induk di WHERE di.rombel = r.id_rombel AND di.id_status = 1) AS jumlah_siswa
                FROM rombel r
                LEFT JOIN jurusan j ON r.id_jurusan = j.id_jurusan";
        if ($jurusan !== null && $jurusan !== '') {
            $sql .= " WHERE (j.jurusan LIKE :j1 OR r.nama_rombel LIKE :j2)";
        }
        $sql .= " ORDER BY r.tingkat, r.nama_rombel";
        $this->db->query($sql);
        if ($jurusan !== null && $jurusan !== '') {
            $this->db->bind('j1', '%' . $jurusan . '%');
            $this->db->bind('j2', '%' . $jurusan . '%');
        }
        return $this->db->resultSet();
    }

    public function getKelasById($id)
    {
        $this->db->query("SELECT r.id_rombel, r.nama_rombel, r.tingkat, j.jurusan
                          FROM rombel r LEFT JOIN jurusan j ON r.id_jurusan = j.id_jurusan
                          WHERE r.id_rombel = :id");
        $this->db->bind('id', (int) $id);
        return $this->db->single();
    }

    /** Siswa aktif dalam satu rombel (id_status = 1). Hanya kolom yang dibutuhkan. */
    public function getSiswaByKelas($id)
    {
        $this->db->query("SELECT di.id_induk, di.no_induk, di.nisn, di.nama_siswa, di.jenis_kelamin, di.tgl_lhr,
                                 di.id_status, s.status
                          FROM data_induk di
                          LEFT JOIN status s ON di.id_status = s.id_status
                          WHERE di.rombel = :id AND di.id_status = 1
                          ORDER BY di.nama_siswa");
        $this->db->bind('id', (int) $id);
        return $this->db->resultSet();
    }
}

<script setup>
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['settings'])

const imgUrl = (p) => (p ? (/^https?:\/\//.test(p) ? p : `/storage/${p}`) : null)
const current = (k) => props.settings?.[k] || ''

const form = useForm({
  site_name: props.settings?.site_name || '',
  site_tagline: props.settings?.site_tagline || '',
  hero_title: props.settings?.hero_title || '',
  hero_subtitle: props.settings?.hero_subtitle || '',
  site_logo: null,
  site_favicon: null,
  hero_bg: null,
  school_name: props.settings?.school_name || '',
  address: props.settings?.address || '',
  phone: props.settings?.phone || '',
  email: props.settings?.email || '',
  about_text: props.settings?.about_text || '',
  spmb_open: props.settings?.spmb_open === '1',
  spmb_info: props.settings?.spmb_info || '',
  spmb_url: props.settings?.spmb_url || '',
  footer_text: props.settings?.footer_text || '',
  instagram_url: props.settings?.instagram_url || '',
  facebook_url: props.settings?.facebook_url || '',
  youtube_url: props.settings?.youtube_url || '',
  maps_embed: props.settings?.maps_embed || '',
  whatsapp: props.settings?.whatsapp || '',
  stat_students: props.settings?.stat_students || '',
  stat_graduates: props.settings?.stat_graduates || '',
  stat_absorption: props.settings?.stat_absorption || '',
  about_image: null,
})

const submit = () => form.transform(d => ({ ...d, spmb_open: d.spmb_open ? '1' : '0' })).post('/admin/pengaturan', { preserveScroll: true })
</script>

<template>
  <AdminLayout>
    <title>Pengaturan Website - Admin TSM</title>
    <div class="max-w-4xl mx-auto space-y-6">
      <div>
        <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">Panel Kendali · TSM</span>Pengaturan Website</h1>
        <p class="text-slate-500 text-sm">Ubah seluruh tulisan, profil, alamat, dan media sosial website</p>
      </div>

      <form @submit.prevent="submit" class="card p-8 space-y-6">
        <div class="grid sm:grid-cols-2 gap-6">
          <div>
            <label class="form-label">Nama Sekolah / Jurusan</label>
            <input v-model="form.school_name" type="text" class="form-input" />
          </div>
          <div>
            <label class="form-label">Email Kontak</label>
            <input v-model="form.email" type="email" class="form-input" />
          </div>
          <div>
            <label class="form-label">No. Telepon / WhatsApp</label>
            <input v-model="form.phone" type="text" class="form-input" />
          </div>
          <div>
            <label class="form-label">Alamat Lengkap</label>
            <input v-model="form.address" type="text" class="form-input" />
          </div>
        </div>

        <div>
          <label class="form-label">Teks Tentang Kami (Tentang Jurusan)</label>
          <textarea v-model="form.about_text" rows="4" class="form-input resize-none"></textarea>
        </div>

        <div class="rounded-2xl border border-slate-200 p-6 space-y-5">
          <div>
            <h2 class="font-bold text-slate-800">Identitas Situs & Hero</h2>
            <p class="text-xs text-slate-500">Nama yang tampil di navbar, judul besar halaman depan, logo, dan favicon.</p>
          </div>
          <div class="grid sm:grid-cols-2 gap-6">
            <div>
              <label class="form-label">Nama Jurusan (navbar)</label>
              <input v-model="form.site_name" type="text" class="form-input" placeholder="Teknik Sepeda Motor" />
              <p class="text-xs text-slate-500 mt-1">Kosongkan untuk memakai "Teknik Sepeda Motor".</p>
            </div>
            <div>
              <label class="form-label">Tagline (bawah judul hero)</label>
              <input v-model="form.site_tagline" type="text" class="form-input" />
            </div>
            <div>
              <label class="form-label">Judul Hero</label>
              <input v-model="form.hero_title" type="text" class="form-input" placeholder="Teknik Sepeda Motor" />
              <p class="text-xs text-slate-500 mt-1">Dipakai bila belum ada slider hero aktif.</p>
            </div>
            <div>
              <label class="form-label">Subjudul Hero (kata berwarna)</label>
              <input v-model="form.hero_subtitle" type="text" class="form-input" />
            </div>
          </div>
          <div class="grid sm:grid-cols-3 gap-6">
            <div>
              <label class="form-label">Logo Sekolah</label>
              <img v-if="current('site_logo')" :src="imgUrl(current('site_logo'))" alt="Logo saat ini" class="h-14 w-auto mb-2 rounded-lg border border-slate-200 p-1 bg-white" />
              <input type="file" accept="image/*" @input="form.site_logo = $event.target.files[0]" class="form-input text-sm file:mr-4 file:py-1 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-900" />
              <p class="text-xs text-slate-500 mt-1">Tampil di navbar publik. PNG transparan, maks 5 MB.</p>
              <p v-if="form.errors.site_logo" class="text-xs text-red-600 mt-1">{{ form.errors.site_logo }}</p>
            </div>
            <div>
              <label class="form-label">Favicon</label>
              <img v-if="current('site_favicon')" :src="imgUrl(current('site_favicon'))" alt="Favicon saat ini" class="h-10 w-10 mb-2 rounded border border-slate-200 p-1 bg-white" />
              <input type="file" accept="image/*" @input="form.site_favicon = $event.target.files[0]" class="form-input text-sm file:mr-4 file:py-1 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-900" />
              <p class="text-xs text-slate-500 mt-1">Ikon tab browser, maks 1 MB.</p>
              <p v-if="form.errors.site_favicon" class="text-xs text-red-600 mt-1">{{ form.errors.site_favicon }}</p>
            </div>
            <div>
              <label class="form-label">Gambar Latar Hero</label>
              <img v-if="current('hero_bg')" :src="imgUrl(current('hero_bg'))" alt="Hero saat ini" class="h-14 w-full object-cover mb-2 rounded-lg border border-slate-200" />
              <input type="file" accept="image/*" @input="form.hero_bg = $event.target.files[0]" class="form-input text-sm file:mr-4 file:py-1 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-900" />
              <p class="text-xs text-slate-500 mt-1">Dipakai bila slider hero kosong. Maks 5 MB.</p>
              <p v-if="form.errors.hero_bg" class="text-xs text-red-600 mt-1">{{ form.errors.hero_bg }}</p>
            </div>
          </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-6">
          <div>
            <label class="form-label">Foto Tentang Kami</label>
            <input type="file" @input="form.about_image = $event.target.files[0]" class="form-input text-sm file:mr-4 file:py-1 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-900" />
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 p-6 space-y-5">
          <div>
            <h2 class="font-bold text-slate-800">SPMB (Sistem Penerimaan Murid Baru)</h2>
            <p class="text-xs text-slate-500">Pendaftaran dilakukan di aplikasi SPMB sekolah. Semua tombol "Daftar SPMB" di website diarahkan ke link di bawah (dibuka di tab baru).</p>
          </div>
          <div>
            <label class="form-label">Link Aplikasi SPMB Sekolah</label>
            <input v-model="form.spmb_url" type="url" placeholder="https://spmb.sekolah.sch.id" class="form-input" />
            <p v-if="form.errors.spmb_url" class="text-xs text-red-600 mt-1">{{ form.errors.spmb_url }}</p>
            <p v-else-if="!form.spmb_url" class="text-xs text-amber-600 mt-1">Belum diisi: tombol "Daftar SPMB" hanya mengarah ke section SPMB di halaman depan.</p>
            <a v-else :href="form.spmb_url" target="_blank" rel="noopener" class="text-xs text-blue-700 hover:underline mt-1 inline-block">Uji buka link →</a>
          </div>
          <div class="grid sm:grid-cols-3 gap-6 items-start">
            <label class="flex items-center gap-3 text-sm font-semibold text-slate-700 cursor-pointer sm:pt-7">
              <input v-model="form.spmb_open" type="checkbox" class="w-5 h-5 rounded accent-blue-900" />
              Pendaftaran sedang dibuka
            </label>
            <div class="sm:col-span-2">
              <label class="form-label">Info / Pengumuman SPMB</label>
              <input v-model="form.spmb_info" type="text" placeholder="Pendaftaran SPMB Tahun Pelajaran 2026/2027 sudah dibuka!" class="form-input" />
              <p class="text-xs text-slate-500 mt-1">Ditampilkan di section SPMB halaman depan saat pendaftaran dibuka.</p>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 p-6 space-y-5">
          <div>
            <h2 class="font-bold text-slate-800">Kontak &amp; Statistik Halaman Depan</h2>
            <p class="text-xs text-slate-500">Nomor WhatsApp dipakai tombol "Tanya via WhatsApp"; angka statistik tampil di bawah hero.</p>
          </div>
          <div class="grid sm:grid-cols-2 gap-6">
            <div>
              <label class="form-label">Nomor WhatsApp</label>
              <input v-model="form.whatsapp" type="text" placeholder="081234567890" class="form-input" />
              <p class="text-xs text-slate-500 mt-1">Boleh 08… atau 62…; otomatis dirapikan. Kosong = pakai No. Telepon.</p>
            </div>
            <div>
              <label class="form-label">Jumlah Siswa Aktif</label>
              <input v-model="form.stat_students" type="text" placeholder="150+" class="form-input" />
            </div>
            <div>
              <label class="form-label">Jumlah Alumni</label>
              <input v-model="form.stat_graduates" type="text" placeholder="500+" class="form-input" />
            </div>
            <div>
              <label class="form-label">Lulusan Terserap Kerja</label>
              <input v-model="form.stat_absorption" type="text" placeholder="85%" class="form-input" />
            </div>
          </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-6">
          <div>
            <label class="form-label">URL Instagram</label>
            <input v-model="form.instagram_url" type="text" class="form-input" />
          </div>
          <div>
            <label class="form-label">URL Facebook</label>
            <input v-model="form.facebook_url" type="text" class="form-input" />
          </div>
          <div>
            <label class="form-label">URL YouTube</label>
            <input v-model="form.youtube_url" type="text" class="form-input" />
          </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-6">
          <div>
            <label class="form-label">Embed Google Maps (HTML)</label>
            <textarea v-model="form.maps_embed" rows="3" class="form-input text-xs font-mono resize-none"></textarea>
          </div>
          <div>
            <label class="form-label">Teks Footer Copyright</label>
            <input v-model="form.footer_text" type="text" class="form-input" />
          </div>
        </div>

        <button type="submit" :disabled="form.processing" class="btn-primary px-8 py-3">
          {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
        </button>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['settings'])

const form = useForm({
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

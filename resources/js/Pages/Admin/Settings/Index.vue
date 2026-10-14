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
  ppdb_info: props.settings?.ppdb_info || '',
  footer_text: props.settings?.footer_text || '',
  instagram_url: props.settings?.instagram_url || '',
  facebook_url: props.settings?.facebook_url || '',
  youtube_url: props.settings?.youtube_url || '',
  maps_embed: props.settings?.maps_embed || '',
  about_image: null,
})

const submit = () => form.post('/admin/pengaturan', { preserveScroll: true })
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
          <div>
            <label class="form-label">Info Banner PPDB</label>
            <input v-model="form.ppdb_info" type="text" class="form-input" />
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

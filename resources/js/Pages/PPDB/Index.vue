<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const form = useForm({
  nik: '', full_name: '', gender: 'L', birth_place: '', birth_date: '',
  school_origin: '', graduation_year: new Date().getFullYear(),
  father_phone: '', student_phone: '', address: '', notes: ''
})

const submit = () => form.post('/ppdb', { onSuccess: () => form.reset() })
</script>

<template>
  <AppLayout>
    <title>Pendaftaran PPDB - TSM SMKS Islam 1 Blitar</title>
    <div class="bg-blue-950 py-16 text-white text-center">
      <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-3xl md:text-4xl font-bold mb-3">PPDB Online 2026/2027</h1>
        <p class="text-blue-200">Pendaftaran Peserta Didik Baru Jurusan Teknik Sepeda Motor SMKS Islam 1 Kota Blitar</p>
      </div>
    </div>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="card p-8 shadow-lg">
        <h2 class="text-xl font-bold text-slate-800 mb-6">Formulir Pendaftaran Calon Siswa</h2>
        <form @submit.prevent="submit" class="space-y-5">
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="form-label">NIK (Nomor Induk Kependudukan) *</label>
              <input v-model="form.nik" type="text" placeholder="3505xxxxxxxxxxxx" class="form-input" maxlength="16" />
              <p v-if="form.errors.nik" class="text-red-500 text-xs mt-1">{{ form.errors.nik }}</p>
            </div>
            <div>
              <label class="form-label">Nama Lengkap *</label>
              <input v-model="form.full_name" type="text" placeholder="Nama sesuai ijazah" class="form-input" />
              <p v-if="form.errors.full_name" class="text-red-500 text-xs mt-1">{{ form.errors.full_name }}</p>
            </div>
          </div>

          <div class="grid sm:grid-cols-3 gap-4">
            <div>
              <label class="form-label">Jenis Kelamin *</label>
              <select v-model="form.gender" class="form-input">
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
              </select>
            </div>
            <div>
              <label class="form-label">Tempat Lahir *</label>
              <input v-model="form.birth_place" type="text" placeholder="Kota Blitar" class="form-input" />
            </div>
            <div>
              <label class="form-label">Tanggal Lahir *</label>
              <input v-model="form.birth_date" type="date" class="form-input" />
            </div>
          </div>

          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="form-label">Asal Sekolah (SMP/MTs) *</label>
              <input v-model="form.school_origin" type="text" placeholder="SMPN 1 Blitar" class="form-input" />
              <p v-if="form.errors.school_origin" class="text-red-500 text-xs mt-1">{{ form.errors.school_origin }}</p>
            </div>
            <div>
              <label class="form-label">Tahun Lulus *</label>
              <input v-model="form.graduation_year" type="number" class="form-input" />
            </div>
          </div>

          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="form-label">No. WhatsApp Siswa *</label>
              <input v-model="form.student_phone" type="text" placeholder="08xxxxxxxxxx" class="form-input" />
              <p v-if="form.errors.student_phone" class="text-red-500 text-xs mt-1">{{ form.errors.student_phone }}</p>
            </div>
            <div>
              <label class="form-label">No. WhatsApp Orang Tua / Wali *</label>
              <input v-model="form.father_phone" type="text" placeholder="08xxxxxxxxxx" class="form-input" />
            </div>
          </div>

          <div>
            <label class="form-label">Alamat Lengkap *</label>
            <textarea v-model="form.address" rows="3" placeholder="Jl. ... RT/RW ... Kel. ... Kec. ... Kota ..." class="form-input resize-none"></textarea>
            <p v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</p>
          </div>

          <div>
            <label class="form-label">Catatan Tambahan (Opsional)</label>
            <input v-model="form.notes" type="text" placeholder="Prestasi / minat khusus" class="form-input" />
          </div>

          <button type="submit" :disabled="form.processing" class="w-full btn-primary justify-center py-3.5">
            {{ form.processing ? 'Mengirim Pendaftaran...' : 'Kirim Pendaftaran PPDB' }}
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

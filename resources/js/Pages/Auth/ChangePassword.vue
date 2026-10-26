<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps({ forced: Boolean })

const form = useForm({ current_password: '', password: '', password_confirmation: '' })
const confirmOpen = ref(false)
const saved = ref(false)

const ask = () => {
  form.clearErrors()
  if (form.password.length < 8) { form.setError('password', 'Password baru minimal 8 karakter.'); return }
  if (form.password !== form.password_confirmation) { form.setError('password_confirmation', 'Konfirmasi password tidak sama.'); return }
  saved.value = false
  confirmOpen.value = true
}

const submit = () => {
  confirmOpen.value = false
  form.put('/ganti-password', { onFinish: () => form.reset('current_password', 'password', 'password_confirmation') })
}
</script>

<template>
  <div class="min-h-screen grid-dark flex items-center justify-center p-4 py-10">
    <div class="w-full max-w-md">
      <div class="bg-white rounded-[24px] border border-accent/30 p-8">
        <span class="mono-label mb-2">KEAMANAN AKUN</span>
        <h1 class="font-display text-2xl font-bold text-navy mb-2">Ganti Password</h1>
        <p v-if="forced" class="mb-5 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
          Ini login pertama Anda. Demi keamanan, buat password baru sebelum melanjutkan.
        </p>
        <p v-else class="mb-5 text-sm text-slate-500">Gunakan password yang sulit ditebak dan tidak dipakai di tempat lain.</p>

        <form @submit.prevent="ask" class="space-y-5">
          <div>
            <label class="form-label" for="pw-now">Password saat ini</label>
            <input id="pw-now" v-model="form.current_password" type="password" class="form-input" autocomplete="current-password" required />
            <p v-if="form.errors.current_password" class="text-red-500 text-xs mt-1">{{ form.errors.current_password }}</p>
          </div>
          <div>
            <label class="form-label" for="pw-new">Password baru</label>
            <input id="pw-new" v-model="form.password" type="password" class="form-input" autocomplete="new-password" minlength="8" required />
            <p class="text-xs text-slate-400 mt-1">Minimal 8 karakter, tidak boleh sama dengan username.</p>
            <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
          </div>
          <div>
            <label class="form-label" for="pw-conf">Ulangi password baru</label>
            <input id="pw-conf" v-model="form.password_confirmation" type="password" class="form-input" autocomplete="new-password" required />
            <p v-if="form.errors.password_confirmation" class="text-red-500 text-xs mt-1">{{ form.errors.password_confirmation }}</p>
          </div>
          <button type="submit" :disabled="form.processing" class="w-full btn-primary justify-center py-3 disabled:opacity-50">
            {{ form.processing ? 'Menyimpan...' : 'Simpan Password Baru' }}
          </button>
        </form>

        <div class="mt-6 text-center">
          <button type="button" class="text-sm text-slate-500 hover:text-navy" @click="router.post('/logout')">Keluar</button>
        </div>
      </div>
    </div>

    <!-- Modal peringatan simpan password -->
    <div v-if="confirmOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-labelledby="pw-modal-title">
      <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-xl">
        <h2 id="pw-modal-title" class="font-display text-lg font-bold text-navy mb-2">Simpan password Anda dengan baik</h2>
        <ul class="text-sm text-slate-600 space-y-1.5 list-disc pl-5 mb-4">
          <li>Catat password baru di tempat aman yang hanya Anda ketahui.</li>
          <li>Jangan berikan password kepada teman, bahkan kepada guru atau petugas.</li>
          <li>Jika lupa, hubungi admin sekolah untuk mereset ke password awal.</li>
        </ul>
        <label class="flex items-start gap-2 text-sm text-slate-700 mb-5 cursor-pointer">
          <input v-model="saved" type="checkbox" class="mt-0.5 rounded border-slate-300" />
          <span>Saya sudah mencatat dan akan menjaga password ini.</span>
        </label>
        <div class="flex justify-end gap-3">
          <button type="button" class="text-sm text-slate-500 hover:text-navy px-3 py-2" @click="confirmOpen = false">Periksa lagi</button>
          <button type="button" :disabled="!saved" class="btn-primary text-sm px-5 py-2 disabled:opacity-40" @click="submit">Ya, ganti sekarang</button>
        </div>
      </div>
    </div>
  </div>
</template>

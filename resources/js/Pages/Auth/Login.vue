<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useForm } from '@inertiajs/vue3'
import GearRatio from '@/Components/Landing/GearRatio.vue'

const form = useForm({ email: '', password: '', remember: false })

const netIssue = ref('')
const checking = ref(false)
let controller = null

const OFFLINE_MSG = 'Tidak ada koneksi internet. Sambungkan Wi-Fi atau data seluler, lalu coba lagi.'

const markOffline = () => { netIssue.value = OFFLINE_MSG }
const clearOffline = () => {
  if (netIssue.value === OFFLINE_MSG) netIssue.value = ''
}

onMounted(() => {
  if (!navigator.onLine) markOffline()
  window.addEventListener('offline', markOffline)
  window.addEventListener('online', clearOffline)
})

onBeforeUnmount(() => {
  window.removeEventListener('offline', markOffline)
  window.removeEventListener('online', clearOffline)
  if (controller) controller.abort()
})

// Pastikan server benar-benar bisa dijangkau sebelum mengirim kredensial.
async function serverReachable() {
  controller = new AbortController()
  const timer = setTimeout(() => controller.abort(), 8000)
  try {
    const res = await fetch('/up', {
      method: 'GET',
      cache: 'no-store',
      headers: { Accept: 'text/plain' },
      signal: controller.signal,
    })
    return res.ok
  } catch {
    return false
  } finally {
    clearTimeout(timer)
  }
}

const submit = async () => {
  if (form.processing || checking.value) return
  netIssue.value = ''

  if (!navigator.onLine) {
    markOffline()
    return
  }

  checking.value = true
  const reachable = await serverReachable()
  checking.value = false

  if (!reachable) {
    netIssue.value = 'Server tidak dapat dihubungi. Periksa koneksi internet Anda, lalu coba lagi.'
    return
  }

  form.post('/login', { onFinish: () => form.reset('password') })
}
</script>

<template>
  <div class="min-h-screen grid-dark flex items-center justify-center p-4 py-10">
    <div class="w-full max-w-5xl grid lg:grid-cols-2 gap-10 items-center">
      <div class="hidden lg:block lp" style="background:transparent">
        <span class="badge-status mb-6">Panel Kendali</span>
        <h1 class="font-display font-extrabold text-white text-5xl leading-[1.05] tracking-[-1.5px] mb-4">Kelola Website<span class="block text-accent">Jurusan TSM.</span></h1>
        <p class="text-on-navy-muted mb-8 max-w-md">Teknik Sepeda Motor SMKS Islam 1 Kota Blitar — Jl. Musi No. 06 Kota Blitar.</p>
        <GearRatio />
      </div>

      <div class="w-full max-w-md mx-auto">
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-full border border-accent/45 mb-4">
            <span class="text-accent font-mono text-sm tracking-wider">TSM</span>
          </div>
          <div class="font-display text-white text-3xl font-bold">Panel Admin</div>
          <p class="font-mono text-xs tracking-wider uppercase text-on-navy-subtle mt-2">Teknik Sepeda Motor SMKS Islam 1 Blitar</p>
        </div>

        <div class="bg-white rounded-[24px] border border-accent/30 p-8">
          <span class="mono-label mb-2">MASUK</span>
          <h2 class="font-display text-2xl font-bold text-navy mb-6">Masuk ke Panel Admin</h2>

          <div
            v-if="netIssue"
            class="mb-5 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-left"
            role="alert"
            aria-live="polite"
          >
            <p class="flex items-start gap-2 text-sm text-amber-900">
              <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
              </svg>
              <span>{{ netIssue }}</span>
            </p>
            <button
              type="button"
              class="mt-2 text-xs font-semibold text-amber-900 underline underline-offset-2 hover:text-amber-700"
              @click="submit"
            >
              Coba lagi
            </button>
          </div>

          <form @submit.prevent="submit" class="space-y-5">
            <div>
              <label class="form-label">Alamat Email</label>
              <input
                v-model="form.email"
                type="email"
                placeholder="admin@tsm-blitar.sch.id"
                class="form-input"
                :class="{ 'border-red-400 ring-red-200': form.errors.email }"
                autocomplete="email"
                required
              />
              <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
            </div>

            <div>
              <label class="form-label">Password</label>
              <input
                v-model="form.password"
                type="password"
                placeholder="••••••••"
                class="form-input"
                :class="{ 'border-red-400 ring-red-200': form.errors.password }"
                autocomplete="current-password"
                required
              />
              <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
            </div>

            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-label">
                Ingat saya
              </label>
            </div>

            <button
              type="submit"
              :disabled="form.processing || checking"
              class="w-full btn-primary justify-center py-3 relative overflow-hidden group disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span class="relative z-10 flex items-center justify-center gap-2">
                <svg v-if="form.processing || checking" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span>{{ checking ? 'Memeriksa koneksi...' : form.processing ? 'Masuk...' : 'Masuk' }}</span>
              </span>
            </button>
          </form>

          <div class="mt-6 text-center">
            <a href="/" class="text-sm text-slate-500 hover:text-label transition-colors">← Kembali ke website</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import GearRatio from '@/Components/Landing/GearRatio.vue'
import StudentLogin from '@/Components/Superapp/StudentLogin.vue'

const props = defineProps({ mode: { type: String, default: 'cms' }, recaptchaKey: String })
const tab = ref('siswa')
const isApp = computed(() => props.mode === 'app')
const form = useForm({ 
  email: '', 
  password: '', 
  remember: false 
})

const submit = () => {
  form.post(isApp.value ? '/masuk' : '/login', {
    onFinish: () => form.reset('password')
  })
}
</script>

<template>
  <div class="min-h-screen grid-dark flex items-center justify-center p-4 py-10">
    <div class="w-full max-w-5xl grid lg:grid-cols-2 gap-10 items-center">
      <div class="hidden lg:block lp" style="background:transparent">
        <span class="badge-status mb-6">{{ isApp ? 'TSM Superapp' : 'Panel Kendali' }}</span>
        <h1 v-if="isApp" class="font-display font-extrabold text-white text-5xl leading-[1.05] tracking-[-1.5px] mb-4">Satu akun,<span class="block text-accent">semua layanan TSM.</span></h1>
        <h1 v-else class="font-display font-extrabold text-white text-5xl leading-[1.05] tracking-[-1.5px] mb-4">Kelola Website<span class="block text-accent">Jurusan TSM.</span></h1>
        <p v-if="isApp" class="text-on-navy-muted mb-8 max-w-md">LMS, PKL, UKK, dan Perpustakaan Digital untuk guru dan siswa Teknik Sepeda Motor.</p>
        <p v-if="!isApp" class="text-on-navy-muted mb-8 max-w-md">Teknik Sepeda Motor SMKS Islam 1 Kota Blitar — Jl. Musi No. 06 Kota Blitar.</p>
        <GearRatio />
      </div>
    <div class="w-full max-w-md mx-auto">

      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full border border-accent/45 mb-4">
          <span class="text-accent font-mono text-sm tracking-wider">TSM</span>
        </div>
        <div class="font-display text-white text-3xl font-bold">{{ isApp ? 'TSM Superapp' : 'Panel Admin' }}</div>
        <p class="font-mono text-xs tracking-wider uppercase text-on-navy-subtle mt-2">Teknik Sepeda Motor SMKS Islam 1 Blitar</p>
      </div>

      <div class="bg-white rounded-[24px] border border-accent/30 p-8">
        <span class="mono-label mb-2">MASUK</span>
        <h2 class="font-display text-2xl font-bold text-navy mb-6">{{ isApp ? 'Masuk TSM Superapp' : 'Masuk ke Panel Admin' }}</h2>

        <div v-if="isApp" class="grid grid-cols-2 gap-1 p-1 rounded-full bg-slate-100 mb-6 text-sm font-semibold" role="tablist">
          <button type="button" role="tab" :aria-selected="tab === 'siswa'" @click="tab = 'siswa'" :class="['py-2 rounded-full transition', tab === 'siswa' ? 'bg-navy text-white' : 'text-slate-500']">Siswa</button>
          <button type="button" role="tab" :aria-selected="tab === 'staf'" @click="tab = 'staf'" :class="['py-2 rounded-full transition', tab === 'staf' ? 'bg-navy text-white' : 'text-slate-500']">Guru / Staf</button>
        </div>
        <StudentLogin v-if="isApp && tab === 'siswa'" :recaptcha-key="recaptchaKey" />
        
        <!-- Login Form -->
        <form v-else @submit.prevent="submit" class="space-y-5">
          <!-- Email Field -->
          <div>
            <label class="form-label">Alamat Email</label>
            <input 
              v-model="form.email" 
              type="email" 
              :placeholder="isApp ? 'nama@tsm-blitar.sch.id' : 'admin@tsm-blitar.sch.id'" 
              class="form-input" 
              :class="{ 'border-red-400 ring-red-200': form.errors.email }"
              autocomplete="email"
              required
            />
            <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
          </div>

          <!-- Password Field -->
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

          <!-- Remember Checkbox -->
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
              <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-label">
              Ingat saya
            </label>
          </div>

          <!-- Submit Button -->
          <button 
            type="submit" 
            :disabled="form.processing"
            class="w-full btn-primary justify-center py-3 relative overflow-hidden group disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span class="relative z-10 flex items-center justify-center gap-2">
              <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <span>{{ form.processing ? 'Masuk...' : 'Masuk' }}</span>
            </span>
            
          </button>
        </form>

        <!-- Footer -->
        <div class="mt-6 text-center">
          <a href="/" class="text-sm text-slate-500 hover:text-label transition-colors">← Kembali ke website</a>
        </div>
      </div>

      <p v-if="isApp" class="mt-6 text-center text-on-navy-subtle text-xs">Siswa: masuk dengan NIS (atau scan kartu) + tanggal lahir. Akun guru dibuat oleh superadmin jurusan.</p>
    </div>
  </div>
  </div>
</template>

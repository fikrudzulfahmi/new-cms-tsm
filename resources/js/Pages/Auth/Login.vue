<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import GearAnimation from '@/Components/GearAnimation.vue'

const form = useForm({ 
  email: '', 
  password: '', 
  remember: false 
})

const submit = () => {
  form.post('/login', {
    onError: (errors) => {
      console.log('Login errors:', errors)
    }
  })
}
</script>

<template>
  <div class="min-h-screen gradient-navy flex flex-col items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Gear Animation Header -->
      <div class="mb-8">
        <GearAnimation />
      </div>

      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-white/10 rounded-2xl mb-4 shadow-lg">
          <span class="text-white font-bold text-2xl">TSM</span>
        </div>
        <h1 class="text-white text-3xl font-bold">Panel Admin</h1>
        <p class="text-blue-300 text-sm mt-2">Teknik Sepeda Motor SMKS Islam 1 Blitar</p>
      </div>

      <div class="bg-white rounded-2xl shadow-2xl p-8">
        <h2 class="text-xl font-bold text-slate-800 mb-6">Masuk ke Panel Admin</h2>
        
        <!-- Login Form -->
        <form @submit.prevent="submit" class="space-y-5">
          <!-- Email Field -->
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
              <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-blue-600">
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
            <span v-if="!form.processing" class="absolute inset-0 bg-blue-800 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300 -z-0"></span>
          </button>
        </form>

        <!-- Footer -->
        <div class="mt-6 text-center">
          <a href="/" class="text-sm text-slate-500 hover:text-blue-900 transition-colors">← Kembali ke website</a>
        </div>
      </div>

      <!-- Info Box -->
      <div class="mt-6 text-center text-blue-200 text-xs bg-white/5 rounded-lg p-4 backdrop-blur">
        <p><strong>Demo:</strong> admin@tsm-blitar.sch.id / password123</p>
      </div>
    </div>
  </div>
</template>

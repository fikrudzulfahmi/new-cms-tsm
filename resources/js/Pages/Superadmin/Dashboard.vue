<script setup>
import { Link } from '@inertiajs/vue3'
import SuperappLayout from '@/Layouts/SuperappLayout.vue'

const props = defineProps({ features: Object, stats: Object })
const featureList = Object.entries(props.features || {}).map(([key, f]) => ({ key, ...f }))
</script>

<template>
  <SuperappLayout variant="superadmin">
    <title>Superadmin - TSM Superapp</title>
    <div class="space-y-8">
      <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">TSM Superapp · Superadmin</span>Dashboard</h1>

      <div class="grid sm:grid-cols-3 gap-5">
        <div class="card p-5">
          <div class="text-xs text-slate-500">Akun Siswa</div>
          <div class="font-display text-3xl font-bold text-navy">{{ stats.siswa }}</div>
          <Link href="/superadmin/siswa" class="text-xs font-semibold text-label">Data Siswa →</Link>
        </div>
        <div class="card p-5">
          <div class="text-xs text-slate-500">Akun Guru</div>
          <div class="font-display text-3xl font-bold text-navy">{{ stats.guru }}</div>
          <Link href="/superadmin/guru" class="text-xs font-semibold text-label">Data Guru →</Link>
        </div>
        <div class="card p-5">
          <div class="text-xs text-slate-500">Kelola akun & plotting fitur</div>
          <div class="font-display text-lg font-bold text-navy mt-1">Pengguna & Akses</div>
          <Link href="/superadmin/pengguna" class="text-xs font-semibold text-label">Buka →</Link>
        </div>
      </div>

      <div>
        <h2 class="font-bold text-slate-800 mb-1">Fitur Superapp</h2>
        <p class="text-xs text-slate-500 mb-4">Semua fitur masih dalam pengembangan (Coming Soon). User di-plot ke fitur lewat menu Pengguna & Akses.</p>
        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-5">
          <Link v-for="f in featureList" :key="f.key" :href="`/superadmin/fitur/${f.key}`" class="card p-5 flex flex-col gap-3 hover:-translate-y-0.5 hover:shadow-lg transition">
            <div class="flex items-start justify-between">
              <div class="w-11 h-11 rounded-2xl bg-navy text-accent flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" :d="f.icon" /></svg>
              </div>
              <span class="font-mono text-[10px] tracking-wider uppercase px-2 py-1 rounded-full bg-amber-100 text-amber-700">Segera</span>
            </div>
            <div>
              <div class="font-display text-lg font-bold text-navy">{{ f.name }}</div>
              <div class="text-xs text-slate-500">{{ f.full }}</div>
            </div>
            <div class="text-xs text-slate-600 mt-auto">{{ stats.plots?.[f.key] || 0 }} pengguna di-plot</div>
          </Link>
        </div>
      </div>
    </div>
  </SuperappLayout>
</template>

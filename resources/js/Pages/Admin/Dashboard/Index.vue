<script setup>
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['stats', 'recentPosts', 'recentVisitors', 'recentComments'])

const cards = computed(() => [
  { label: 'Total Kunjungan', value: props.stats?.total_visits || 0, icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z', color: 'bg-blue-500' },
  { label: 'Jumlah Berita', value: props.stats?.total_posts || 0, icon: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z', color: 'bg-emerald-500' },
  { label: 'Prestasi', value: props.stats?.total_achievements || 0, icon: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', color: 'bg-amber-500' },
  { label: 'Pendaftaran PPDB', value: props.stats?.total_ppdb || 0, icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', color: 'bg-purple-500' },
])

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) : ''
</script>

<template>
  <AdminLayout>
    <title>Dashboard Admin - TSM SMKS Islam 1 Blitar</title>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
        <p class="text-slate-500 text-sm">Selamat datang kembali di panel administrasi TSM</p>
      </div>

      <!-- Stats Grid -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="c in cards" :key="c.label" class="card p-6 flex items-center gap-4">
          <div :class="['w-12 h-12 rounded-xl flex items-center justify-center text-white flex-shrink-0', c.color]">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="c.icon"/></svg>
          </div>
          <div>
            <div class="text-slate-500 text-xs font-medium">{{ c.label }}</div>
            <div class="text-2xl font-bold text-slate-800">{{ c.value }}</div>
          </div>
        </div>
      </div>

      <!-- Tables -->
      <div class="grid lg:grid-cols-2 gap-6">
        <!-- Recent Posts -->
        <div class="card p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-slate-800">Berita Terbaru</h2>
            <a href="/admin/berita" class="text-xs text-blue-700 font-semibold hover:underline">Kelola Berita →</a>
          </div>
          <div class="space-y-3">
            <div v-for="p in recentPosts" :key="p.id" class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
              <div class="min-w-0 pr-3">
                <div class="font-semibold text-sm text-slate-800 truncate">{{ p.title }}</div>
                <div class="text-xs text-slate-400 mt-0.5">{{ formatDate(p.published_at) }} · {{ p.views_count }} views</div>
              </div>
              <span :class="['badge text-xs', p.status==='published' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700']">{{ p.status }}</span>
            </div>
          </div>
        </div>

        <!-- Recent Comments -->
        <div class="card p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-slate-800">Komentar Terbaru</h2>
            <a href="/admin/komentar" class="text-xs text-blue-700 font-semibold hover:underline">Kelola Komentar →</a>
          </div>
          <div class="space-y-3">
            <div v-for="c in recentComments" :key="c.id" class="p-3 rounded-xl bg-slate-50">
              <div class="flex items-center justify-between mb-1">
                <div class="font-semibold text-xs text-slate-800">{{ c.name }}</div>
                <div class="text-xs text-slate-400">{{ formatDate(c.created_at) }}</div>
              </div>
              <p class="text-slate-600 text-xs line-clamp-2">{{ c.body }}</p>
            </div>
            <div v-if="!recentComments?.length" class="text-slate-400 text-xs py-4 text-center">Belum ada komentar</div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

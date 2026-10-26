<script setup>
import { Link } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
defineProps({ courses: Array })
</script>

<template>
  <LmsShell title="Mapel Saya">
    <p v-if="!courses.length" class="text-slate-500 text-sm bg-white rounded-2xl border p-6">Belum ada mapel yang ditugaskan. Minta superadmin menambahkannya.</p>
    <div class="grid sm:grid-cols-2 gap-4">
      <Link v-for="c in courses" :key="c.id" :href="`/ruang-guru/mapel/${c.id}`" class="block bg-white rounded-2xl border border-slate-200 p-5 hover:border-navy transition">
        <div class="font-semibold text-navy">{{ c.title }}</div>
        <div class="text-xs text-slate-500 mt-1">{{ c.year }} &middot; {{ c.classes.join(', ') }}</div>
        <span v-if="!c.is_active" class="inline-block mt-2 text-xs bg-slate-100 text-slate-500 px-2 py-0.5 rounded">Nonaktif</span>
      </Link>
    </div>
  </LmsShell>
</template>

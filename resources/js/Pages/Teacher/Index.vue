<script setup>
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHero from '@/Components/Shared/PageHero.vue'
import TeacherCard from '@/Components/Shared/TeacherCard.vue'

const props = defineProps({ teachers: Array })
const q = ref('')
const list = computed(() => {
  const t = q.value.trim().toLowerCase()
  if (!t) return props.teachers
  return props.teachers.filter((x) => [x.name, x.position, x.subject].some((v) => (v || '').toLowerCase().includes(t)))
})
</script>

<template>
  <AppLayout>
    <Head><title>Guru &amp; Instruktur</title></Head>
    <PageHero eyebrow="Data Guru" title="Guru & Instruktur" subtitle="Tenaga pendidik dan instruktur yang membimbing siswa di kelas dan bengkel praktik." :crumbs="[{ label: 'Profil', href: '/profil' }, { label: 'Guru' }]" />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <p class="text-slate-500 text-sm">{{ list.length }} dari {{ teachers.length }} guru dan instruktur</p>
        <input v-model="q" type="search" aria-label="Cari guru" placeholder="Cari nama, jabatan, atau mapel…" class="form-input text-sm w-full sm:w-80" />
      </div>
      <div v-if="list.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
        <TeacherCard v-for="t in list" :key="t.id" :teacher="t">
          <p v-if="t.education" class="text-xs text-slate-400 mt-2">{{ t.education }}</p>
        </TeacherCard>
      </div>
      <p v-else class="text-center text-slate-500 py-16">Tidak ada guru yang cocok dengan pencarian.</p>
    </div>
  </AppLayout>
</template>

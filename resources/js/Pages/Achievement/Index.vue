<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHero from '@/Components/Shared/PageHero.vue'
import { img, hideImg } from '@/lib/site'

defineProps({ achievements: Object, levels: Array, filters: Object })
const levelHref = (l) => (l ? `/prestasi?tingkat=${encodeURIComponent(l)}` : '/prestasi')
const chip = (on) => ['px-4 py-2 rounded-full text-sm font-semibold transition', on ? 'bg-navy text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50']
</script>

<template>
  <AppLayout>
    <Head><title>Prestasi</title></Head>
    <PageHero eyebrow="Prestasi" title="Bukti kompetensi di ajang lomba" :subtitle="`${achievements.total} prestasi tercatat, dari tingkat sekolah hingga nasional.`" :crumbs="[{ label: 'Prestasi' }]" />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div v-if="levels.length > 1" class="flex flex-wrap gap-2 mb-8" aria-label="Filter tingkat">
        <Link :href="levelHref('')" preserve-scroll :class="chip(!filters.tingkat)">Semua</Link>
        <Link v-for="l in levels" :key="l" :href="levelHref(l)" preserve-scroll :class="chip(filters.tingkat === l)">{{ l }}</Link>
      </div>
      <div v-if="achievements.data.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <article v-for="a in achievements.data" :key="a.id" class="card overflow-hidden flex flex-col">
          <div class="relative h-52 bg-navy overflow-hidden">
            <img v-if="a.image" :src="img(a.image)" :alt="a.title" loading="lazy" class="w-full h-full object-cover" @error="hideImg" />
            <span v-if="a.rank" class="absolute top-3 left-3 bg-accent text-navy font-bold text-sm px-3 py-1 rounded-full">{{ a.rank }}</span>
            <span v-if="a.is_featured" class="absolute top-3 right-3 bg-white/90 text-navy font-mono text-[10px] tracking-wider uppercase px-2 py-1 rounded-full">Unggulan</span>
          </div>
          <div class="p-6 flex-1">
            <span class="font-mono text-[11px] tracking-wider uppercase text-label">{{ a.level || 'Umum' }}<template v-if="a.year"> · {{ a.year }}</template></span>
            <h2 class="font-display font-bold text-lg text-navy mt-1 mb-2 leading-snug">{{ a.title }}</h2>
            <p class="text-sm text-slate-600">{{ a.student_name }}</p>
            <p v-if="a.organizer" class="text-xs text-slate-400 mt-1">Penyelenggara: {{ a.organizer }}</p>
            <p v-if="a.description" class="text-sm text-slate-500 mt-3 leading-relaxed line-clamp-3">{{ a.description }}</p>
          </div>
        </article>
      </div>
      <p v-else class="text-center text-slate-500 py-16">Belum ada prestasi pada tingkat ini.</p>
      <nav v-if="achievements.last_page > 1" class="flex justify-center flex-wrap gap-1 mt-10" aria-label="Halaman">
        <component :is="l.url ? Link : 'span'" v-for="(l, i) in achievements.links" :key="i" :href="l.url" v-html="l.label"
          :class="['px-4 py-2 rounded-full text-sm font-semibold border font-mono', l.active ? 'bg-navy border-navy text-white' : l.url ? 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' : 'bg-slate-100 border-slate-200 text-slate-400']" />
      </nav>
    </div>
  </AppLayout>
</template>

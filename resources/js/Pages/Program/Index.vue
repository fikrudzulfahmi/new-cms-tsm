<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHero from '@/Components/Shared/PageHero.vue'
import { img, hideImg, iconFor } from '@/lib/site'

defineProps({ programs: Array })
</script>

<template>
  <AppLayout>
    <Head><title>Program Unggulan</title></Head>
    <PageHero eyebrow="Program Unggulan" title="Program yang membedakan TSM" subtitle="Kegiatan pendukung agar siswa siap kerja, bersertifikat, dan mampu berwirausaha." :crumbs="[{ label: 'Program' }]" />
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
      <article v-for="(p, i) in programs" :key="p.id" class="card overflow-hidden grid md:grid-cols-2">
        <div class="bg-ice min-h-56 flex items-center justify-center" :class="{ 'md:order-2': i % 2 }">
          <img v-if="p.image" :src="img(p.image)" :alt="p.title" loading="lazy" class="w-full h-full object-cover" @error="hideImg" />
          <div v-else class="icon-box !w-20 !h-20 !rounded-3xl">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="iconFor(p.icon)" /></svg>
          </div>
        </div>
        <div class="p-8 flex flex-col justify-center">
          <span class="font-mono text-xs tracking-[2px] uppercase text-label">{{ String(i + 1).padStart(2, '0') }}<template v-if="p.subtitle"> · {{ p.subtitle }}</template></span>
          <h2 class="font-display font-bold text-2xl text-navy mt-2 mb-3">{{ p.title }}</h2>
          <p class="text-slate-600 leading-relaxed whitespace-pre-line">{{ p.description }}</p>
        </div>
      </article>
      <p v-if="!programs.length" class="text-center text-slate-500 py-16">Belum ada data program.</p>
    </div>
  </AppLayout>
</template>

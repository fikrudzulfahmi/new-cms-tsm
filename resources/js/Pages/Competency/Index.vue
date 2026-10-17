<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHero from '@/Components/Shared/PageHero.vue'
import { img, hideImg, iconFor } from '@/lib/site'

defineProps({ competencies: Array })
</script>

<template>
  <AppLayout>
    <Head><title>Kompetensi Keahlian</title></Head>
    <PageHero eyebrow="Kompetensi Keahlian" title="Apa saja yang dipelajari di TSM?" subtitle="Materi disusun bersama mitra industri, dengan porsi praktik yang besar di bengkel sekolah." :crumbs="[{ label: 'Kompetensi' }]" />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div v-if="competencies.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <Link v-for="k in competencies" :key="k.id" :href="`/kompetensi/${k.slug}`" class="card overflow-hidden group hover:border-accent hover:-translate-y-1 transition-all flex flex-col">
          <div v-if="k.image" class="h-44 overflow-hidden bg-ice">
            <img :src="img(k.image)" :alt="k.title" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" @error="hideImg" />
          </div>
          <div class="p-6 flex-1 flex flex-col">
            <div class="icon-box mb-4">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="iconFor(k.icon)" /></svg>
            </div>
            <h2 class="font-display font-bold text-xl text-navy mb-2 group-hover:text-label">{{ k.title }}</h2>
            <p class="text-slate-500 text-sm leading-relaxed flex-1">{{ k.short_description }}</p>
            <span class="text-sm font-semibold text-label mt-4">Selengkapnya →</span>
          </div>
        </Link>
      </div>
      <p v-else class="text-center text-slate-500 py-16">Belum ada data kompetensi.</p>
    </div>
  </AppLayout>
</template>

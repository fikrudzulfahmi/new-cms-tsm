<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHero from '@/Components/Shared/PageHero.vue'
import { img, hideImg, iconFor, paragraphs } from '@/lib/site'

const props = defineProps({ competency: Object, others: Array })
const body = computed(() => paragraphs(props.competency.description || props.competency.short_description))
</script>

<template>
  <AppLayout>
    <Head><title>{{ competency.title }} — Kompetensi</title></Head>
    <PageHero eyebrow="Kompetensi Keahlian" :title="competency.title" :subtitle="competency.short_description" :crumbs="[{ label: 'Kompetensi', href: '/kompetensi' }, { label: competency.title }]" />
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid lg:grid-cols-[1fr_300px] gap-10">
      <article>
        <img v-if="competency.image" :src="img(competency.image)" :alt="competency.title" class="w-full rounded-3xl mb-8 aspect-video object-cover" @error="hideImg" />
        <div class="space-y-4 text-slate-600 leading-relaxed text-[17px]">
          <p v-for="(p, i) in body" :key="i" class="whitespace-pre-line">{{ p }}</p>
        </div>
        <Link href="/kompetensi" class="inline-block mt-10 text-sm font-semibold text-label hover:underline">← Semua kompetensi</Link>
      </article>
      <aside v-if="others.length">
        <h2 class="font-mono text-xs tracking-[2px] uppercase text-label mb-4">Kompetensi lain</h2>
        <div class="space-y-3">
          <Link v-for="o in others" :key="o.id" :href="`/kompetensi/${o.slug}`" class="card p-4 flex gap-3 items-start hover:border-accent transition">
            <div class="icon-box !w-10 !h-10 !rounded-xl">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="iconFor(o.icon)" /></svg>
            </div>
            <div class="min-w-0">
              <b class="block text-navy text-sm">{{ o.title }}</b>
              <span class="text-xs text-slate-500 line-clamp-2">{{ o.short_description }}</span>
            </div>
          </Link>
        </div>
      </aside>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps(['posts', 'categories', 'filters'])
const search = ref(props.filters?.search || '')
const category = ref(props.filters?.category || '')

const filter = () => {
  router.get('/berita', { search: search.value, category: category.value }, { preserveState: true, replace: true })
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' }) : ''
</script>

<template>
  <AppLayout>
    <Head><title>Berita &amp; Artikel — TSM</title></Head>
    <title>Berita & Artikel - TSM SMKS Islam 1 Blitar</title>
    <div class="grid-dark page-hero border-b border-accent/20">
      <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <span class="badge-status mb-6">Kabar Jurusan</span>
        <h1 class="font-display font-extrabold text-white mb-4 max-w-3xl">Berita & Pengumuman</h1>
        <p class="text-lg max-w-2xl">Informasi terbaru seputar kegiatan Teknik Sepeda Motor SMKS Islam 1 Blitar</p>
      </div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="flex flex-col md:flex-row gap-4 mb-8 justify-between">
        <div class="flex flex-wrap gap-2">
          <button @click="category=''; filter()" :class="['px-4 py-2 rounded-full text-sm font-semibold transition', !category ? 'bg-navy text-white' : 'bg-white border border-line text-slate-600 hover:bg-slate-50']">Semua</button>
          <button v-for="c in categories" :key="c.id" @click="category=c.slug; filter()" :class="['px-4 py-2 rounded-full text-sm font-semibold transition', category===c.slug ? 'bg-navy text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50']">{{ c.name }}</button>
        </div>
        <form @submit.prevent="filter" class="flex gap-2">
          <input v-model="search" type="text" placeholder="Cari berita..." class="form-input text-sm" />
          <button type="submit" class="btn-primary text-sm px-4">Cari</button>
        </form>
      </div>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <a v-for="post in posts.data" :key="post.id" :href="`/berita/${post.slug}`" class="card hover:border-accent transition-all hover:-translate-y-1 block group">
          <div class="h-48 overflow-hidden">
            <img v-if="post.cover_image" :src="`/storage/${post.cover_image}`" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" :alt="post.title" />
            <div v-else class="w-full h-full grid-light flex items-center justify-center text-slate-300">
              <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
            </div>
          </div>
          <div class="p-5">
            <div v-if="post.category" class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold mb-2" :style="`background:${post.category.color}20;color:${post.category.color}`">{{ post.category.name }}</div>
            <h2 class="font-display font-bold text-navy mb-2 group-hover:text-label transition-colors line-clamp-2">{{ post.title }}</h2>
            <p class="text-slate-500 text-sm line-clamp-2 mb-4">{{ post.excerpt }}</p>
            <div class="flex items-center justify-between text-xs text-slate-400">
              <span>{{ formatDate(post.published_at) }}</span>
              <div class="flex items-center gap-3">
                <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>{{ post.views_count }}</span>
                <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>{{ post.comments_count }}</span>
              </div>
            </div>
          </div>
        </a>
      </div>
      <div v-if="!posts.data?.length" class="text-center py-16 text-slate-400">Tidak ada berita ditemukan</div>
      <!-- Pagination -->
      <div v-if="posts.links?.length > 3" class="flex justify-center gap-1 mt-10">
        <component :is="link.url ? 'a' : 'span'" v-for="(link, i) in posts.links" :key="i" :href="link.url" v-html="link.label" :class="['px-4 py-2 rounded-full text-sm font-semibold border font-mono', link.active ? 'bg-blue-900 border-blue-900 text-white' : link.url ? 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' : 'bg-slate-100 border-slate-200 text-slate-400 cursor-not-allowed']" />
      </div>
    </div>
  </AppLayout>
</template>

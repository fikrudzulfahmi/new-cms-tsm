<script setup>
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHero from '@/Components/Shared/PageHero.vue'
import { img, hideImg } from '@/lib/site'

const props = defineProps({ facilities: Array })
const cats = computed(() => ['Semua', ...new Set(props.facilities.map((f) => f.category).filter(Boolean))])
const cat = ref('Semua')
const list = computed(() => (cat.value === 'Semua' ? props.facilities : props.facilities.filter((f) => f.category === cat.value)))
</script>

<template>
  <AppLayout>
    <Head><title>Sarana &amp; Prasarana</title></Head>
    <PageHero eyebrow="Sarana & Prasarana" title="Bengkel praktik berstandar industri" subtitle="Fasilitas yang dipakai siswa untuk praktik harian, uji kompetensi, dan layanan servis." :crumbs="[{ label: 'Sarana' }]" />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div v-if="cats.length > 2" class="flex flex-wrap gap-2 mb-8" role="tablist" aria-label="Kategori sarana">
        <button v-for="c in cats" :key="c" type="button" role="tab" :aria-selected="cat === c" @click="cat = c"
          :class="['px-4 py-2 rounded-full text-sm font-semibold transition', cat === c ? 'bg-navy text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50']">{{ c }}</button>
      </div>
      <div v-if="list.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <article v-for="f in list" :key="f.id" class="card overflow-hidden flex flex-col">
          <div class="h-52 bg-ice overflow-hidden">
            <img v-if="f.image" :src="img(f.image)" :alt="f.name" loading="lazy" class="w-full h-full object-cover" @error="hideImg" />
          </div>
          <div class="p-6 flex-1">
            <span v-if="f.category" class="font-mono text-[11px] tracking-wider uppercase text-label">{{ f.category }}</span>
            <h2 class="font-display font-bold text-lg text-navy mt-1 mb-2">{{ f.name }}</h2>
            <p v-if="f.description" class="text-sm text-slate-500 leading-relaxed">{{ f.description }}</p>
            <p v-if="f.quantity || f.condition" class="text-xs text-slate-400 mt-3 font-mono">
              <template v-if="f.quantity">{{ f.quantity }} unit</template><template v-if="f.quantity && f.condition"> · </template>{{ f.condition }}
            </p>
          </div>
        </article>
      </div>
      <p v-else class="text-center text-slate-500 py-16">Belum ada data sarana.</p>
    </div>
  </AppLayout>
</template>

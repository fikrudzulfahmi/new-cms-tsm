<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHero from '@/Components/Shared/PageHero.vue'
import TeacherCard from '@/Components/Shared/TeacherCard.vue'
import { img, hideImg, initials, paragraphs } from '@/lib/site'

const props = defineProps({ head: Object, mission: Array, teachers: Array, teacherCount: Number })
const s = computed(() => usePage().props.settings || {})
const dept = computed(() => s.value.department_name || 'Teknik Sepeda Motor')
const headName = computed(() => s.value.profile_head_name || props.head?.name || '')
const headPosition = computed(() => s.value.profile_head_position || props.head?.position || '')
const headPhoto = computed(() => s.value.profile_head_photo || props.head?.photo || '')
const speech = computed(() => paragraphs(s.value.profile_speech))
const history = computed(() => paragraphs(s.value.profile_history || s.value.about_text))

const toc = computed(() => [
  speech.value.length && { id: 'sambutan', label: 'Sambutan' },
  history.value.length && { id: 'sejarah', label: 'Sejarah' },
  (s.value.profile_vision || props.mission?.length) && { id: 'visi-misi', label: 'Visi & Misi' },
  s.value.profile_structure_image && { id: 'struktur', label: 'Struktur Organisasi' },
  props.teachers?.length && { id: 'guru', label: 'Guru & Instruktur' },
].filter(Boolean))
</script>

<template>
  <AppLayout>
    <Head><title>Profil Jurusan</title></Head>
    <PageHero eyebrow="Profil Jurusan" :title="dept" :subtitle="s.school_name ? `Program keahlian di ${s.school_name}` : ''" :crumbs="[{ label: 'Profil' }]">
      <nav v-if="toc.length > 1" aria-label="Isi halaman" class="flex flex-wrap gap-2 mt-8">
        <a v-for="t in toc" :key="t.id" :href="`#${t.id}`" class="px-4 py-2 rounded-full border border-accent/30 text-sm font-semibold text-on-navy-muted hover:text-white hover:border-accent transition">{{ t.label }}</a>
      </nav>
    </PageHero>

    <section v-if="speech.length" id="sambutan" class="scroll-mt-28 max-w-6xl mx-auto px-6 lg:px-8 py-16 grid md:grid-cols-[280px_1fr] gap-10 items-start">
      <figure class="text-center">
        <div class="aspect-[4/5] rounded-3xl overflow-hidden bg-ice flex items-center justify-center">
          <img v-if="headPhoto" :src="img(headPhoto)" :alt="headName" class="w-full h-full object-cover" @error="hideImg" />
          <span v-else class="font-display font-bold text-6xl text-label" aria-hidden="true">{{ initials(headName) }}</span>
        </div>
        <figcaption v-if="headName" class="mt-4">
          <b class="block font-display text-navy">{{ headName }}</b>
          <small class="font-mono text-[11px] tracking-wider uppercase text-label">{{ headPosition }}</small>
        </figcaption>
      </figure>
      <div>
        <span class="mono-label">SAMBUTAN</span>
        <h2 class="section-title mt-2 mb-6">Selamat datang di {{ dept }}</h2>
        <div class="space-y-4 text-slate-600 leading-relaxed text-[17px]">
          <p v-for="(p, i) in speech" :key="i" class="whitespace-pre-line">{{ p }}</p>
        </div>
      </div>
    </section>

    <section v-if="history.length" id="sejarah" class="scroll-mt-28 bg-ice-section border-y border-line">
      <div class="max-w-4xl mx-auto px-6 lg:px-8 py-16">
        <span class="mono-label">SEJARAH</span>
        <h2 class="section-title mt-2 mb-6">Perjalanan jurusan</h2>
        <div class="space-y-4 text-slate-600 leading-relaxed text-[17px]">
          <p v-for="(p, i) in history" :key="i" class="whitespace-pre-line">{{ p }}</p>
        </div>
      </div>
    </section>

    <section v-if="s.profile_vision || mission?.length" id="visi-misi" class="scroll-mt-28 max-w-6xl mx-auto px-6 lg:px-8 py-16">
      <span class="mono-label">VISI &amp; MISI</span>
      <h2 class="section-title mt-2 mb-8">Arah dan tujuan kami</h2>
      <div class="grid md:grid-cols-2 gap-6">
        <div v-if="s.profile_vision" class="rounded-3xl bg-navy text-white p-8">
          <span class="font-mono text-xs tracking-[2px] uppercase text-accent">Visi</span>
          <p class="font-display text-xl leading-relaxed mt-4">{{ s.profile_vision }}</p>
        </div>
        <div v-if="mission?.length" class="card p-8">
          <span class="font-mono text-xs tracking-[2px] uppercase text-label">Misi</span>
          <ol class="mt-4 space-y-3">
            <li v-for="(m, i) in mission" :key="i" class="flex gap-3 text-slate-700">
              <span class="font-mono text-sm text-label flex-shrink-0 w-6">{{ String(i + 1).padStart(2, '0') }}</span>
              <span>{{ m }}</span>
            </li>
          </ol>
        </div>
      </div>
    </section>

    <section v-if="s.profile_structure_image" id="struktur" class="scroll-mt-28 bg-ice-section border-y border-line">
      <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16">
        <span class="mono-label">STRUKTUR ORGANISASI</span>
        <h2 class="section-title mt-2 mb-8">Struktur organisasi jurusan</h2>
        <a :href="img(s.profile_structure_image)" target="_blank" rel="noopener" class="block card p-4">
          <img :src="img(s.profile_structure_image)" alt="Bagan struktur organisasi jurusan" class="w-full h-auto rounded-2xl" loading="lazy" @error="hideImg" />
        </a>
        <p class="text-sm text-slate-500 mt-3">Klik gambar untuk memperbesar.</p>
      </div>
    </section>

    <section v-if="teachers?.length" id="guru" class="scroll-mt-28 max-w-7xl mx-auto px-6 lg:px-8 py-16">
      <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
        <div>
          <span class="mono-label">GURU &amp; INSTRUKTUR</span>
          <h2 class="section-title mt-2">Dibimbing praktisi berpengalaman</h2>
        </div>
        <Link href="/guru" class="btn-primary text-sm px-5">Lihat semua ({{ teacherCount }}) →</Link>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
        <TeacherCard v-for="t in teachers" :key="t.id" :teacher="t" />
      </div>
    </section>
  </AppLayout>
</template>

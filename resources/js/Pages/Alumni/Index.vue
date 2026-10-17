<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHero from '@/Components/Shared/PageHero.vue'
import { img, hideImg, initials } from '@/lib/site'

defineProps({ testimonials: Object })
const page = usePage()
const show = ref(false)
const form = useForm({ name: '', graduation_year: '', occupation: '', workplace: '', message: '', rating: 5, website: '' })
const submit = () => form.post('/testimoni', { preserveScroll: true, onSuccess: () => { form.reset(); show.value = false } })
</script>

<template>
  <AppLayout>
    <Head><title>Umpan Balik Alumni</title></Head>
    <PageHero eyebrow="Umpan Balik Lulusan" title="Kata alumni tentang TSM" subtitle="Cerita lulusan yang kini bekerja, berwirausaha, atau melanjutkan studi." :crumbs="[{ label: 'Alumni' }]">
      <button type="button" class="mt-6 inline-flex items-center h-11 px-6 rounded-full bg-accent text-navy text-sm font-bold hover:brightness-105 transition" @click="show = !show">{{ show ? 'Tutup formulir' : 'Kirim testimoni' }}</button>
    </PageHero>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <p v-if="page.props.flash?.success && !show" class="mb-6 rounded-2xl bg-green-50 border border-green-200 text-green-800 px-5 py-3 text-sm" role="status">{{ page.props.flash.success }}</p>

      <form v-if="show" class="card p-6 mb-10 grid sm:grid-cols-2 gap-4" @submit.prevent="submit">
        <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="hidden" />
        <label class="text-sm font-semibold text-slate-700">Nama lengkap<input v-model="form.name" required maxlength="150" class="form-input mt-1" /></label>
        <label class="text-sm font-semibold text-slate-700">Tahun lulus<input v-model="form.graduation_year" type="number" min="2000" :max="new Date().getFullYear()" required class="form-input mt-1" /></label>
        <label class="text-sm font-semibold text-slate-700">Pekerjaan<input v-model="form.occupation" maxlength="150" class="form-input mt-1" /></label>
        <label class="text-sm font-semibold text-slate-700">Tempat kerja / kuliah<input v-model="form.workplace" maxlength="150" class="form-input mt-1" /></label>
        <label class="text-sm font-semibold text-slate-700 sm:col-span-2">Pesan dan kesan<textarea v-model="form.message" rows="4" required maxlength="1000" class="form-input mt-1"></textarea></label>
        <div class="sm:col-span-2 flex items-center gap-1">
          <span class="text-sm text-slate-600 mr-2">Penilaian:</span>
          <button v-for="n in 5" :key="n" type="button" :aria-label="`${n} bintang`" class="text-2xl" :class="n <= form.rating ? 'text-amber-400' : 'text-slate-300'" @click="form.rating = n">★</button>
        </div>
        <p v-for="(e, k) in form.errors" :key="k" class="sm:col-span-2 text-sm text-red-600">{{ e }}</p>
        <div class="sm:col-span-2"><button type="submit" class="btn-primary px-6" :disabled="form.processing">Kirim testimoni</button></div>
        <p class="sm:col-span-2 text-xs text-slate-400">Testimoni tampil setelah diperiksa admin.</p>
      </form>

      <div v-if="testimonials.data.length" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <figure v-for="t in testimonials.data" :key="t.id" class="card p-6 flex flex-col">
          <div class="text-amber-400 text-lg" :aria-label="`${t.rating} dari 5 bintang`">
            <span v-for="n in 5" :key="n" :class="{ 'text-slate-200': n > t.rating }">★</span>
          </div>
          <blockquote class="text-slate-600 leading-relaxed mt-3 flex-1">“{{ t.message }}”</blockquote>
          <figcaption class="flex items-center gap-3 mt-5 pt-5 border-t border-line">
            <div class="w-12 h-12 rounded-full overflow-hidden bg-ice flex items-center justify-center flex-shrink-0">
              <img v-if="t.photo" :src="img(t.photo)" :alt="t.name" class="w-full h-full object-cover" loading="lazy" @error="hideImg" />
              <span v-else class="font-bold text-label" aria-hidden="true">{{ initials(t.name) }}</span>
            </div>
            <div class="min-w-0">
              <b class="block text-navy">{{ t.name }}</b>
              <small class="text-slate-500">{{ [t.occupation, t.workplace, t.graduation_year ? `Lulus ${t.graduation_year}` : ''].filter(Boolean).join(' · ') }}</small>
            </div>
          </figcaption>
        </figure>
      </div>
      <p v-else class="text-center text-slate-500 py-16">Belum ada testimoni alumni.</p>
      <nav v-if="testimonials.last_page > 1" class="flex justify-center flex-wrap gap-1 mt-10" aria-label="Halaman">
        <component :is="l.url ? Link : 'span'" v-for="(l, i) in testimonials.links" :key="i" :href="l.url" v-html="l.label"
          :class="['px-4 py-2 rounded-full text-sm font-semibold border font-mono', l.active ? 'bg-navy border-navy text-white' : l.url ? 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' : 'bg-slate-100 border-slate-200 text-slate-400']" />
      </nav>
    </div>
  </AppLayout>
</template>

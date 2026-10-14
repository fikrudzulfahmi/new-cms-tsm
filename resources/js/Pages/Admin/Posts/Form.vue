<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['post', 'categories'])
const isEdit = !!props.post

const form = useForm({
  title: props.post?.title || '',
  category_id: props.post?.category_id || props.categories?.[0]?.id || '',
  body: props.post?.body || '',
  excerpt: props.post?.excerpt || '',
  meta_title: props.post?.meta_title || '',
  meta_description: props.post?.meta_description || '',
  focus_keyword: props.post?.focus_keyword || '',
  status: props.post?.status || 'published',
  cover_image: null,
  cover_alt: props.post?.cover_alt || '',
})

// Strict SEO Analyzer computation
const seoAnalysis = computed(() => {
  let score = 0
  let checks = []
  
  const title = form.title || ''
  const body = form.body || ''
  const desc = form.meta_description || ''
  const kw = (form.focus_keyword || '').toLowerCase().trim()
  
  // 1. Focus keyword present
  if (kw) {
    if (title.toLowerCase().includes(kw)) {
      score += 25
      checks.push({ pass: true, text: 'Kata kunci fokus ditemukan di Judul (+25)' })
    } else {
      checks.push({ pass: false, text: 'Kata kunci fokus TIDAK ditemukan di Judul (0/25)' })
    }
    
    if (body.toLowerCase().includes(kw)) {
      score += 25
      checks.push({ pass: true, text: 'Kata kunci fokus ditemukan di Konten (+25)' })
    } else {
      checks.push({ pass: false, text: 'Kata kunci fokus TIDAK ditemukan di Konten (0/25)' })
    }
  } else {
    checks.push({ pass: false, text: 'Masukkan Kata Kunci Fokus untuk analisis SEO' })
  }
  
  // 2. Title length (50-60 chars)
  if (title.length >= 40 && title.length <= 70) {
    score += 20
    checks.push({ pass: true, text: `Panjang Judul optimal (${title.length} karakter) (+20)` })
  } else {
    checks.push({ pass: false, text: `Panjang Judul kurang optimal (${title.length} karakter, ideal 40-70)` })
  }
  
  // 3. Meta description length (120-160 chars)
  if (desc.length >= 100 && desc.length <= 165) {
    score += 15
    checks.push({ pass: true, text: `Meta Deskripsi optimal (${desc.length} karakter) (+15)` })
  } else {
    checks.push({ pass: false, text: `Meta Deskripsi kurang optimal (${desc.length} karakter, ideal 100-165)` })
  }
  
  // 4. Content length (> 300 words)
  const wordCount = body.replace(/<[^>]*>/g, '').split(/\s+/).filter(Boolean).length
  if (wordCount >= 200) {
    score += 15
    checks.push({ pass: true, text: `Panjang Konten baik (${wordCount} kata) (+15)` })
  } else {
    checks.push({ pass: false, text: `Konten terlalu pendek (${wordCount} kata, minimal 200 kata)` })
  }
  
  return { score, checks, wordCount }
})

const submit = () => {
  if (isEdit) {
    form.post(`/admin/berita/${props.post.id}`, { _method: 'put' })
  } else {
    form.post('/admin/berita')
  }
}
</script>

<template>
  <AdminLayout>
    <title>{{ isEdit ? 'Edit Berita' : 'Tambah Berita' }} - Admin TSM</title>
    <div class="max-w-5xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">{{ isEdit ? 'Edit Berita' : 'Tambah Berita Baru' }}</h1>
          <p class="text-slate-500 text-sm">Gunakan editor di bawah dan perhatikan analisis SEO</p>
        </div>
        <a href="/admin/berita" class="text-sm text-slate-600 hover:text-blue-900">← Kembali</a>
      </div>

      <form @submit.prevent="submit" class="grid lg:grid-cols-3 gap-6">
        <!-- Left: Main inputs -->
        <div class="lg:col-span-2 space-y-5">
          <div class="card p-6 space-y-4">
            <div>
              <label class="form-label">Judul Berita *</label>
              <input v-model="form.title" type="text" placeholder="Masukkan judul berita..." class="form-input" />
              <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="form-label">Kategori *</label>
                <select v-model="form.category_id" class="form-input">
                  <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>
              <div>
                <label class="form-label">Status Publikasi *</label>
                <select v-model="form.status" class="form-input">
                  <option value="published">Published (Tayang)</option>
                  <option value="draft">Draft</option>
                </select>
              </div>
            </div>

            <div>
              <label class="form-label">Ringkasan (Excerpt) *</label>
              <textarea v-model="form.excerpt" rows="2" placeholder="Ringkasan singkat berita..." class="form-input resize-none"></textarea>
              <p v-if="form.errors.excerpt" class="text-red-500 text-xs mt-1">{{ form.errors.excerpt }}</p>
            </div>

            <div>
              <label class="form-label">Konten Berita (Rich Text / HTML) *</label>
              <textarea v-model="form.body" rows="12" placeholder="Tulis isi berita lengkap di sini (mendukung tag HTML & formatting)..." class="form-input font-mono text-sm"></textarea>
              <p class="text-xs text-slate-400 mt-1">Jumlah kata: {{ seoAnalysis.wordCount }} kata</p>
              <p v-if="form.errors.body" class="text-red-500 text-xs mt-1">{{ form.errors.body }}</p>
            </div>

            <div>
              <label class="form-label">Gambar Utama (Cover)</label>
              <input type="file" @input="form.cover_image = $event.target.files[0]" class="form-input text-sm file:mr-4 file:py-1 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100" />
            </div>
          </div>
        </div>

        <!-- Right: SEO Analysis & Meta -->
        <div class="space-y-6">
          <!-- SEO Checker Card -->
          <div class="card p-6 border-2" :class="seoAnalysis.score >= 80 ? 'border-green-300 bg-green-50/30' : seoAnalysis.score >= 50 ? 'border-amber-300 bg-amber-50/30' : 'border-red-300 bg-red-50/30'">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-bold text-slate-800">Analisis SEO Ketat</h3>
              <div :class="['text-xl font-extrabold', seoAnalysis.score >= 80 ? 'text-green-600' : seoAnalysis.score >= 50 ? 'text-amber-600' : 'text-red-600']">{{ seoAnalysis.score }}/100</div>
            </div>
            <div class="space-y-2 mb-4">
              <div v-for="(chk, i) in seoAnalysis.checks" :key="i" class="flex items-start gap-2 text-xs">
                <span :class="chk.pass ? 'text-green-600' : 'text-red-500'">{{ chk.pass ? '✓' : '✕' }}</span>
                <span :class="chk.pass ? 'text-slate-700' : 'text-slate-500'">{{ chk.text }}</span>
              </div>
            </div>
          </div>

          <!-- SEO Meta Card -->
          <div class="card p-6 space-y-4">
            <h3 class="font-bold text-slate-800">Pengaturan SEO & Meta</h3>
            <div>
              <label class="form-label">Kata Kunci Fokus (Focus Keyword)</label>
              <input v-model="form.focus_keyword" type="text" placeholder="misal: teknik sepeda motor" class="form-input text-sm" />
            </div>
            <div>
              <label class="form-label">Meta Title</label>
              <input v-model="form.meta_title" type="text" placeholder="Judul khusus SEO..." class="form-input text-sm" />
            </div>
            <div>
              <label class="form-label">Meta Description</label>
              <textarea v-model="form.meta_description" rows="3" placeholder="Deskripsi mesin pencari..." class="form-input text-sm resize-none"></textarea>
            </div>
            <button type="submit" :disabled="form.processing" class="w-full btn-primary justify-center py-3">
              {{ form.processing ? 'Menyimpan...' : 'Simpan Berita' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

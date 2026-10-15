<script setup>
import { computed, ref } from 'vue'
import RichEditor from '@/Components/RichEditor.vue'
import { analyzeSeo, slugify } from '@/seo-analyzer.js'
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['post', 'categories'])
const isEdit = !!props.post

const form = useForm({
  title: props.post?.title || '',
  slug: props.post?.slug || '',
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

const coverPreview = ref(props.post?.cover_image ? `/storage/${props.post.cover_image}` : null)
const onCover = (ev) => { const file = ev.target.files[0]; form.cover_image = file; if (file) coverPreview.value = URL.createObjectURL(file) }
const tab = ref('seo')
const seoAnalysis = computed(() => analyzeSeo({ ...form.data(), slug: form.slug || slugify(form.title), has_cover: !!coverPreview.value }))
const light = (s) => s >= 80 ? 'good' : s >= 50 ? 'ok' : 'bad'
const dot = { good: 'bg-green-500', ok: 'bg-orange-400', bad: 'bg-red-500' }
const lightText = { good: 'Bagus', ok: 'Cukup', bad: 'Perlu diperbaiki' }
const lightCls = { good: 'text-green-600', ok: 'text-orange-500', bad: 'text-red-600' }
const curScore = computed(() => tab.value === 'seo' ? seoAnalysis.value.seoScore : seoAnalysis.value.readScore)
const rank = { bad: 0, ok: 1, good: 2 }
const sorted = (list) => [...list].sort((a, b) => rank[a.status] - rank[b.status])
const site = typeof window !== 'undefined' ? window.location.host : 'cms-tsm.almuhsinblitar.my.id'
const snippetTitle = computed(() => { const t = seoAnalysis.value.seoTitle || 'Judul berita'; return t.length > 60 ? t.slice(0, 57) + '…' : t })
const snippetDesc = computed(() => { const d = seoAnalysis.value.desc || 'Isi meta deskripsi agar tampil di hasil pencarian Google.'; return d.length > 156 ? d.slice(0, 153) + '…' : d })

const submit = () => {
  if (isEdit) {
    form.transform(d => ({ ...d, _method: 'put' })).post(`/admin/berita/${props.post.id}`)
  } else {
    form.post('/admin/berita')
  }
}
</script>

<template>
  <AdminLayout>
    <title>{{ isEdit ? 'Edit Berita' : 'Tambah Berita' }} - Admin TSM</title>
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">Panel Kendali · TSM</span>{{ isEdit ? 'Edit Berita' : 'Tambah Berita Baru' }}</h1>
          <p class="text-slate-500 text-sm">Tulis dengan editor, sisipkan video YouTube, dan ikuti saran SEO & keterbacaan</p>
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
              <label class="form-label">Konten Berita *</label>
              <RichEditor v-model="form.body" />
              <p v-if="form.errors.body" class="text-red-500 text-xs mt-1">{{ form.errors.body }}</p>
            </div>

            <div class="grid sm:grid-cols-[1fr_200px] gap-4 items-start">
              <div class="space-y-3">
                <div>
                  <label class="form-label">Gambar Utama (Cover)</label>
                  <input type="file" accept="image/*" @change="onCover" class="form-input text-sm file:mr-4 file:py-1 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-ice file:text-navy" />
                </div>
                <div>
                  <label class="form-label">Teks Alt Cover</label>
                  <input v-model="form.cover_alt" type="text" placeholder="Deskripsi gambar untuk SEO & aksesibilitas" class="form-input text-sm" />
                </div>
              </div>
              <img v-if="coverPreview" :src="coverPreview" class="w-full aspect-video object-cover rounded-xl border border-line" alt="Pratinjau cover" />
            </div>
          </div>
        </div>

        <!-- Right: SEO Analysis & Meta -->
        <div class="space-y-6">
          <!-- SEO Checker (ala Yoast) -->
          <div class="card overflow-hidden">
            <div class="grid grid-cols-2 border-b border-line">
              <button type="button" @click="tab='seo'" :class="['flex items-center justify-center gap-2 py-3 text-sm font-semibold transition', tab==='seo' ? 'bg-navy text-white' : 'text-navy hover:bg-ice']">
                <span :class="['w-2.5 h-2.5 rounded-full', dot[light(seoAnalysis.seoScore)]]"></span>SEO <span class="font-mono text-xs opacity-75">{{ seoAnalysis.seoScore }}</span>
              </button>
              <button type="button" @click="tab='read'" :class="['flex items-center justify-center gap-2 py-3 text-sm font-semibold transition', tab==='read' ? 'bg-navy text-white' : 'text-navy hover:bg-ice']">
                <span :class="['w-2.5 h-2.5 rounded-full', dot[light(seoAnalysis.readScore)]]"></span>Keterbacaan <span class="font-mono text-xs opacity-75">{{ seoAnalysis.readScore }}</span>
              </button>
            </div>
            <div class="p-5">
              <p class="text-xs text-muted mb-3">Status: <b :class="lightCls[light(curScore)]">{{ lightText[light(curScore)] }}</b></p>
              <ul class="space-y-2.5 max-h-[420px] overflow-y-auto pr-1">
                <li v-for="(c, i) in sorted(tab==='seo' ? seoAnalysis.seo : seoAnalysis.read)" :key="tab + i" class="flex gap-2.5 text-xs leading-relaxed">
                  <span :class="['mt-1 w-2.5 h-2.5 rounded-full flex-shrink-0', dot[c.status]]"></span>
                  <span><b class="text-navy">{{ c.label }}:</b> <span class="text-slate-600">{{ c.text }}</span></span>
                </li>
              </ul>
            </div>
          </div>

          <!-- Google preview -->
          <div class="card p-5">
            <h3 class="font-mono text-[11px] uppercase tracking-[2px] text-label mb-3">Pratinjau Google</h3>
            <div class="rounded-xl border border-line p-4 bg-white">
              <div class="text-xs text-slate-500 truncate">{{ site }} › berita › {{ seoAnalysis.slug || 'slug' }}</div>
              <div class="text-[#1a0dab] text-lg leading-snug mt-1 break-words">{{ snippetTitle }}</div>
              <div class="text-[13px] text-slate-600 mt-1 leading-snug break-words">{{ snippetDesc }}</div>
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
              <label class="form-label">Slug URL</label>
              <input v-model="form.slug" type="text" :placeholder="slugify(form.title) || 'otomatis-dari-judul'" class="form-input text-sm font-mono" @blur="form.slug = slugify(form.slug)" />
              <p v-if="form.errors.slug" class="text-red-500 text-xs mt-1">{{ form.errors.slug }}</p>
            </div>
            <div>
              <label class="form-label flex justify-between">Meta Title <span class="font-mono text-[11px] text-muted">{{ (form.meta_title || form.title).length }}/60</span></label>
              <input v-model="form.meta_title" type="text" :placeholder="form.title || 'Judul khusus SEO...'" class="form-input text-sm" />
            </div>
            <div>
              <label class="form-label flex justify-between">Meta Description <span class="font-mono text-[11px] text-muted">{{ form.meta_description.length }}/156</span></label>
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

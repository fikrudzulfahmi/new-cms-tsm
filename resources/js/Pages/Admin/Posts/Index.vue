<script setup>
import { confirmDialog } from '@/dialog'
import { ref, watch, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['posts', 'categories', 'filters'])

const page = usePage()
const isAdmin = computed(() => (page.props.auth?.user?.role || 'editor') === 'admin')

const q = ref(props.filters?.q || '')
const status = ref(props.filters?.status || '')
const category = ref(props.filters?.category || '')

watch(() => props.filters, (f) => {
  q.value = f?.q || ''
  status.value = f?.status || ''
  category.value = f?.category || ''
}, { deep: true })

const applyFilter = () => {
  router.get('/admin/berita', {
    q: q.value || undefined,
    status: status.value || undefined,
    category: category.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  })
}

const resetFilter = () => {
  q.value = ''
  status.value = ''
  category.value = ''
  router.get('/admin/berita', {}, {
    preserveState: true,
    replace: true,
  })
}

const approve = (id) => router.patch(`/admin/berita/${id}/setujui`, {}, { preserveScroll: true })

const deletePost = async (id) => {
  if (await confirmDialog('Apakah Anda yakin ingin menghapus berita ini?')) {
    router.delete(`/admin/berita/${id}`)
  }
}

const statusLabels = {
  published: 'Tayang',
  draft: 'Draft',
  pending: 'Menunggu persetujuan',
  scheduled: 'Terjadwal',
  archived: 'Arsip',
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' }) : ''
</script>

<template>
  <AdminLayout>
    <title>Kelola Berita - Admin TSM</title>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]">
            <span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">Panel Kendali · TSM</span>
            Kelola Berita
          </h1>
          <p class="text-slate-500 text-sm">Buat dan kelola berita dengan analisis SEO ketat</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Link v-if="isAdmin" href="/admin/kategori"
            class="text-sm font-semibold px-4 py-2.5 rounded-full border border-line text-slate-600 hover:border-navy hover:text-navy transition-colors">
            Kategori
          </Link>
          <Link href="/admin/berita/buat" class="btn-primary text-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Berita
          </Link>
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="card p-4">
        <form @submit.prevent="applyFilter" class="grid sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
          <div class="sm:col-span-2 lg:col-span-5 relative">
            <input
              v-model="q"
              type="search"
              placeholder="Cari judul berita..."
              class="form-input text-sm pl-9"
            />
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>

          <div class="lg:col-span-3">
            <select v-model="status" @change="applyFilter" class="form-input text-sm">
              <option value="">Semua Status</option>
              <option value="published">Tayang (Published)</option>
              <option value="pending">Menunggu persetujuan</option>
              <option value="draft">Draft</option>
              <option value="scheduled">Terjadwal (Scheduled)</option>
              <option value="archived">Arsip (Archived)</option>
            </select>
          </div>

          <div class="lg:col-span-3">
            <select v-model="category" @change="applyFilter" class="form-input text-sm">
              <option value="">Semua Kategori</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>

          <div class="lg:col-span-1 flex items-center gap-2">
            <button type="submit" class="btn-primary text-xs px-4 py-2.5 min-h-0 w-full justify-center">
              Cari
            </button>
            <button
              v-if="q || status || category"
              type="button"
              @click="resetFilter"
              class="text-xs text-slate-500 hover:text-red-600 px-2 py-2 whitespace-nowrap"
              title="Reset Filter"
            >
              Reset
            </button>
          </div>
        </form>
      </div>

      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
              <tr>
                <th class="p-4">Judul</th>
                <th class="p-4">Kategori</th>
                <th class="p-4">Status</th>
                <th class="p-4">SEO Score</th>
                <th class="p-4">Statistik</th>
                <th class="p-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="p in posts.data" :key="p.id" class="hover:bg-slate-50/50">
                <td class="p-4 font-semibold text-slate-800 max-w-xs truncate">{{ p.title }}<div v-if="isAdmin && p.user && p.user.role !== 'admin'" class="text-xs font-normal text-slate-400">oleh {{ p.user.name }}</div></td>
                <td class="p-4">
                  <span v-if="p.category" class="badge text-xs" :style="`background:${p.category.color}20;color:${p.category.color}`">{{ p.category.name }}</span>
                </td>
                <td class="p-4">
                  <span :class="[
                    'badge text-xs capitalize',
                    p.status === 'published' ? 'bg-navy text-accent' :
                    p.status === 'scheduled' ? 'bg-blue-100 text-blue-800 border border-blue-200' :
                    p.status === 'pending' ? 'bg-orange-100 text-orange-800 border border-orange-200' :
                    p.status === 'archived' ? 'bg-amber-100 text-amber-800 border border-amber-200' :
                    'bg-ice text-label border border-line'
                  ]">{{ statusLabels[p.status] || p.status }}</span>
                </td>
                <td class="p-4">
                  <span :class="['font-bold', p.seo_score >= 80 ? 'text-green-600' : p.seo_score >= 50 ? 'text-amber-600' : 'text-red-600']">{{ p.seo_score }}/100</span>
                </td>
                <td class="p-4 text-xs text-slate-500">
                  👁 {{ p.views_count }} · 👍 {{ p.likes_count }} · 💬 {{ p.comments_count }}
                </td>
                <td class="p-4 text-right space-x-2">
                  <button v-if="isAdmin && p.status === 'pending'" @click="approve(p.id)" class="text-emerald-700 hover:underline font-semibold">Setujui</button>
                  <Link :href="`/admin/berita/${p.id}/edit`" class="text-blue-600 hover:underline font-medium">Edit</Link>
                  <button @click="deletePost(p.id)" class="text-red-600 hover:underline font-medium">Hapus</button>
                </td>
              </tr>
              <tr v-if="!posts.data?.length">
                <td colspan="6" class="p-8 text-center text-slate-400">Belum ada berita</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer: Total & Pagination -->
        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p class="text-xs text-slate-500">
            Menampilkan <span class="font-semibold text-slate-700">{{ posts.data?.length || 0 }}</span> dari <span class="font-semibold text-slate-700">{{ posts.total || 0 }}</span> berita
          </p>
          <div v-if="posts.links && posts.links.length > 1" class="flex flex-wrap items-center gap-1">
            <a
              v-for="(link, idx) in posts.links"
              :key="idx"
              :href="link.url || undefined"
              @click="!link.url && $event.preventDefault()"
              v-html="link.label"
              :class="[
                'px-3.5 py-1.5 rounded-full text-xs font-semibold border font-mono transition inline-flex items-center justify-center',
                link.active
                  ? 'bg-navy border-navy text-white'
                  : link.url
                    ? 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'
                    : 'bg-slate-100 border-slate-200 text-slate-400 cursor-not-allowed pointer-events-none'
              ]"
            />
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

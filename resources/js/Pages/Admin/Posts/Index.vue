<script setup>
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['posts'])

const deletePost = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus berita ini?')) {
    router.delete(`/admin/berita/${id}`)
  }
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' }) : ''
</script>

<template>
  <AdminLayout>
    <title>Kelola Berita - Admin TSM</title>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Kelola Berita</h1>
          <p class="text-slate-500 text-sm">Buat dan kelola berita dengan analisis SEO ketat</p>
        </div>
        <a href="/admin/berita/create" class="btn-primary text-sm flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Tambah Berita
        </a>
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
                <td class="p-4 font-semibold text-slate-800 max-w-xs truncate">{{ p.title }}</td>
                <td class="p-4">
                  <span v-if="p.category" class="badge text-xs" :style="`background:${p.category.color}20;color:${p.category.color}`">{{ p.category.name }}</span>
                </td>
                <td class="p-4">
                  <span :class="['badge text-xs', p.status==='published' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700']">{{ p.status }}</span>
                </td>
                <td class="p-4">
                  <span :class="['font-bold', p.seo_score >= 80 ? 'text-green-600' : p.seo_score >= 50 ? 'text-amber-600' : 'text-red-600']">{{ p.seo_score }}/100</span>
                </td>
                <td class="p-4 text-xs text-slate-500">
                  👁 {{ p.views_count }} · 👍 {{ p.likes_count }} · 💬 {{ p.comments_count }}
                </td>
                <td class="p-4 text-right space-x-2">
                  <a :href="`/admin/berita/${p.id}/edit`" class="text-blue-600 hover:underline font-medium">Edit</a>
                  <button @click="deletePost(p.id)" class="text-red-600 hover:underline font-medium">Hapus</button>
                </td>
              </tr>
              <tr v-if="!posts.data?.length">
                <td colspan="6" class="p-8 text-center text-slate-400">Belum ada berita</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['comments'])

const approve = (id) => router.put(`/admin/komentar/${id}/approve`, {}, { preserveScroll: true })
const destroy = (id) => {
  if (confirm('Hapus komentar ini?')) router.delete(`/admin/komentar/${id}`, { preserveScroll: true })
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) : ''
</script>

<template>
  <AdminLayout>
    <title>Kelola Komentar - Admin TSM</title>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Moderasi Komentar</h1>
        <p class="text-slate-500 text-sm">Kelola interaksi dan tanggapan pembaca pada berita</p>
      </div>

      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
              <tr>
                <th class="p-4">Pengirim</th>
                <th class="p-4">Komentar</th>
                <th class="p-4">Berita</th>
                <th class="p-4">Status</th>
                <th class="p-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="c in comments.data" :key="c.id" class="hover:bg-slate-50/50">
                <td class="p-4">
                  <div class="font-bold text-slate-800">{{ c.name }}</div>
                  <div class="text-xs text-slate-400">{{ c.email }}</div>
                </td>
                <td class="p-4 max-w-md">
                  <p class="text-slate-600 text-sm">{{ c.body }}</p>
                  <div class="text-xs text-slate-400 mt-1">{{ formatDate(c.created_at) }}</div>
                </td>
                <td class="p-4 text-xs text-blue-900 font-medium max-w-xs truncate">
                  <a :href="`/berita/${c.post?.slug}`" target="_blank" class="hover:underline">{{ c.post?.title }}</a>
                </td>
                <td class="p-4">
                  <span :class="['badge text-xs', c.is_approved ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700']">{{ c.is_approved ? 'Disetujui' : 'Pending' }}</span>
                </td>
                <td class="p-4 text-right space-x-2">
                  <button v-if="!c.is_approved" @click="approve(c.id)" class="text-green-600 hover:underline font-medium">Setujui</button>
                  <button @click="destroy(c.id)" class="text-red-600 hover:underline font-medium">Hapus</button>
                </td>
              </tr>
              <tr v-if="!comments.data?.length">
                <td colspan="5" class="p-8 text-center text-slate-400">Belum ada komentar</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

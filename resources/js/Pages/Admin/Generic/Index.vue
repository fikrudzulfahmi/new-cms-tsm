<script setup>
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['title', 'routeName', 'items', 'fields'])
const form = useForm(props.fields.reduce((acc, f) => ({ ...acc, [f.name]: f.default ?? '' }), {}))

const submit = () => {
  form.post(`/admin/${props.routeName}`, {
    onSuccess: () => form.reset()
  })
}

const destroy = (id) => {
  if (confirm('Hapus item ini?')) {
    form.delete(`/admin/${props.routeName}/${id}`, { preserveScroll: true })
  }
}

const isTesti = props.routeName === 'testimoni'
const setStatus = (item, status) => router.patch(`/admin/testimoni/${item.id}`, { status, is_featured: !!item.is_featured }, { preserveScroll: true })
const statusLabel = { pending: 'Menunggu persetujuan', approved: 'Ditampilkan', rejected: 'Ditolak' }
const statusClass = { pending: 'bg-amber-100 text-amber-700', approved: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-700' }
const pendingCount = props.items?.filter(i => i.status === 'pending').length || 0
</script>

<template>
  <AdminLayout>
    <title>{{ title }} - Admin TSM</title>
    <div class="space-y-6">
      <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">Panel Kendali · TSM</span>{{ title }}</h1>

      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Form Add -->
        <div v-if="fields?.length" class="card p-6 h-fit space-y-4">
          <h2 class="font-bold text-slate-800">Tambah Baru</h2>
          <form @submit.prevent="submit" class="space-y-4">
            <div v-for="f in fields" :key="f.name">
              <label class="form-label">{{ f.label }}</label>
              <input v-if="f.type==='text' || f.type==='number'" v-model="form[f.name]" :type="f.type" :placeholder="f.label" class="form-input text-sm" />
              <textarea v-else-if="f.type==='textarea'" v-model="form[f.name]" rows="2" :placeholder="f.label" class="form-input text-sm resize-none"></textarea>
              <select v-else-if="f.type==='select'" v-model="form[f.name]" class="form-input text-sm">
                <option v-for="opt in f.options" :key="opt" :value="opt">{{ opt }}</option>
              </select>
              <input v-else-if="f.type==='file'" type="file" @input="form[f.name] = $event.target.files[0]" class="form-input text-sm" />
            </div>
            <button type="submit" :disabled="form.processing" class="w-full btn-primary text-sm justify-center py-2.5">Simpan</button>
          </form>
        </div>

        <!-- List Items -->
        <div :class="[fields?.length ? 'lg:col-span-2' : 'lg:col-span-3', 'card p-6']">
          <h2 class="font-bold text-slate-800 mb-4">Daftar {{ title }}</h2>
          <p v-if="isTesti" class="text-xs text-slate-500 -mt-2 mb-4">Testimoni kiriman alumni baru tampil di website setelah disetujui. <b v-if="pendingCount" class="text-amber-700">{{ pendingCount }} menunggu persetujuan.</b></p>
          <div class="space-y-3">
            <div v-for="item in items" :key="'t'+item.id" v-show="isTesti" class="p-4 rounded-xl bg-slate-50 space-y-2" :class="item.status === 'pending' ? 'ring-1 ring-amber-300' : ''">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="font-bold text-sm text-slate-800">{{ item.name }} <span class="font-normal text-slate-500">· Lulusan {{ item.graduation_year }}<template v-if="item.occupation"> · {{ item.occupation }}</template><template v-if="item.workplace">, {{ item.workplace }}</template></span></div>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" :class="statusClass[item.status]">{{ statusLabel[item.status] || item.status }}</span>
              </div>
              <div class="text-amber-500 text-sm">{{ '★'.repeat(item.rating || 0) }}<span class="text-slate-300">{{ '★'.repeat(5 - (item.rating || 0)) }}</span></div>
              <p class="text-sm text-slate-600 whitespace-pre-line">{{ item.message }}</p>
              <div class="flex flex-wrap gap-3 pt-1 text-xs font-semibold">
                <button v-if="item.status !== 'approved'" @click="setStatus(item, 'approved')" class="px-3 py-1.5 rounded-lg bg-green-600 text-white hover:bg-green-700">Setujui &amp; tampilkan</button>
                <button v-if="item.status === 'approved'" @click="setStatus(item, 'pending')" class="px-3 py-1.5 rounded-lg bg-slate-200 text-slate-700 hover:bg-slate-300">Sembunyikan</button>
                <button v-if="item.status !== 'rejected'" @click="setStatus(item, 'rejected')" class="px-3 py-1.5 rounded-lg bg-amber-100 text-amber-800 hover:bg-amber-200">Tolak</button>
                <button @click="destroy(item.id)" class="px-3 py-1.5 text-red-600 hover:underline">Hapus</button>
              </div>
            </div>
            <div v-for="item in items" :key="item.id" v-show="!isTesti" class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
              <div class="flex items-center gap-3 min-w-0 pr-4">
                <img v-if="item.image || item.photo || item.logo || item.file" :src="`/storage/${item.image || item.photo || item.logo || item.file}`" class="w-12 h-12 rounded-lg object-cover flex-shrink-0" />
                <div class="min-w-0">
                  <div class="font-bold text-sm text-slate-800 truncate">{{ item.title || item.name || item.full_name }}</div>
                  <div class="text-xs text-slate-500 truncate">{{ item.subtitle || item.short_description || item.position || item.category || item.level || item.message || item.registration_number || '' }}<span v-if="item.status" class="ml-2 badge-status">{{ item.status }}</span></div>
                </div>
              </div>
              <button v-if="!isTesti" @click="destroy(item.id)" class="text-red-600 text-xs font-semibold hover:underline flex-shrink-0">Hapus</button>
            </div>
            <div v-if="!items?.length" class="text-slate-400 text-xs text-center py-8">Belum ada data</div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

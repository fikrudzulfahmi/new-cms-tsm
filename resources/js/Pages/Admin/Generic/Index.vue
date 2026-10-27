<script setup>
import { confirmDialog } from '@/dialog'
import { ref, computed } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import SectionTabs from '@/Components/Admin/SectionTabs.vue'

const props = defineProps(['title', 'routeName', 'items', 'fields', 'canCreate', 'publicUrl'])

// Halaman konten yang dikelola dengan formulir yang sama dikelompokkan
// sebagai tab, supaya sidebar tidak penuh tombol serupa.
const HALAMAN_TABS = [
  { label: 'Guru', href: '/admin/guru' },
  { label: 'Kompetensi', href: '/admin/kompetensi' },
  { label: 'Program', href: '/admin/program' },
  { label: 'Sarana', href: '/admin/sarana' },
  { label: 'Prestasi', href: '/admin/prestasi' },
  { label: 'Testimoni', href: '/admin/testimoni' },
  { label: 'Mitra', href: '/admin/mitra' },
]
const MEDIA_TABS = [
  { label: 'Galeri Foto & Video', href: '/admin/galeri' },
  { label: 'Slider Hero', href: '/admin/slider' },
]

const pageUrl = computed(() => (usePage().url || '').split('?')[0])
const tabs = computed(() => {
  if (HALAMAN_TABS.some((t) => t.href === pageUrl.value)) return HALAMAN_TABS
  if (MEDIA_TABS.some((t) => t.href === pageUrl.value)) return MEDIA_TABS
  return null
})

const emptyValues = () => (props.fields || []).reduce((acc, f) => ({
  ...acc,
  [f.name]: f.default ?? '',
  ...(f.type === 'file' ? { ['remove_' + f.name]: false } : {}),
}), {})
const form = useForm(emptyValues())

const editing = ref(null)
const q = ref('')
const visibleItems = computed(() => {
  const needle = q.value.trim().toLowerCase()
  if (!needle) return props.items || []
  return (props.items || []).filter((i) => [i.title, i.name, i.subject, i.category, i.level, i.position]
    .filter(Boolean).some((v) => String(v).toLowerCase().includes(needle)))
})
const editingId = computed(() => editing.value?.id ?? null)
const isTesti = computed(() => props.routeName === 'testimoni')
const isPesan = computed(() => props.routeName === 'pesan')
const canCreate = computed(() => props.canCreate !== false)
const showForm = computed(() => !!props.fields?.length && (canCreate.value || !!editing.value))

function startEdit(item) {
  editing.value = item
  const values = emptyValues()
  for (const f of props.fields || []) {
    if (f.type === 'file') {
      // Jangan kirim path lama sebagai file: biarkan kosong bila tidak diganti.
      values[f.name] = null
      continue
    }
    const v = item[f.name]
    // Boolean dari API harus jadi string '1'/'0' agar <select> memilih opsi
    // yang benar (kalau tidak, dropdown status tampak kosong saat mengubah).
    if (typeof v === 'boolean') values[f.name] = v ? '1' : '0'
    else values[f.name] = (v === null || v === undefined) ? '' : v
  }
  form.clearErrors()
  form.defaults(values)
  form.reset()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function resetForm() {
  editing.value = null
  form.defaults(emptyValues())
  form.reset()
  form.clearErrors()
}

function cancelEdit() {
  resetForm()
}

function submit() {
  const opts = {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => resetForm(),
  }
  if (editingId.value) {
    // Testimoni memakai PATCH, modul lain PUT.
    const method = props.routeName === 'testimoni' ? 'patch' : 'put'
    form.transform((d) => ({ ...d, _method: method })).post(`/admin/${props.routeName}/${editingId.value}`, opts)
  } else {
    form.post(`/admin/${props.routeName}`, opts)
  }
}

const destroy = async (itemOrId) => {
  const item = typeof itemOrId === 'object' ? itemOrId : (props.items || []).find(i => i.id === itemOrId)
  const label = item ? (item.title || item.name || item.subject || `#${item.id}`) : `#${itemOrId}`
  if (await confirmDialog(`Hapus "${label}"? Tindakan ini tidak bisa dibatalkan.`)) {
    router.delete(`/admin/${props.routeName}/${item ? item.id : itemOrId}`, { preserveScroll: true })
  }
}

const setStatus = (item, status) => router.patch(`/admin/testimoni/${item.id}`, { status, is_featured: !!item.is_featured }, { preserveScroll: true })
const markRead = (item) => router.patch(`/admin/pesan/${item.id}/read`, {}, { preserveScroll: true })
const statusLabel = { pending: 'Menunggu persetujuan', approved: 'Ditampilkan', rejected: 'Ditolak' }
const statusClass = { pending: 'bg-amber-100 text-amber-700', approved: 'bg-green-100 text-green-700', rejected: 'bg-red-100 text-red-700' }
const pendingCount = props.items?.filter(i => i.status === 'pending').length || 0
const unreadCount = props.items?.filter(i => !i.is_read).length || 0
const itemImage = (item) => item.image || item.photo || item.logo || item.file || null
const itemSubtitle = (item) => item.subtitle || item.short_description || item.position || item.category || item.level || item.subject || ''
const waNumber = (phone) => (phone || '').replace(/[^0-9]/g, '').replace(/^0/, '62')
</script>

<template>
  <AdminLayout>
    <title>{{ title }} - Admin TSM</title>
    <div class="space-y-6">
      <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">Panel Kendali · TSM</span>{{ title }}</h1>
      <a v-if="publicUrl" :href="publicUrl" target="_blank" rel="noopener" class="inline-flex -mt-4 text-sm font-semibold text-label hover:underline">Lihat halaman {{ publicUrl }} di website ↗</a>

      <SectionTabs v-if="tabs" :items="tabs" />

      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Form tambah / ubah -->
        <div v-if="showForm" class="card p-6 h-fit space-y-4">
          <div v-if="canCreate || editingId" class="flex items-center justify-between gap-3">
            <h2 class="font-bold text-slate-800">{{ editingId ? 'Ubah Data' : 'Tambah Baru' }}</h2>
            <button v-if="editingId" type="button" @click="cancelEdit" class="text-xs font-semibold text-slate-500 hover:text-slate-800">Batal</button>
          </div>
          <p v-if="editingId" class="text-xs text-slate-500 -mt-2">Sedang mengubah: <b>{{ editing.name || editing.title }}</b></p>
          <form @submit.prevent="submit" class="space-y-4">
            <div v-for="f in fields" :key="f.name">
              <label :for="`f-${f.name}`" class="form-label">{{ f.label }}</label>
              <input v-if="f.type==='text' || f.type==='number' || f.type==='date'" :id="`f-${f.name}`" v-model="form[f.name]" :type="f.type" :placeholder="f.label" class="form-input text-sm" />
              <textarea v-else-if="f.type==='textarea'" :id="`f-${f.name}`" v-model="form[f.name]" rows="3" :placeholder="f.label" class="form-input text-sm"></textarea>
              <select v-else-if="f.type==='select'" :id="`f-${f.name}`" v-model="form[f.name]" class="form-input text-sm">
                <option v-for="opt in f.options" :key="opt" :value="opt">{{ opt }}</option>
              </select>
              <template v-else-if="f.type==='file'">
                <input type="file" @change="form[f.name] = $event.target.files[0] || null" class="form-input text-sm" />
                <p v-if="editingId && editing[f.name]" class="text-xs text-slate-500 mt-1">
                  File sekarang: <a :href="`/storage/${editing[f.name]}`" target="_blank" class="text-blue-700 hover:underline">lihat</a> — biarkan kosong bila tidak diganti.
                </p>
                <label v-if="editingId && editing[f.name]" class="flex items-center gap-2 text-xs text-slate-500 mt-2">
                  <input type="checkbox" v-model="form['remove_' + f.name]" /> Hapus file ini
                </label>
              </template>
              <p v-if="f.hint" class="text-xs text-slate-400 mt-1">{{ f.hint }}</p>
              <p v-if="form.errors[f.name]" class="text-xs text-red-600 mt-1">{{ form.errors[f.name] }}</p>
            </div>
            <div class="flex gap-2">
              <button type="submit" :disabled="form.processing" class="flex-1 btn-primary text-sm justify-center py-2.5">{{ editingId ? 'Simpan Perubahan' : 'Simpan' }}</button>
              <button v-if="editingId" type="button" @click="cancelEdit" class="btn-ghost text-sm py-2.5">Batal</button>
            </div>
          </form>
        </div>

        <!-- Daftar -->
        <div :class="[showForm ? 'lg:col-span-2' : 'lg:col-span-3', 'card p-6']">
          <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h2 class="font-bold text-slate-800">Daftar {{ title }}</h2>
            <input v-if="(items?.length || 0) > 8" v-model="q" type="search" placeholder="Cari…" class="form-input text-sm w-48 py-1.5" />
          </div>
          <p v-if="q && !visibleItems.length" class="text-xs text-slate-500 -mt-2 mb-3">Tidak ada yang cocok dengan "{{ q }}".</p>
          <p v-if="isTesti" class="text-xs text-slate-500 -mt-2 mb-4">Testimoni kiriman alumni baru tampil di website setelah disetujui. <b v-if="pendingCount" class="text-amber-700">{{ pendingCount }} menunggu persetujuan.</b></p>
          <p v-if="isPesan && unreadCount" class="text-xs text-slate-500 -mt-2 mb-4"><b class="text-amber-700">{{ unreadCount }} pesan belum dibaca.</b></p>

          <div class="space-y-3">
            <!-- Testimoni -->
            <div v-for="item in visibleItems" :key="'t'+item.id" v-show="isTesti" class="p-4 rounded-xl bg-slate-50 space-y-2" :class="[item.status === 'pending' ? 'ring-1 ring-amber-300' : '', editingId === item.id ? 'ring-2 ring-blue-400' : '']">
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
                <button v-if="fields?.length" @click="startEdit(item)" class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-800 hover:bg-blue-100">Ubah</button>
                <button @click="destroy(item)" class="px-3 py-1.5 text-red-600 hover:underline">Hapus</button>
              </div>
            </div>

            <!-- Pesan masuk -->
            <div v-for="item in visibleItems" :key="'m'+item.id" v-show="isPesan" class="p-4 rounded-xl bg-slate-50 space-y-2" :class="!item.is_read ? 'ring-1 ring-amber-300' : ''">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="font-bold text-sm text-slate-800">{{ item.name }}<span class="font-normal text-slate-500"><template v-if="item.email"> · {{ item.email }}</template><template v-if="item.phone"> · {{ item.phone }}</template></span></div>
                <span class="text-[11px] text-slate-500">{{ new Date(item.created_at).toLocaleString('id-ID') }}</span>
              </div>
              <div v-if="item.subject" class="text-xs font-semibold text-slate-600">{{ item.subject }}</div>
              <p class="text-sm text-slate-600 whitespace-pre-line">{{ item.message }}</p>
              <div class="flex flex-wrap gap-3 pt-1 text-xs font-semibold">
                <a v-if="item.email" :href="`mailto:${item.email}?subject=${encodeURIComponent('Balasan: ' + (item.subject || 'Pesan Anda'))}`" class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-800 hover:bg-blue-100">Balas lewat Email</a>
                <a v-if="item.phone" :href="`https://wa.me/${waNumber(item.phone)}`" target="_blank" rel="noopener" class="px-3 py-1.5 rounded-lg bg-green-50 text-green-800 hover:bg-green-100">Balas lewat WhatsApp</a>
                <button v-if="!item.is_read" @click="markRead(item)" class="px-3 py-1.5 rounded-lg bg-slate-200 text-slate-700 hover:bg-slate-300">Tandai sudah dibaca</button>
                <span v-else class="px-3 py-1.5 text-slate-400">Sudah dibaca</span>
                <button @click="destroy(item)" class="px-3 py-1.5 text-red-600 hover:underline">Hapus</button>
              </div>
            </div>

            <!-- Item umum -->
            <div v-for="item in visibleItems" :key="item.id" v-show="!isTesti && !isPesan" class="flex items-start justify-between gap-3 p-3 rounded-xl bg-slate-50" :class="editingId === item.id ? 'ring-2 ring-blue-400' : ''">
              <div class="flex items-center gap-3 min-w-0 pr-2">
                <img v-if="itemImage(item)" :src="`/storage/${itemImage(item)}`" class="w-12 h-12 rounded-lg object-cover flex-shrink-0" />
                <div class="min-w-0">
                  <div class="font-bold text-sm text-slate-800 truncate">{{ item.title || item.name }}</div>
                  <div class="text-xs text-slate-500 truncate">
                    {{ itemSubtitle(item) }}
                    <span v-if="item.year" class="ml-2">{{ item.year }}</span>
                    <span v-if="item.quantity" class="ml-2">{{ item.quantity }} unit</span>
                    <span v-if="item.sort_order !== undefined && item.sort_order !== null" class="ml-2 font-mono text-slate-400">#{{ item.sort_order }}</span>
                    <span v-if="item.is_active !== undefined" class="ml-2" :class="item.is_active ? 'text-green-700' : 'text-slate-400'">{{ item.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-2 flex-shrink-0 text-xs font-semibold">
                <button v-if="fields?.length" @click="startEdit(item)" class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-800 hover:bg-blue-100">Ubah</button>
                <button @click="destroy(item)" class="text-red-600 hover:underline px-1">Hapus</button>
              </div>
            </div>

            <div v-if="!items?.length" class="text-slate-400 text-xs text-center py-8">Belum ada data</div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { confirmDialog, promptDialog } from '@/dialog'

const props = defineProps(['users', 'filters', 'roles', 'counts'])

const showCreate = ref(false)
const editing = ref(null)

const form = useForm({
  name: '',
  email: '',
  password: '',
  role: 'writer',
  is_active: true,
})

const editForm = useForm({ name: '', email: '', role: 'writer', is_active: true })

const roleOptions = computed(() => props.roles || { admin: 'Administrator', editor: 'Editor', writer: 'Penulis' })
const roleLabel = (r) => roleOptions.value[r] || r

const search = ref(props.filters?.q || '')
const roleFilter = ref(props.filters?.role || '')

const applyFilter = () => {
  router.get('/admin/pengguna', { q: search.value || undefined, role: roleFilter.value || undefined }, { preserveState: true, replace: true })
}

const submit = () => {
  form.post('/admin/pengguna', {
    preserveScroll: true,
    onSuccess: () => { form.reset('name', 'email', 'password'); showCreate.value = false },
  })
}

const startEdit = (u) => {
  editing.value = u.id
  editForm.name = u.name
  editForm.email = u.email
  editForm.role = u.role
  editForm.is_active = u.is_active
}

const cancelEdit = () => { editing.value = null; editForm.clearErrors() }

const saveEdit = (u) => {
  editForm.put(`/admin/pengguna/${u.id}`, {
    preserveScroll: true,
    onSuccess: () => { editing.value = null },
  })
}

const toggleActive = (u) => {
  editForm.name = u.name
  editForm.email = u.email
  editForm.role = u.role
  editForm.is_active = !u.is_active
  editForm.put(`/admin/pengguna/${u.id}`, { preserveScroll: true, onSuccess: () => { editing.value = null } })
}

const resetPassword = async (u) => {
  const pw = await promptDialog(`Kata sandi baru untuk ${u.name} (min. 8 karakter)`, { placeholder: 'kata sandi baru' })
  if (!pw || pw.length < 8) return
  router.patch(`/admin/pengguna/${u.id}/reset-password`, { password: pw }, { preserveScroll: true })
}

const destroy = async (u) => {
  if (await confirmDialog(`Hapus akun ${u.name}?`)) router.delete(`/admin/pengguna/${u.id}`, { preserveScroll: true })
}

const fmt = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'
</script>

<template>
  <AdminLayout>
    <title>Pengguna - Admin TSM</title>
    <div class="space-y-6">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]">
            <span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">Panel Kendali · TSM</span>
            Pengguna
          </h1>
          <p class="text-slate-500 text-sm">Kelola akun admin, editor, dan penulis berita.</p>
        </div>
        <button @click="showCreate = !showCreate" class="btn-primary px-6 py-2.5 text-sm">
          {{ showCreate ? 'Tutup Form' : '+ Tambah Pengguna' }}
        </button>
      </div>

      <!-- Ringkasan peran -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="card p-4">
          <div class="font-mono text-[11px] uppercase tracking-[1.5px] text-label">Semua</div>
          <div class="font-display text-2xl font-bold text-navy">{{ counts.all }}</div>
        </div>
        <div class="card p-4">
          <div class="font-mono text-[11px] uppercase tracking-[1.5px] text-label">Administrator</div>
          <div class="font-display text-2xl font-bold text-navy">{{ counts.admin }}</div>
        </div>
        <div class="card p-4">
          <div class="font-mono text-[11px] uppercase tracking-[1.5px] text-label">Editor</div>
          <div class="font-display text-2xl font-bold text-navy">{{ counts.editor }}</div>
        </div>
        <div class="card p-4">
          <div class="font-mono text-[11px] uppercase tracking-[1.5px] text-label">Penulis</div>
          <div class="font-display text-2xl font-bold text-navy">{{ counts.writer }}</div>
        </div>
      </div>

      <!-- Form tambah -->
      <div v-if="showCreate" class="card p-6 space-y-4">
        <h2 class="font-bold text-slate-800">Akun Baru</h2>
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="form-label" for="u-name">Nama Lengkap</label>
            <input id="u-name" v-model="form.name" type="text" class="form-input" autocomplete="off" />
            <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="form-label" for="u-email">Email</label>
            <input id="u-email" v-model="form.email" type="email" class="form-input" autocomplete="off" />
            <p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
          </div>
          <div>
            <label class="form-label" for="u-pass">Kata Sandi</label>
            <input id="u-pass" v-model="form.password" type="password" class="form-input" autocomplete="new-password" />
            <p class="text-xs text-slate-500 mt-1">Minimal 8 karakter.</p>
            <p v-if="form.errors.password" class="text-xs text-red-600 mt-1">{{ form.errors.password }}</p>
          </div>
          <div>
            <label class="form-label" for="u-role">Peran</label>
            <select id="u-role" v-model="form.role" class="form-input">
              <option v-for="(label, key) in roleOptions" :key="key" :value="key">{{ label }}</option>
            </select>
            <p v-if="form.errors.role" class="text-xs text-red-600 mt-1">{{ form.errors.role }}</p>
          </div>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input v-model="form.is_active" type="checkbox" /> Akun langsung aktif
        </label>
        <div class="flex gap-3">
          <button @click="submit" :disabled="form.processing" class="btn-primary px-6 py-2.5 text-sm">Simpan Akun</button>
          <button @click="showCreate = false" class="btn-ghost px-6 py-2.5 text-sm">Batal</button>
        </div>
      </div>

      <!-- Filter -->
      <form @submit.prevent="applyFilter" class="card p-4 flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
          <label class="form-label" for="q">Cari</label>
          <input id="q" v-model="search" type="search" class="form-input" placeholder="Nama atau email" />
        </div>
        <div>
          <label class="form-label" for="roleFilter">Peran</label>
          <select id="roleFilter" v-model="roleFilter" class="form-input">
            <option value="">Semua peran</option>
            <option v-for="(label, key) in roleOptions" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
        <button type="submit" class="btn-ghost px-5 py-2.5 text-sm">Terapkan</button>
      </form>

      <!-- Tabel -->
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
              <tr>
                <th class="p-4">Nama</th>
                <th class="p-4">Peran</th>
                <th class="p-4">Status</th>
                <th class="p-4">Login Terakhir</th>
                <th class="p-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="u in users.data" :key="u.id" class="hover:bg-slate-50/50 align-top">
                <td class="p-4">
                  <template v-if="editing === u.id">
                    <input v-model="editForm.name" type="text" class="form-input mb-2" />
                    <input v-model="editForm.email" type="email" class="form-input" />
                    <p v-if="editForm.errors.name" class="text-xs text-red-600 mt-1">{{ editForm.errors.name }}</p>
                    <p v-if="editForm.errors.email" class="text-xs text-red-600 mt-1">{{ editForm.errors.email }}</p>
                  </template>
                  <template v-else>
                    <div class="font-bold text-slate-800">{{ u.name }}</div>
                    <div class="text-xs text-slate-400">{{ u.email }}</div>
                  </template>
                </td>
                <td class="p-4">
                  <select v-if="editing === u.id" v-model="editForm.role" class="form-input">
                    <option v-for="(label, key) in roleOptions" :key="key" :value="key">{{ label }}</option>
                  </select>
                  <span v-else class="badge bg-ice text-label border border-line">{{ roleLabel(u.role) }}</span>
                </td>
                <td class="p-4">
                  <button v-if="editing === u.id" @click="editForm.is_active = !editForm.is_active"
                    :class="['badge', editForm.is_active ? 'bg-navy text-accent' : 'bg-red-50 text-red-700 border border-red-200']">
                    {{ editForm.is_active ? 'Aktif' : 'Nonaktif' }}
                  </button>
                  <button v-else @click="toggleActive(u)"
                    :class="['badge', u.is_active ? 'bg-navy text-accent' : 'bg-red-50 text-red-700 border border-red-200']"
                    :title="u.is_active ? 'Nonaktifkan akun' : 'Aktifkan akun'">
                    {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                  </button>
                </td>
                <td class="p-4 text-xs text-slate-500">{{ fmt(u.last_login_at) }}</td>
                <td class="p-4 text-right space-x-3 whitespace-nowrap">
                  <template v-if="editing === u.id">
                    <button @click="saveEdit(u)" :disabled="editForm.processing" class="text-blue-800 font-medium hover:underline">Simpan</button>
                    <button @click="cancelEdit" class="text-slate-500 hover:underline">Batal</button>
                  </template>
                  <template v-else>
                    <button @click="startEdit(u)" class="text-blue-800 font-medium hover:underline">Ubah</button>
                    <button @click="resetPassword(u)" class="text-amber-600 font-medium hover:underline">Reset Sandi</button>
                    <button @click="destroy(u)" class="text-red-600 font-medium hover:underline">Hapus</button>
                  </template>
                </td>
              </tr>
              <tr v-if="!users.data?.length">
                <td colspan="5" class="p-8 text-center text-slate-400">Belum ada pengguna yang cocok.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="users.links?.length > 3" class="flex flex-wrap gap-1 p-4 border-t border-slate-100">
          <template v-for="link in users.links" :key="link.label">
            <a v-if="link.url" :href="link.url" v-html="link.label"
              :class="['px-3 py-1.5 rounded-lg text-xs font-semibold border', link.active ? 'bg-navy text-white border-navy' : 'border-line text-slate-600 hover:border-label']"></a>
            <span v-else class="px-3 py-1.5 rounded-lg text-xs text-slate-300 border border-transparent" v-html="link.label"></span>
          </template>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

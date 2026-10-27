<script setup>
import { confirmDialog } from '@/dialog'
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue'
const props = defineProps({ activeYear: String, courses: Array, teachers: Array, allClasses: Array })
const editId = ref(null)
const form = useForm({ title: '', description: '', teacher_id: '', class_ids: [], is_active: true })
function reset() { editId.value = null; form.reset(); form.clearErrors() }
function edit(c) { editId.value = c.id; form.title = c.title; form.description = c.description || ''; form.teacher_id = c.teacher_id; form.class_ids = [...c.class_ids]; form.is_active = c.is_active; window.scrollTo({ top: 0 }) }
function save() {
  const opt = { preserveScroll: true, onSuccess: reset }
  editId.value ? form.put(`/superadmin/mapel/${editId.value}`, opt) : form.post('/superadmin/mapel', opt)
}
async function del(c) { if (await confirmDialog(`Hapus mapel "${c.title}" beserta seluruh bab dan materinya?`)) router.delete(`/superadmin/mapel/${c.id}`, { preserveScroll: true }) }
</script>

<template>
  <SuperadminLayout title="Mapel & Penugasan Guru">
    <p class="text-sm text-slate-500 -mt-2 mb-4">Tahun ajaran aktif: <b>{{ activeYear || 'belum diatur' }}</b></p>
    <form class="bg-white rounded-2xl border border-slate-200 p-5 space-y-3 mb-6" @submit.prevent="save">
      <h2 class="font-semibold text-navy">{{ editId ? 'Ubah mapel' : 'Mapel baru' }}</h2>
      <input v-model="form.title" placeholder="Nama mapel" maxlength="150" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
      <textarea v-model="form.description" rows="2" placeholder="Deskripsi (opsional)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
      <select v-model="form.teacher_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
        <option value="" disabled>Pilih guru</option>
        <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
      </select>
      <div>
        <div class="text-xs text-slate-500 mb-1">Rombel peserta</div>
        <div class="flex flex-wrap gap-3">
          <label v-for="k in allClasses" :key="k.id" class="text-sm flex items-center gap-1.5"><input v-model="form.class_ids" type="checkbox" :value="k.id" /> {{ k.name }}</label>
          <span v-if="!allClasses.length" class="text-xs text-slate-400">Belum ada rombel. Jalankan sinkron dulu.</span>
        </div>
      </div>
      <label class="text-sm flex items-center gap-2"><input v-model="form.is_active" type="checkbox" /> Aktif</label>
      <p v-for="(e, k) in form.errors" :key="k" class="text-red-600 text-xs">{{ e }}</p>
      <div class="flex gap-2">
        <button class="rounded-xl bg-navy text-white text-sm font-semibold px-4 py-2" :disabled="form.processing">{{ editId ? 'Simpan' : 'Tambah' }}</button>
        <button v-if="editId" type="button" class="text-sm text-slate-500" @click="reset">Batal</button>
      </div>
    </form>

    <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
      <div v-for="c in courses" :key="c.id" class="p-4 flex flex-wrap items-center gap-3 text-sm">
        <div class="flex-1 min-w-[12rem]">
          <div class="font-semibold text-navy">{{ c.title }} <span v-if="!c.is_active" class="text-xs text-slate-400">(nonaktif)</span></div>
          <div class="text-xs text-slate-500">{{ c.teacher }} &middot; {{ c.classes.join(', ') }}</div>
        </div>
        <button class="text-navy text-xs font-semibold" @click="edit(c)">Ubah</button>
        <button class="text-red-600 text-xs" @click="del(c)">Hapus</button>
      </div>
      <p v-if="!courses.length" class="p-4 text-sm text-slate-500">Belum ada mapel pada tahun ajaran ini.</p>
    </div>
  </SuperadminLayout>
</template>

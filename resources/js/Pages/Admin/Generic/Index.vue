<script setup>
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['title', 'routeName', 'items', 'fields'])
const form = useForm(props.fields.reduce((acc, f) => ({ ...acc, [f.name]: f.default || '' }), {}))
form.image = null

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
</script>

<template>
  <AdminLayout>
    <title>{{ title }} - Admin TSM</title>
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-slate-900">{{ title }}</h1>

      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Form Add -->
        <div class="card p-6 h-fit space-y-4">
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
        <div class="lg:col-span-2 card p-6">
          <h2 class="font-bold text-slate-800 mb-4">Daftar {{ title }}</h2>
          <div class="space-y-3">
            <div v-for="item in items" :key="item.id" class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
              <div class="flex items-center gap-3 min-w-0 pr-4">
                <img v-if="item.image || item.photo || item.logo || item.file" :src="`/storage/${item.image || item.photo || item.logo || item.file}`" class="w-12 h-12 rounded-lg object-cover flex-shrink-0" />
                <div class="min-w-0">
                  <div class="font-bold text-sm text-slate-800 truncate">{{ item.title || item.name }}</div>
                  <div class="text-xs text-slate-500 truncate">{{ item.subtitle || item.short_description || item.position || item.category || item.level || '' }}</div>
                </div>
              </div>
              <button @click="destroy(item.id)" class="text-red-600 text-xs font-semibold hover:underline flex-shrink-0">Hapus</button>
            </div>
            <div v-if="!items?.length" class="text-slate-400 text-xs text-center py-8">Belum ada data</div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

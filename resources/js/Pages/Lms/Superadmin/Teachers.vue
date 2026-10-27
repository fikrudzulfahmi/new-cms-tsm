<script setup>
import { confirmDialog } from '@/dialog'
import { ref } from 'vue'
import { useForm, router, Head, Link } from '@inertiajs/vue3'
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue'

const props = defineProps(['teachers', 'q'])
const form = useForm({ name: '', username: '' })
const search = ref(props.q || '')
const cari = () => router.get('/superadmin/guru', { q: search.value || undefined }, { preserveState: true, replace: true })
const writer = (u) => router.post(`/superadmin/akun/${u.id}/penulis`, {}, { preserveScroll: true })
const reset = async (u) => await confirmDialog(`Reset password ${u.name} ke username?`) && router.post(`/superadmin/akun/${u.id}/reset`, {}, { preserveScroll: true })
const toggle = async (u) => await confirmDialog(`${u.is_active ? 'Nonaktifkan' : 'Aktifkan'} akun ${u.name}?`) && router.post(`/superadmin/akun/${u.id}/aktif`, {}, { preserveScroll: true })
</script>

<template>
  <Head title="Data Guru" />
  <SuperadminLayout title="Data Guru">
    <div class="space-y-6">
      <section class="rounded-2xl bg-white border border-line p-5">
        <h2 class="mb-1 font-semibold">Tambah guru</h2>
        <p class="mb-3 text-xs text-slate-500">API induk belum menyediakan data guru, jadi akun guru dibuat manual di sini. Username = NIK, password awal = username (wajib diganti saat login pertama).</p>
        <form class="grid gap-2 md:grid-cols-[1fr_1fr_auto]" @submit.prevent="form.post('/superadmin/guru', { preserveScroll: true, onSuccess: () => form.reset() })">
          <input v-model="form.name" placeholder="Nama guru" class="rounded border px-3 py-1.5 text-sm" />
          <input v-model="form.username" placeholder="NIK (username login)" class="rounded border px-3 py-1.5 text-sm" />
          <button class="rounded bg-navy px-3 py-1.5 text-sm text-white" :disabled="form.processing">Buat akun guru</button>
          <p v-if="form.errors.name || form.errors.username" class="text-xs text-red-600 md:col-span-3">{{ form.errors.name || form.errors.username }}</p>
        </form>
      </section>
      <section class="rounded-2xl bg-white border border-line p-5 space-y-3">
        <h2 class="font-semibold">Daftar guru</h2>
        <form @submit.prevent="cari"><input v-model="search" placeholder="Cari nama / NIK…" class="w-full rounded border px-3 py-1.5 text-sm md:w-72" /></form>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
          <thead class="text-xs text-slate-500"><tr><th class="py-1">Nama</th><th>Username (NIK)</th><th>Penulis berita</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <tr v-for="u in teachers.data" :key="u.id" class="border-t">
              <td class="py-1.5">{{ u.name }}</td><td>{{ u.username }}</td>
              <td><button type="button" role="switch" :aria-checked="u.can_write_posts" :title="u.can_write_posts ? 'Cabut izin menulis' : 'Beri izin menulis berita'" class="relative h-5 w-9 rounded-full transition" :class="u.can_write_posts ? 'bg-navy' : 'bg-slate-300'" @click="writer(u)"><span class="absolute top-0.5 h-4 w-4 rounded-full bg-white transition-all" :class="u.can_write_posts ? 'left-[18px]' : 'left-0.5'"></span></button></td>
              <td>{{ u.is_active ? 'Aktif' : 'Nonaktif' }}<span v-if="u.must_change_password" class="ml-1 text-xs text-amber-600">(belum ganti password)</span></td>
              <td class="space-x-2 text-right"><button class="text-navy font-semibold hover:underline" @click="reset(u)">Reset password</button><button class="text-slate-600 hover:underline" @click="toggle(u)">{{ u.is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></td>
            </tr>
            <tr v-if="!teachers.data.length"><td colspan="5" class="py-3 text-slate-500">Belum ada guru.</td></tr>
          </tbody>
        </table></div>
      <div v-if="teachers.links && teachers.last_page > 1" class="flex flex-wrap gap-1 text-sm">
        <template v-for="l in teachers.links" :key="l.label">
          <Link v-if="l.url" :href="l.url" preserve-scroll class="rounded border px-2.5 py-1" :class="l.active ? 'bg-navy text-white border-navy' : 'bg-white hover:bg-slate-50'" v-html="l.label" />
          <span v-else class="rounded border px-2.5 py-1 text-slate-400" v-html="l.label" />
        </template>
      </div>
      </section>
    </div>
  </SuperadminLayout>
</template>

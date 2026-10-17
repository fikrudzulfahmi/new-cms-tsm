<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  logs: Object, stats: Object, failedByIp: Array, deniedRecent: Array,
  users: Array, modules: Object, actions: Array, filters: Object, retentionDays: Number,
})

const f = ref({ aksi: props.filters.action || '', modul: props.filters.module || '', user: props.filters.userId || '', q: props.filters.q || '', dari: props.filters.from || '', sampai: props.filters.to || '' })
const loading = ref(false)
let t = null

const apply = () => {
  const clean = Object.fromEntries(Object.entries(f.value).filter(([, v]) => v !== ''))
  router.get('/admin/aktivitas', clean, { preserveState: true, preserveScroll: true, replace: true, onStart: () => (loading.value = true), onFinish: () => (loading.value = false) })
}
watch(f, () => { clearTimeout(t); t = setTimeout(apply, 350) }, { deep: true })
const reset = () => { f.value = { aksi: '', modul: '', user: '', q: '', dari: '', sampai: '' } }

const badge = {
  green: 'bg-green-100 text-green-700', red: 'bg-red-100 text-red-700', amber: 'bg-amber-100 text-amber-700',
  blue: 'bg-blue-100 text-blue-700', slate: 'bg-slate-100 text-slate-600', purple: 'bg-purple-100 text-purple-700',
}
const badgeClass = (c) => badge[c] || badge.slate
const hasFilter = computed(() => Object.values(f.value).some((v) => v !== ''))
const suspiciousIps = computed(() => props.failedByIp.filter((r) => r.suspicious))
</script>

<template>
  <AdminLayout>
    <title>Log Aktivitas - Admin TSM</title>
    <div class="space-y-6">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">Panel Kendali · TSM</span>Log Aktivitas</h1>
          <p class="text-slate-500 text-sm">Riwayat perubahan data admin dan indikator percobaan akses tidak sah. Log disimpan {{ retentionDays }} hari.</p>
        </div>
        <span v-if="loading" class="text-xs font-mono text-slate-400">memuat…</span>
      </div>

      <!-- Ringkasan -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5"><span class="mono-label">Hari ini (WIB)</span><p class="font-display text-2xl font-bold text-navy mt-1">{{ stats.today }}</p><small class="text-slate-500">aktivitas tercatat</small></div>
        <div class="card p-5" :class="{ 'ring-2 ring-red-200': stats.failed_24h > 0 }"><span class="mono-label">Login gagal 24 jam</span><p class="font-display text-2xl font-bold mt-1" :class="stats.failed_24h > 0 ? 'text-red-600' : 'text-navy'">{{ stats.failed_24h }}</p><small class="text-slate-500">{{ suspiciousIps.length }} IP perlu diperiksa</small></div>
        <div class="card p-5"><span class="mono-label">Akses ditolak 24 jam</span><p class="font-display text-2xl font-bold text-navy mt-1">{{ stats.denied_24h }}</p><small class="text-slate-500">403 / 429</small></div>
        <div class="card p-5"><span class="mono-label">Pengguna aktif 7 hari</span><p class="font-display text-2xl font-bold text-navy mt-1">{{ stats.users_7d }}</p><small class="text-slate-500">akun admin</small></div>
      </div>

      <!-- Peringatan keamanan -->
      <div v-if="suspiciousIps.length || deniedRecent.length" class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
          <div class="min-w-0 flex-1 space-y-3">
            <div>
              <h2 class="font-bold text-amber-900">Perlu diperiksa</h2>
              <p class="text-sm text-amber-800">Ada percobaan login gagal berulang dan/atau akses yang ditolak. Pastikan ini bukan aktivitas Anda sendiri.</p>
            </div>
            <div v-if="suspiciousIps.length" class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead><tr class="text-left font-mono text-[11px] uppercase tracking-wider text-amber-700"><th class="py-1 pr-4">IP</th><th class="py-1 pr-4">Percobaan</th><th class="py-1 pr-4">Akun</th><th class="py-1 pr-4">Pertama</th><th class="py-1">Terakhir</th></tr></thead>
                <tbody>
                  <tr v-for="r in suspiciousIps" :key="r.ip" class="border-t border-amber-200/70">
                    <td class="py-1.5 pr-4 font-mono text-amber-900">{{ r.ip }}</td>
                    <td class="py-1.5 pr-4 font-bold text-red-700">{{ r.attempts }}×</td>
                    <td class="py-1.5 pr-4">{{ r.accounts }}</td>
                    <td class="py-1.5 pr-4">{{ r.first_at }}</td>
                    <td class="py-1.5">{{ r.last_at }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <ul v-if="deniedRecent.length" class="text-sm text-amber-900 space-y-1">
              <li v-for="d in deniedRecent" :key="d.id"><span class="font-mono">{{ d.at }}</span> · {{ d.user_name }} · {{ d.description }} <span class="text-amber-700">({{ d.status }}, {{ d.ip }})</span></li>
            </ul>
            <p class="text-xs text-amber-700">Bila ada IP asing yang mencoba masuk: ganti password, nonaktifkan akun yang tidak dipakai, lalu blokir IP tersebut di Cloudflare (Security → WAF → Custom rules).</p>
          </div>
        </div>
      </div>

      <!-- Filter -->
      <div class="card p-5">
        <div class="grid sm:grid-cols-2 lg:grid-cols-6 gap-3">
          <div class="lg:col-span-2">
            <label class="form-label" for="act-q">Cari</label>
            <input id="act-q" v-model="f.q" type="search" placeholder="keterangan, nama, IP, atau path" class="form-input text-sm" />
          </div>
          <div>
            <label class="form-label" for="act-aksi">Aksi</label>
            <select id="act-aksi" v-model="f.aksi" class="form-input text-sm">
              <option value="">Semua</option>
              <option value="security">⚠ Semua peristiwa keamanan</option>
              <option v-for="a in actions" :key="a.value" :value="a.value">{{ a.label }}</option>
            </select>
          </div>
          <div>
            <label class="form-label" for="act-modul">Modul</label>
            <select id="act-modul" v-model="f.modul" class="form-input text-sm">
              <option value="">Semua</option>
              <option v-for="(label, key) in modules" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
          <div>
            <label class="form-label" for="act-user">Pengguna</label>
            <select id="act-user" v-model="f.user" class="form-input text-sm">
              <option value="">Semua</option>
              <option value="sistem">Sistem / tamu</option>
              <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
            </select>
          </div>
          <div class="flex items-end gap-2">
            <div class="flex-1">
              <label class="form-label" for="act-dari">Dari</label>
              <input id="act-dari" v-model="f.dari" type="date" class="form-input text-sm" />
            </div>
            <div class="flex-1">
              <label class="form-label" for="act-sampai">Sampai</label>
              <input id="act-sampai" v-model="f.sampai" type="date" class="form-input text-sm" />
            </div>
          </div>
        </div>
        <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
          <span>{{ logs.total }} baris cocok</span>
          <button v-if="hasFilter" type="button" class="font-semibold text-label hover:underline" @click="reset">Reset filter</button>
        </div>
      </div>

      <!-- Tabel log -->
      <div class="card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left font-mono text-[11px] uppercase tracking-wider text-slate-500">
              <tr>
                <th class="px-4 py-3">Waktu (WIB)</th>
                <th class="px-4 py-3">Aksi</th>
                <th class="px-4 py-3">Pengguna</th>
                <th class="px-4 py-3">Keterangan</th>
                <th class="px-4 py-3">IP</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="l in logs.data" :key="l.id" class="border-t border-slate-100 align-top" :class="{ 'bg-red-50/40': l.is_security }">
                <td class="px-4 py-3 font-mono text-xs text-slate-600 whitespace-nowrap">{{ l.at_wib }}</td>
                <td class="px-4 py-3"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" :class="badgeClass(l.action_color)">{{ l.action_label }}</span></td>
                <td class="px-4 py-3">
                  <b class="text-slate-800">{{ l.user_name }}</b>
                  <small v-if="l.role" class="block text-slate-400 font-mono text-[11px] uppercase">{{ l.role }}</small>
                </td>
                <td class="px-4 py-3 text-slate-700">
                  <span class="font-mono text-[11px] uppercase tracking-wider text-slate-400 mr-2">{{ l.module_label }}</span>{{ l.description }}
                  <div v-if="l.changed && l.changed.length" class="text-xs text-slate-500 mt-1">Isian diubah: <span class="font-mono">{{ l.changed.join(', ') }}</span></div>
                  <div v-if="l.message" class="text-xs text-slate-400 mt-0.5">{{ l.message }}</div>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-slate-500 whitespace-nowrap">{{ l.ip }}</td>
              </tr>
              <tr v-if="!logs.data.length"><td colspan="5" class="px-4 py-12 text-center text-slate-500">Tidak ada aktivitas pada filter ini.</td></tr>
            </tbody>
          </table>
        </div>
        <nav v-if="logs.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t border-slate-100" aria-label="Halaman log">
          <span class="text-xs text-slate-500">Halaman {{ logs.current_page }} dari {{ logs.last_page }}</span>
          <div class="flex flex-wrap gap-1">
            <template v-for="(l, i) in logs.links" :key="i">
              <button v-if="l.url" type="button" class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 bg-white hover:bg-slate-50" :class="{ 'bg-navy text-white border-navy': l.active }" @click="router.get(l.url, {}, { preserveScroll: true, preserveState: true })" v-html="l.label"></button>
              <span v-else class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-300" v-html="l.label"></span>
            </template>
          </div>
        </nav>
      </div>

      <p class="text-xs text-slate-400">Log otomatis dibersihkan tiap pukul 03.00 WIB (retensi {{ retentionDays }} hari). Atur lewat <span class="font-mono">ACTIVITY_LOG_RETENTION_DAYS</span>.</p>
    </div>
  </AdminLayout>
</template>

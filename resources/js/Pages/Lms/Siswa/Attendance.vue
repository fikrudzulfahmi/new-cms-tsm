<script setup>
import { reactive, ref, onBeforeUnmount } from 'vue'
import { useForm } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ sessions: Array, statuses: Object })
const forms = reactive({})
const f = (id) => (forms[id] ??= useForm({ status: 'hadir', note: '', code: '', selfie: null, lat: null, lng: null, accuracy: null }))
const fmt = (v) => v.replace('T', ' ')
const color = { hadir: 'text-emerald-700', terlambat: 'text-amber-600', izin: 'text-sky-700', sakit: 'text-sky-700', alpa: 'text-red-600' }

// Selfie + lokasi (hanya untuk sesi yang mewajibkan)
const proof = reactive({})
const camId = ref(null)
const video = ref(null)
let stream = null
const stopCam = () => { stream?.getTracks().forEach((t) => t.stop()); stream = null; camId.value = null }
onBeforeUnmount(stopCam)
const p = (id) => (proof[id] ??= { preview: null, loc: null, err: '', busy: false })

const getLoc = () => new Promise((ok, no) => {
  if (!navigator.geolocation) return no(new Error('Browser tidak mendukung lokasi.'))
  navigator.geolocation.getCurrentPosition(ok, (e) => no(new Error(e.code === 1 ? 'Izin lokasi ditolak. Aktifkan izin lokasi untuk situs ini di pengaturan browser, lalu coba lagi.' : 'Lokasi tidak terbaca. Pastikan GPS aktif lalu coba lagi.')), { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 })
})

async function openCam(id) {
  const st = p(id); st.err = ''; st.busy = true
  try {
    const pos = await getLoc() // lokasi dulu: bila ditolak, kamera tidak dibuka
    st.loc = { lat: pos.coords.latitude, lng: pos.coords.longitude, acc: pos.coords.accuracy }
    if (!navigator.mediaDevices?.getUserMedia) throw new Error('Browser tidak mendukung kamera. Gunakan browser terbaru.')
    stopCam()
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 960 } }, audio: false }).catch(() => { throw new Error('Izin kamera ditolak. Aktifkan izin kamera untuk situs ini, lalu coba lagi.') })
    camId.value = id
    await new Promise((r) => setTimeout(r, 50))
    video.value.srcObject = stream
    await video.value.play()
  } catch (e) { st.err = e.message; stopCam() } finally { st.busy = false }
}

function snap(id) {
  const v = video.value
  const c = document.createElement('canvas')
  c.width = v.videoWidth; c.height = v.videoHeight
  c.getContext('2d').drawImage(v, 0, 0)
  c.toBlob((b) => {
    const st = p(id)
    if (st.preview) URL.revokeObjectURL(st.preview)
    st.preview = URL.createObjectURL(b)
    f(id).selfie = new File([b], 'selfie.jpg', { type: 'image/jpeg' })
    stopCam()
  }, 'image/jpeg', 0.85)
}

function submit(s) {
  const fm = f(s.id), st = p(s.id)
  if (s.needs_proof && fm.status === 'hadir') {
    if (!fm.selfie || !st.loc) { st.err = 'Ambil selfie dan aktifkan lokasi dulu.'; return }
    Object.assign(fm, { lat: st.loc.lat, lng: st.loc.lng, accuracy: Math.round(st.loc.acc) })
  }
  fm.post(`/belajar/presensi/${s.id}`, { preserveScroll: true, forceFormData: true, onSuccess: () => { st.preview = null; st.loc = null; fm.selfie = null } })
}
</script>

<template>
  <LmsShell title="Presensi">
    <p v-if="!sessions.length" class="text-sm text-slate-500 bg-white rounded-2xl border p-6">Belum ada presensi untuk kelas Anda.</p>
    <div v-for="s in sessions" :key="s.id" class="bg-white rounded-2xl border border-slate-200 p-4 mb-3">
      <div class="flex flex-wrap items-center gap-2">
        <h2 class="font-semibold text-navy flex-1">{{ s.title }}</h2>
        <span v-if="s.status" class="text-sm font-semibold" :class="color[s.status]">{{ statuses[s.status] }}</span>
      </div>
      <p class="text-xs text-slate-500">{{ fmt(s.opens_at) }} &rarr; {{ fmt(s.closes_at) }} WIB &middot; {{ s.teacher }}</p>
      <p v-if="s.description" class="text-sm text-slate-600 mt-1 whitespace-pre-line">{{ s.description }}</p>
      <p v-if="s.note" class="text-xs text-slate-500 mt-1">Keterangan: {{ s.note }}</p>
      <p v-if="s.state==='soon'" class="text-xs text-slate-400 mt-2">Belum dibuka.</p>
      <p v-else-if="s.locked" class="text-xs text-slate-500 mt-2">Status diatur oleh guru.</p>
      <form v-else-if="s.state==='open'" class="mt-3 flex flex-wrap items-end gap-2" @submit.prevent="submit(s)">
        <select v-model="f(s.id).status" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
          <option value="hadir">Hadir</option><option value="izin">Izin</option><option value="sakit">Sakit</option>
        </select>
        <input v-if="f(s.id).status!=='hadir'" v-model="f(s.id).note" placeholder="Keterangan" maxlength="255" class="rounded-xl border border-slate-300 px-3 py-2 text-sm flex-1 min-w-[10rem]" />
        <input v-if="s.needs_code" v-model="f(s.id).code" placeholder="Kode dari guru" maxlength="20" autocomplete="off" class="rounded-xl border border-slate-300 px-3 py-2 text-sm w-40" />

        <div v-if="s.needs_proof && f(s.id).status==='hadir'" class="w-full rounded-xl bg-slate-50 border border-slate-200 p-3">
          <p class="text-xs text-slate-600 mb-2">Presensi ini wajib <b>selfie</b> dan <b>lokasi</b>. Izinkan akses lokasi dan kamera saat diminta browser.</p>
          <div v-if="camId===s.id" class="space-y-2">
            <video ref="video" playsinline muted class="w-full max-w-xs rounded-xl bg-black -scale-x-100"></video>
            <div class="flex gap-2">
              <button type="button" class="rounded-xl bg-accent text-navy font-bold text-sm px-4 py-2" @click="snap(s.id)">Ambil foto</button>
              <button type="button" class="text-sm text-slate-500" @click="stopCam">Batal</button>
            </div>
          </div>
          <div v-else class="flex flex-wrap items-center gap-3">
            <img v-if="p(s.id).preview" :src="p(s.id).preview" alt="Selfie" class="h-20 w-20 rounded-xl object-cover border -scale-x-100" />
            <button type="button" class="rounded-xl border border-navy text-navy font-semibold text-sm px-4 py-2 disabled:opacity-50" :disabled="p(s.id).busy" @click="openCam(s.id)">
              {{ p(s.id).busy ? 'Memeriksa…' : (p(s.id).preview ? 'Ulangi selfie' : 'Mulai selfie & lokasi') }}
            </button>
            <span v-if="p(s.id).loc" class="text-xs text-emerald-700">Lokasi terdeteksi (±{{ Math.round(p(s.id).loc.acc) }} m)</span>
          </div>
          <p v-if="p(s.id).err" class="text-red-600 text-xs mt-2">{{ p(s.id).err }}</p>
        </div>

        <button class="rounded-xl bg-accent text-navy font-bold text-sm px-4 py-2" :disabled="f(s.id).processing">{{ s.status ? 'Ubah' : 'Kirim' }}</button>
        <p v-for="(e, k) in f(s.id).errors" :key="k" class="text-red-600 text-xs w-full">{{ e }}</p>
      </form>
    </div>
  </LmsShell>
</template>

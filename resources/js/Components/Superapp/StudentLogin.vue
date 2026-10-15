<script setup>
// Login siswa: NIS + tanggal lahir. NIS bisa diketik, di-scan pakai kamera (QR/barcode berisi
// 5 angka pertama NIS), atau pakai scanner USB (berperilaku seperti keyboard + Enter).
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({ recaptchaKey: String })
const form = useForm({ nis: '', birth_date: '', captcha: '' })

const day = ref(''), month = ref(''), year = ref('')
const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
const thisYear = new Date().getFullYear()
const years = Array.from({ length: 30 }, (_, i) => thisYear - 10 - i)
const birthDate = computed(() => day.value && month.value && year.value
  ? `${year.value}-${String(month.value).padStart(2, '0')}-${String(day.value).padStart(2, '0')}` : '')

const nisInput = ref(null), dayInput = ref(null)
const scanned = ref(false)

// ---- reCAPTCHA v2 checkbox ----
const captchaBox = ref(null)
let widgetId = null
function renderCaptcha() {
  if (!props.recaptchaKey || !captchaBox.value || !window.grecaptcha?.render) return
  widgetId = window.grecaptcha.render(captchaBox.value, { sitekey: props.recaptchaKey })
}
onMounted(() => {
  if (!props.recaptchaKey) return
  if (window.grecaptcha?.render) return renderCaptcha()
  window.__onRecaptcha = renderCaptcha
  if (!document.getElementById('recaptcha-js')) {
    const s = document.createElement('script')
    s.id = 'recaptcha-js'; s.async = true; s.defer = true
    s.src = 'https://www.google.com/recaptcha/api.js?onload=__onRecaptcha&render=explicit&hl=id'
    document.head.appendChild(s)
  }
})

// ---- Scan kamera ----
const scanning = ref(false)
const scanError = ref('')
let scanner = null
function applyScan(text) {
  const digits = (String(text).match(/\d+/) || [''])[0]
  if (digits.length < 5) { scanError.value = 'Kode tidak dikenali (butuh minimal 5 angka).'; return false }
  form.nis = digits.slice(0, 5)
  scanned.value = true
  form.clearErrors('nis')
  nextTick(() => dayInput.value?.focus())
  return true
}
async function startScan() {
  scanError.value = ''
  scanning.value = true
  await nextTick()
  try {
    const { Html5Qrcode } = await import('html5-qrcode')
    scanner = new Html5Qrcode('qr-reader', { verbose: false })
    await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 220, height: 220 } },
      (text) => { if (applyScan(text)) stopScan() }, () => {})
  } catch (e) {
    scanError.value = window.isSecureContext
      ? 'Kamera tidak bisa dibuka. Izinkan akses kamera atau ketik NIS manual.'
      : 'Kamera hanya bisa dipakai lewat HTTPS. Ketik NIS manual.'
    scanning.value = false
  }
}
async function stopScan() {
  try { if (scanner?.isScanning) await scanner.stop(); scanner?.clear() } catch (_) {}
  scanner = null; scanning.value = false
}
onBeforeUnmount(stopScan)

function onNisEnter() { if (form.nis.length >= 5) { scanned.value = false; dayInput.value?.focus() } }
// NIS di Data Induk berbentuk 31577/1582/004; yang dipakai login = 5 angka pertama, jadi tanda / tetap diterima.
function onNisInput(e) { form.nis = e.target.value.replace(/[^0-9/.\-]/g, '').slice(0, 20); scanned.value = false }

function submit() {
  form.birth_date = birthDate.value
  if (props.recaptchaKey) {
    form.captcha = window.grecaptcha?.getResponse(widgetId) || ''
    if (!form.captcha) { form.setError('captcha', 'Centang "Saya bukan robot" dulu.'); return }
  }
  form.post('/masuk/siswa', {
    onFinish: () => { if (props.recaptchaKey && window.grecaptcha) window.grecaptcha.reset(widgetId) },
  })
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-5">
    <div>
      <label class="form-label">NIS <span class="font-normal text-slate-400">— 5 angka pertama pada kartu siswa</span></label>
      <div class="flex gap-2">
        <input ref="nisInput" :value="form.nis" @input="onNisInput" @keydown.enter.prevent="onNisEnter"
          inputmode="text" autocomplete="username" placeholder="5 angka NIS atau scan kartu" class="form-input flex-1 font-mono tracking-wider"
          :class="{ 'border-red-400 ring-red-200': form.errors.nis }" required autofocus />
        <button type="button" @click="scanning ? stopScan() : startScan()" class="shrink-0 px-3 rounded-xl border border-line text-slate-600 hover:border-label hover:text-label" :aria-label="scanning ? 'Tutup kamera' : 'Scan kartu'" title="Scan kartu dengan kamera">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7V5a1 1 0 011-1h2M17 4h2a1 1 0 011 1v2M20 17v2a1 1 0 01-1 1h-2M7 20H5a1 1 0 01-1-1v-2M7 12h10M8 8h1M8 16h1m6-8h1m-1 8h1" /></svg>
        </button>
      </div>
      <p v-if="scanned" class="text-emerald-700 text-xs mt-1">✓ Kartu terbaca. Sekarang isi tanggal lahir.</p>
      <p v-if="form.errors.nis" class="text-red-500 text-xs mt-1">{{ form.errors.nis }}</p>
      <div v-show="scanning" class="mt-3 rounded-2xl overflow-hidden border border-line bg-black">
        <div id="qr-reader" class="w-full"></div>
        <button type="button" @click="stopScan" class="w-full py-2 text-sm text-white/80 bg-navy">Tutup kamera</button>
      </div>
      <p v-if="scanError" class="text-amber-700 text-xs mt-1">{{ scanError }}</p>
    </div>

    <div>
      <label class="form-label">Tanggal Lahir</label>
      <div class="grid grid-cols-[1fr_1.6fr_1.2fr] gap-2">
        <select ref="dayInput" v-model="day" class="form-input" required aria-label="Tanggal">
          <option value="">Tgl</option>
          <option v-for="d in 31" :key="d" :value="d">{{ d }}</option>
        </select>
        <select v-model="month" class="form-input" required aria-label="Bulan">
          <option value="">Bulan</option>
          <option v-for="(m, i) in months" :key="m" :value="i + 1">{{ m }}</option>
        </select>
        <select v-model="year" class="form-input" required aria-label="Tahun">
          <option value="">Tahun</option>
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
      <p v-if="form.errors.birth_date" class="text-red-500 text-xs mt-1">Tanggal lahir tidak valid.</p>
    </div>

    <div v-if="recaptchaKey">
      <div ref="captchaBox"></div>
      <p v-if="form.errors.captcha" class="text-red-500 text-xs mt-1">{{ form.errors.captcha }}</p>
    </div>

    <button type="submit" :disabled="form.processing" class="w-full btn-primary justify-center py-3 disabled:opacity-50">
      {{ form.processing ? 'Masuk…' : 'Masuk' }}
    </button>
  </form>
</template>

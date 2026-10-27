<script setup>
import { confirmDialog } from '@/dialog'
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
const props = defineProps({ quiz: Object, attempt: Object, items: Array })
const csrf = () => decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '')
const hdr = () => ({ 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': csrf() })
const answers = reactive(Object.fromEntries(props.items.map(i => [i.id, i.answer ?? (i.type === 'pg_kompleks' ? { options: [] } : i.type === 'menjodohkan' ? { pairs: {} } : i.type === 'pg' || i.type === 'benar_salah' ? {} : { text: '' })])))
const left = ref(props.attempt.seconds_left)
const cur = ref(0)
const status = ref('')
const done = ref(false)
let timer, saveTimers = {}
const item = computed(() => props.items[cur.value])
const mmss = computed(() => `${String(Math.floor(left.value / 60)).padStart(2, '0')}:${String(left.value % 60).padStart(2, '0')}`)

async function save(id) {
  status.value = 'Menyimpan...'
  try {
    const r = await fetch(`/belajar/pengerjaan/${props.attempt.id}/soal/${id}`, { method: 'PUT', headers: hdr(), body: JSON.stringify({ answer: answers[id] }) })
    if (r.status === 409) { done.value = true; router.visit(`/belajar/pengerjaan/${props.attempt.id}/hasil`); return }
    status.value = r.ok ? 'Tersimpan' : 'Gagal menyimpan — periksa koneksi'
  } catch { status.value = 'Gagal menyimpan — periksa koneksi' }
}
function changed(id, delay = 600) { clearTimeout(saveTimers[id]); saveTimers[id] = setTimeout(() => save(id), delay) }
function toggle(id, oid) {
  const a = answers[id].options; const i = a.indexOf(oid); i >= 0 ? a.splice(i, 1) : a.push(oid); changed(id, 200)
}
function pick(id, oid) { answers[id] = { option: oid }; changed(id, 100) }
function pair(id, l, r) { const p = answers[id].pairs; r ? (p[l] = r) : delete p[l]; changed(id, 200) }
const answered = (i) => { const a = answers[i.id]; return !!(a.option || a.options?.length || a.text?.trim() || (a.pairs && Object.keys(a.pairs).length)) }

async function flush() { for (const id of Object.keys(saveTimers)) { clearTimeout(saveTimers[id]); await save(id) } saveTimers = {} }
async function submit() {
  if (!await confirmDialog('Kumpulkan jawaban sekarang?')) return
  await flush(); done.value = true
  router.post(`/belajar/pengerjaan/${props.attempt.id}/kumpul`)
}
function tabEvent() {
  if (document.hidden && !done.value) fetch(`/belajar/pengerjaan/${props.attempt.id}/tab`, { method: 'POST', headers: hdr(), keepalive: true })
}
onMounted(() => {
  timer = setInterval(async () => {
    left.value = Math.max(0, left.value - 1)
    if (left.value === 0 && !done.value) { done.value = true; await flush(); router.post(`/belajar/pengerjaan/${props.attempt.id}/kumpul`) }
  }, 1000)
  document.addEventListener('visibilitychange', tabEvent)
})
onBeforeUnmount(() => { clearInterval(timer); document.removeEventListener('visibilitychange', tabEvent) })
</script>

<template>
  <div class="min-h-screen bg-slate-50 select-none">
    <header class="sticky top-0 z-10 bg-navy text-white px-4 py-2 flex items-center gap-3">
      <b class="flex-1 truncate">{{ quiz.title }}</b>
      <span class="text-xs text-white/70">{{ status }}</span>
      <span class="font-mono text-lg px-2 rounded" :class="left < 60 ? 'bg-red-600' : 'bg-white/10'">{{ mmss }}</span>
    </header>
    <main class="max-w-3xl mx-auto p-4">
      <div class="flex flex-wrap gap-1 mb-4">
        <button v-for="(it, i) in items" :key="it.id" class="w-8 h-8 rounded-lg text-xs border" :class="[i === cur ? 'ring-2 ring-navy' : '', answered(it) ? 'bg-accent border-accent text-navy font-semibold' : 'bg-white border-slate-300']" @click="cur = i">{{ i + 1 }}</button>
      </div>
      <section v-if="item" class="bg-white rounded-2xl border border-slate-200 p-5">
        <p class="text-xs text-slate-400 mb-1">Soal {{ cur + 1 }} dari {{ items.length }} · {{ item.points }} poin</p>
        <p class="whitespace-pre-line mb-4">{{ item.body }}</p>

        <div v-if="['pg','benar_salah'].includes(item.type)" class="space-y-2">
          <label v-for="o in item.options" :key="o.id" class="flex gap-2 rounded-xl border px-3 py-2 cursor-pointer" :class="answers[item.id].option === o.id ? 'border-navy bg-slate-50' : 'border-slate-200'">
            <input type="radio" :checked="answers[item.id].option === o.id" @change="pick(item.id, o.id)" /> <span class="text-sm">{{ o.body }}</span>
          </label>
        </div>
        <div v-else-if="item.type === 'pg_kompleks'" class="space-y-2">
          <p class="text-xs text-slate-500">Pilih semua jawaban yang benar.</p>
          <label v-for="o in item.options" :key="o.id" class="flex gap-2 rounded-xl border border-slate-200 px-3 py-2 cursor-pointer">
            <input type="checkbox" :checked="answers[item.id].options.includes(o.id)" @change="toggle(item.id, o.id)" /> <span class="text-sm">{{ o.body }}</span>
          </label>
        </div>
        <div v-else-if="item.type === 'menjodohkan'" class="space-y-2">
          <div v-for="l in item.lefts" :key="l.id" class="flex items-center gap-2 text-sm">
            <span class="flex-1">{{ l.body }}</span>
            <select class="rounded-xl border border-slate-300 px-2 py-1.5 max-w-[50%]" :value="answers[item.id].pairs[l.id] || ''" @change="pair(item.id, l.id, $event.target.value)">
              <option value="">— pilih —</option><option v-for="r in item.rights" :key="r.id" :value="r.id">{{ r.body }}</option>
            </select>
          </div>
        </div>
        <input v-else-if="item.type === 'isian'" v-model="answers[item.id].text" maxlength="500" autocomplete="off" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm select-text" @input="changed(item.id)" />
        <textarea v-else v-model="answers[item.id].text" rows="6" maxlength="5000" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm select-text" @input="changed(item.id, 1000)"></textarea>

        <div class="flex justify-between mt-5">
          <button class="rounded-xl border border-slate-300 px-4 py-2 text-sm disabled:opacity-40" :disabled="cur === 0" @click="cur--">Sebelumnya</button>
          <button v-if="cur < items.length - 1" class="rounded-xl bg-navy text-white px-4 py-2 text-sm" @click="cur++">Berikutnya</button>
          <button v-else class="rounded-xl bg-accent text-navy font-semibold px-4 py-2 text-sm" @click="submit">Kumpulkan</button>
        </div>
      </section>
      <button class="mt-4 text-sm text-navy underline" @click="submit">Kumpulkan sekarang</button>
    </main>
  </div>
</template>

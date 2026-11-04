<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue'
import '../../../css/landing.css'

// ---- Geometri mesin (satuan SVG) ----
const VB_W = 460
const VB_H = 480
const CX = 230            // sumbu silinder
const CRANK = { x: 230, y: 376 }
const RC = 46             // radius putar engkol
const ROD = 150           // panjang stang piston
const DECK = 126          // dasar head / TMA
const BORE_L = 168
const BORE_R = 292
const PISTON_H = 62
const CYCLE = 720         // 4 tak = 2 putaran engkol
const SPEED = 110         // °/detik

const STROKES = [
  { key: 'hisap', label: 'Hisap', note: 'Klep masuk terbuka, piston turun menghisap campuran bahan bakar & udara.' },
  { key: 'kompresi', label: 'Kompresi', note: 'Kedua klep tertutup, piston naik memampatkan campuran.' },
  { key: 'usaha', label: 'Usaha', note: 'Busi memercikkan api, ledakan mendorong piston turun.' },
  { key: 'buang', label: 'Buang', note: 'Klep buang terbuka, piston naik mendorong gas sisa keluar.' },
]

const cycle = ref(0)
const paused = ref(false)
const dragging = ref(false)

let raf = null
let last = 0
let drag = null

// ---- Posisi mekanik ----
const mech = computed(() => {
  const th = ((-90 + (cycle.value % 360)) * Math.PI) / 180
  const px = CRANK.x + RC * Math.cos(th)
  const py = CRANK.y + RC * Math.sin(th)
  const dx = px - CX
  const pinY = py - Math.sqrt(ROD * ROD - dx * dx)
  return { px, py, pinY, crown: pinY - PISTON_H / 2, crankDeg: cycle.value % 360 }
})

const phase = computed(() => Math.min(3, Math.max(0, Math.floor(((cycle.value % CYCLE) + CYCLE) % CYCLE / 180))))
const active = computed(() => STROKES[phase.value] || STROKES[0])

const crown = computed(() => mech.value.crown)
const gasTop = computed(() => DECK + 2)
const gasH = computed(() => Math.max(0, crown.value - gasTop.value))

// ---- Buka-tutup klep (durasi 220°, overlap kecil) ----
function bump(c, start, dur) {
  const t = (((c - start) % CYCLE) + CYCLE) % CYCLE
  return t <= dur ? Math.sin((Math.PI * t) / dur) : 0
}
const intakeLift = computed(() => bump(cycle.value, -20, 220))
const exhaustLift = computed(() => bump(cycle.value, 520, 220))

const ignition = computed(() => {
  const c = cycle.value % CYCLE
  return c > 350 && c < 400 ? 1 - Math.abs(c - 370) / 30 : 0
})

const gasFill = computed(() => {
  switch (phase.value) {
    case 0: return { color: '#7CC8FF', op: 0.22 }
    case 1: return { color: '#3E8ED0', op: 0.42 }
    case 2: return { color: '#F59E0B', op: 0.5 }
    default: return { color: '#94A3B8', op: 0.3 }
  }
})

const caption = computed(() => active.value.note)

// ---- Loop ----
function frame(now) {
  if (!last) last = now
  const dt = Math.min(0.05, (now - last) / 1000)
  last = now
  if (!dragging.value && !paused.value && !document.hidden && Number.isFinite(dt)) {
    cycle.value = (cycle.value + SPEED * dt) % CYCLE
  }
  raf = requestAnimationFrame(frame)
}

// ---- Drag engkol ----
const svgRef = ref(null)

function toLocal(e) {
  const r = svgRef.value.getBoundingClientRect()
  return [((e.clientX - r.left) * VB_W) / r.width, ((e.clientY - r.top) * VB_H) / r.height]
}
const norm = (d) => ((d + 540) % 360) - 180

function onDown(e) {
  e.preventDefault()
  svgRef.value.setPointerCapture(e.pointerId)
  const [x, y] = toLocal(e)
  drag = { ang: (Math.atan2(y - CRANK.y, x - CRANK.x) * 180) / Math.PI }
  dragging.value = true
}

function onMove(e) {
  if (!drag) return
  const [x, y] = toLocal(e)
  const ang = (Math.atan2(y - CRANK.y, x - CRANK.x) * 180) / Math.PI
  const d = norm(ang - drag.ang)
  cycle.value = ((cycle.value + d) % CYCLE + CYCLE) % CYCLE
  drag.ang = ang
}

function onUp(e) {
  if (!drag) return
  try { svgRef.value.releasePointerCapture(e.pointerId) } catch {}
  drag = null
  dragging.value = false
}

function onKey(e) {
  if (e.key === 'ArrowRight') { e.preventDefault(); cycle.value = (cycle.value + 12) % CYCLE }
  if (e.key === 'ArrowLeft') { e.preventDefault(); cycle.value = ((cycle.value - 12) + CYCLE) % CYCLE }
}

function jump(i) {
  cycle.value = i * 180 + 4
  paused.value = true
}

onMounted(() => {
  if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) paused.value = true
  raf = requestAnimationFrame(frame)
})

onBeforeUnmount(() => {
  if (raf !== null) cancelAnimationFrame(raf)
  raf = null
})
</script>

<template>
  <div class="gear-wrap engine-wrap">
    <svg
      ref="svgRef"
      class="gear-svg"
      :class="{ 'is-dragging': dragging }"
      :viewBox="`0 0 ${VB_W} ${VB_H}`"
      role="img"
      aria-label="Animasi cara kerja motor 4 tak: putar engkol untuk menjalankan siklus"
      tabindex="0"
      @pointerdown="onDown"
      @pointermove="onMove"
      @pointerup="onUp"
      @pointercancel="onUp"
      @keydown="onKey"
    >
      <defs>
        <linearGradient id="steel" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stop-color="#3C5C93" />
          <stop offset="0.5" stop-color="#24406F" />
          <stop offset="1" stop-color="#142A50" />
        </linearGradient>
        <linearGradient id="pistonFace" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0" stop-color="#8FA8CC" />
          <stop offset="0.45" stop-color="#D8E4F4" />
          <stop offset="1" stop-color="#6C86AC" />
        </linearGradient>
        <radialGradient id="flame" cx="0.5" cy="0.6" r="0.6">
          <stop offset="0" stop-color="#FFE0A3" stop-opacity="0.95" />
          <stop offset="0.55" stop-color="#F59E0B" stop-opacity="0.6" />
          <stop offset="1" stop-color="#F59E0B" stop-opacity="0" />
        </radialGradient>
      </defs>

      <!-- Crankcase -->
      <circle :cx="CRANK.x" :cy="CRANK.y" r="92" fill="var(--navy-panel)" stroke="rgba(124,200,255,.22)" stroke-width="2" />
      <circle :cx="CRANK.x" :cy="CRANK.y" r="80" fill="none" stroke="rgba(124,200,255,.14)" stroke-dasharray="2 6" />

      <!-- Sirip pendingin silinder -->
      <g fill="var(--navy-panel)" stroke="rgba(124,200,255,.16)">
        <rect v-for="i in 8" :key="'finL' + i" :x="BORE_L - 20" :y="134 + i * 17" width="20" height="7" rx="2" />
        <rect v-for="i in 8" :key="'finR' + i" :x="BORE_R" :y="134 + i * 17" width="20" height="7" rx="2" />
      </g>

      <!-- Blok head -->
      <rect :x="BORE_L - 24" y="82" :width="BORE_R - BORE_L + 48" height="46" rx="8"
            fill="#12285A" stroke="rgba(124,200,255,.28)" stroke-width="1.5" />

      <!-- Dinding silinder (bagian dalam) -->
      <rect :x="BORE_L" :y="DECK - 2" :width="BORE_R - BORE_L" height="188" fill="#061027" stroke="none" rx="3" />

      <!-- Gas di ruang bakar -->
      <rect :x="BORE_L + 2" :y="gasTop" :width="BORE_R - BORE_L - 4" :height="gasH"
            :fill="gasFill.color" :fill-opacity="gasFill.op" />

      <!-- Percikan api -->
      <circle v-if="ignition > 0.02" :cx="CX" :cy="crown - 6" r="34" fill="url(#flame)" :opacity="ignition" />

      <!-- Piston -->
      <g>
        <rect :x="BORE_L + 4" :y="crown" :width="BORE_R - BORE_L - 8" :height="PISTON_H" rx="5"
              fill="url(#pistonFace)" stroke="#0A1A3A" stroke-width="1.5" />
        <line v-for="i in 3" :key="'ring' + i"
              :x1="BORE_L + 4" :x2="BORE_R - 4" :y1="crown + 8 + i * 6" :y2="crown + 8 + i * 6"
              stroke="#12285A" stroke-width="2" stroke-opacity=".55" />
        <circle :cx="CX" :cy="mech.pinY" r="7" fill="#12285A" stroke="#7CC8FF" stroke-width="1.5" />
      </g>

      <!-- Dinding silinder (garis luarnya) -->
      <path :d="`M ${BORE_L} ${DECK} L ${BORE_L} 314 M ${BORE_R} ${DECK} L ${BORE_R} 314`"
            stroke="var(--accent)" stroke-opacity=".55" stroke-width="5" stroke-linecap="round" fill="none" />

      <!-- Stang piston -->
      <line :x1="CX" :y1="mech.pinY" :x2="mech.px" :y2="mech.py"
            stroke="#C7D6EC" stroke-width="11" stroke-linecap="round" />
      <line :x1="CX" :y1="mech.pinY" :x2="mech.px" :y2="mech.py"
            stroke="#7CC8FF" stroke-width="3" stroke-linecap="round" stroke-opacity=".8" />

      <!-- Poros engkol -->
      <g>
        <circle :cx="CRANK.x" :cy="CRANK.y" r="54" fill="none" stroke="rgba(124,200,255,.25)" stroke-width="1.5" />
        <circle :cx="CRANK.x - 46 * Math.cos(mech.crankDeg * Math.PI / 180)"
                :cy="CRANK.y - 46 * Math.sin(mech.crankDeg * Math.PI / 180)"
                r="26" fill="#0E1F42" stroke="rgba(124,200,255,.3)" stroke-width="1.5" />
        <line :x1="CRANK.x" :y1="CRANK.y" :x2="mech.px" :y2="mech.py"
              stroke="url(#steel)" stroke-width="16" stroke-linecap="round" />
        <circle :cx="CRANK.x" :cy="CRANK.y" r="20" fill="#0A1A3A" stroke="#7CC8FF" stroke-width="2" />
        <circle :cx="CRANK.x" :cy="CRANK.y" r="6" fill="#7CC8FF" />
        <circle :cx="mech.px" :cy="mech.py" r="9" fill="#0A1A3A" stroke="#7CC8FF" stroke-width="2" />
      </g>

      <!-- Klep hisap (kiri) -->
      <g>
        <path :d="`M 205 90 L 205 ${DECK - 26 - intakeLift * 9}`" stroke="#8FA8CC" stroke-width="5" stroke-linecap="round" />
        <ellipse :cx="205" :cy="DECK - 4 - intakeLift * 9" rx="17" ry="6"
                 fill="#C7D6EC" stroke="#0A1A3A" stroke-width="1.5" />
        <path :d="`M 195 96 q 10 -8 20 0 q -10 8 -20 0 M 195 108 q 10 -8 20 0 q -10 8 -20 0`"
              fill="none" stroke="rgba(124,200,255,.5)" stroke-width="1.5"
              :transform="`translate(0 ${-intakeLift * 4})`" />
      </g>

      <!-- Klep buang (kanan) -->
      <g>
        <path :d="`M 255 90 L 255 ${DECK - 26 - exhaustLift * 9}`" stroke="#8FA8CC" stroke-width="5" stroke-linecap="round" />
        <ellipse :cx="255" :cy="DECK - 4 - exhaustLift * 9" rx="17" ry="6"
                 fill="#C7D6EC" stroke="#0A1A3A" stroke-width="1.5" />
        <path :d="`M 245 96 q 10 -8 20 0 q -10 8 -20 0 M 245 108 q 10 -8 20 0 q -10 8 -20 0`"
              fill="none" stroke="rgba(255,255,255,.35)" stroke-width="1.5"
              :transform="`translate(0 ${-exhaustLift * 4})`" />
      </g>

      <!-- Busi -->
      <g>
        <rect :x="CX - 6" y="60" width="12" height="30" rx="2" fill="#12285A" stroke="rgba(255,255,255,.3)" stroke-width="1.2" />
        <rect :x="CX - 9" y="86" width="18" height="8" rx="2" fill="#24406F" />
        <line :x1="CX" :y1="94" :x2="CX" :y2="DECK - 6" stroke="#C7D6EC" stroke-width="3" />
        <circle v-if="ignition > 0.02" :cx="CX" :cy="DECK - 4" r="5" fill="#FFE0A3" :opacity="ignition" />
      </g>

      <!-- Aliran hisap / buang -->
      <path v-if="intakeLift > 0.2" :d="`M 150 106 L 192 106`" stroke="#7CC8FF" stroke-width="3"
            stroke-linecap="round" :opacity="intakeLift * 0.9" />
      <path v-if="exhaustLift > 0.2" :d="`M 268 106 L 310 106`" stroke="#94A3B8" stroke-width="3"
            stroke-linecap="round" :opacity="exhaustLift * 0.9" />
    </svg>

    <div class="stroke-tabs" role="tablist" aria-label="Fase langkah kerja">
      <button
        v-for="(s, i) in STROKES"
        :key="s.key"
        type="button"
        role="tab"
        :aria-selected="phase === i"
        :class="{ on: phase === i }"
        @click="jump(i)"
      >{{ i + 1 }}. {{ s.label }}</button>
    </div>

    <p class="engine-note">{{ caption }}</p>

    <div class="gear-foot">
      <span class="mono-caption">PUTAR ENGKOL UNTUK MENJALANKAN SIKLUS · 720°</span>
      <button type="button" class="btn btn-ghost btn-sm" :aria-pressed="paused" @click="paused = !paused">
        {{ paused ? 'Putar' : 'Jeda' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.engine-wrap { gap: 14px; }
.stroke-tabs { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
.stroke-tabs button {
  font: inherit; font-size: 13px; padding: 8px 6px; border-radius: 10px; cursor: pointer;
  color: var(--on-navy-muted); background: rgba(18, 40, 90, .8);
  border: 1px solid rgba(124, 200, 255, .22); transition: background .15s, color .15s, border-color .15s;
}
.stroke-tabs button:hover { border-color: rgba(124, 200, 255, .45); }
.stroke-tabs button.on { background: var(--accent); color: var(--navy); border-color: var(--accent); font-weight: 700; }
.engine-note { font-size: 13px; line-height: 1.5; color: var(--on-navy-muted); margin: 0; min-height: 2.9em; }
</style>

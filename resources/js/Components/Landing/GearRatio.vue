<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'
import '../../../css/landing.css'

// ---- Konstanta (spec §6) ----
const BASE_SPEED = 24 // °/s, gir A searah jarum jam
const M = 9
const N_A = 24
const VB_W = 460
const VB_H = 520
const MAX_FLING = 720

function gearPath(n, r, m) {
  const ra = r + m, rf = r - 1.25 * m, P = (Math.PI * 2) / n
  const pts = [[rf, -0.27], [ra, -0.13], [ra, 0.13], [rf, 0.27]]
  let d = ''
  for (let k = 0; k < n; k++) for (let j = 0; j < 4; j++) {
    const a = (k + pts[j][1]) * P, rad = pts[j][0]
    d += (k || j ? 'L' : 'M') + (rad * Math.cos(a)).toFixed(1) + ' ' + (rad * Math.sin(a)).toFixed(1) + ' '
  }
  return d + 'Z'
}

function makeGear(id, n, spokes, fill, stroke, dot, center, theta) {
  const r = (M * n) / 2
  let cx = center?.[0], cy = center?.[1]
  if (theta != null) {
    const dist = (M * (N_A + n)) / 2
    const rad = (theta * Math.PI) / 180
    cx = A_CENTER[0] + dist * Math.cos(rad)
    cy = A_CENTER[1] + dist * Math.sin(rad)
  }
  const spokeLines = Array.from({ length: spokes }, (_, i) => {
    const t = (i / spokes) * Math.PI * 2
    return {
      x1: (0.26 * r * Math.cos(t)).toFixed(1), y1: (0.26 * r * Math.sin(t)).toFixed(1),
      x2: (0.6 * r * Math.cos(t)).toFixed(1), y2: (0.6 * r * Math.sin(t)).toFixed(1),
    }
  })
  return { id, n, r, cx, cy, theta, fill, stroke, dot, path: gearPath(n, r, M), spokeLines }
}

const A_CENTER = [200, 250]
const gears = [
  makeGear('A', 24, 6, 'var(--navy-panel)', 'var(--accent)', 6, A_CENTER, null),
  makeGear('B', 12, 3, 'var(--accent)', 'var(--navy)', 4, null, -40),
  makeGear('C', 16, 4, 'var(--navy-panel)', 'rgba(255,255,255,.75)', 5, null, 75),
]

function gearAngle(g, a) {
  if (g.theta == null) return a
  return g.theta + 180 + 180 / g.n - (N_A / g.n) * (a - g.theta)
}

// ---- State ----
const svgRef = ref(null)
const gRefs = ref([])
const paused = ref(false)
const dragging = ref(false)
const readout = ref(['0 rpm', '0 rpm', '0 rpm'])

let a = 0
let base = BASE_SPEED
let vel = BASE_SPEED
let raf = 0
let last = 0
let lastReadout = 0
let drag = null // { gear, lastAng, lastT }
let inView = true
let io = null

const norm = d => ((d + 540) % 360) - 180

function applyTransforms() {
  gears.forEach((g, i) => {
    const el = gRefs.value[i]
    if (el) el.setAttribute('transform', `translate(${g.cx.toFixed(2)} ${g.cy.toFixed(2)}) rotate(${gearAngle(g, a).toFixed(2)})`)
  })
}

function fmt(v) {
  const rpm = Math.round(Math.abs(v) / 6)
  if (Math.abs(v) < 0.5) return `${rpm} rpm`
  return `${rpm} rpm · ${v > 0 ? 'CW' : 'CCW'}`
}

function updateReadout(now, force) {
  if (!force && now - lastReadout < 100) return
  lastReadout = now
  readout.value = [fmt(vel), fmt(-vel * 2), fmt(-vel * 1.5)]
}

function frame(now) {
  const dt = Math.min(0.05, (now - last) / 1000 || 0)
  last = now
  if (!dragging.value) {
    vel += (base - vel) * Math.min(1, dt * 1.2)
    a += vel * dt
  }
  a = ((a % 360) + 360) % 360
  applyTransforms()
  updateReadout(now)
  raf = requestAnimationFrame(frame)
}

function start() {
  if (raf || !inView || document.hidden) return
  last = performance.now()
  raf = requestAnimationFrame(frame)
}
function stop() {
  cancelAnimationFrame(raf)
  raf = 0
}

function toLocal(e) {
  const rect = svgRef.value.getBoundingClientRect()
  return [(e.clientX - rect.left) * VB_W / rect.width, (e.clientY - rect.top) * VB_H / rect.height]
}

function hitTest(x, y) {
  // pilih gir dengan jarak relatif terkecil (gir kecil menang di area tumpang tindih)
  let best = null, bestScore = Infinity
  for (const g of gears) {
    const score = Math.hypot(x - g.cx, y - g.cy) / (g.r + M)
    if (score <= 1 && score < bestScore) { best = g; bestScore = score }
  }
  return best
}

function onDown(e) {
  const [x, y] = toLocal(e)
  const g = hitTest(x, y)
  if (!g) return
  e.preventDefault()
  svgRef.value.setPointerCapture(e.pointerId)
  drag = { gear: g, lastAng: Math.atan2(y - g.cy, x - g.cx) * 180 / Math.PI, lastT: performance.now() }
  dragging.value = true
}

function onMove(e) {
  if (!drag) return
  const [x, y] = toLocal(e)
  const g = drag.gear
  const ang = Math.atan2(y - g.cy, x - g.cx) * 180 / Math.PI
  const d = norm(ang - drag.lastAng)
  const da = g.theta == null ? d : (-d * g.n) / N_A
  const now = performance.now()
  const dt = Math.max(0.001, (now - drag.lastT) / 1000)
  a += da
  vel = vel * 0.6 + (da / dt) * 0.4
  drag.lastAng = ang
  drag.lastT = now
  if (!raf) { applyTransforms(); updateReadout(now) }
}

function onUp(e) {
  if (!drag) return
  try { svgRef.value.releasePointerCapture(e.pointerId) } catch {}
  // jika pointer diam sebelum dilepas, jangan "fling"
  if (performance.now() - drag.lastT > 80) vel = 0
  vel = Math.max(-MAX_FLING, Math.min(MAX_FLING, vel))
  drag = null
  dragging.value = false
}

function onKey(e) {
  if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
    e.preventDefault()
    a += e.key === 'ArrowRight' ? 10 : -10
    applyTransforms()
  }
}

function togglePause() {
  paused.value = !paused.value
  base = paused.value ? 0 : BASE_SPEED
}

function onVisibility() {
  document.hidden ? stop() : start()
}

onMounted(() => {
  if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
    paused.value = true
    base = 0
    vel = 0
  }
  applyTransforms()
  updateReadout(performance.now(), true)
  io = new IntersectionObserver(([entry]) => {
    inView = entry.isIntersecting
    inView ? start() : stop()
  })
  io.observe(svgRef.value)
  document.addEventListener('visibilitychange', onVisibility)
  start()
})

onBeforeUnmount(() => {
  stop()
  io?.disconnect()
  document.removeEventListener('visibilitychange', onVisibility)
})

const labels = [
  { name: 'Gir A · 24 gigi', color: 'var(--navy-panel)', border: 'var(--accent)' },
  { name: 'Gir B · 12 gigi', color: 'var(--accent)', border: 'var(--accent)' },
  { name: 'Gir C · 16 gigi', color: 'var(--navy-panel)', border: 'rgba(255,255,255,.75)' },
]
</script>

<template>
  <div class="gear-wrap">
    <svg
      ref="svgRef"
      class="gear-svg"
      :class="{ 'is-dragging': dragging }"
      :viewBox="`0 0 ${VB_W} ${VB_H}`"
      role="img"
      aria-label="Simulasi rasio gir interaktif: seret gir untuk memutarnya"
      tabindex="0"
      @pointerdown="onDown"
      @pointermove="onMove"
      @pointerup="onUp"
      @pointercancel="onUp"
      @keydown="onKey"
    >
      <!-- dekorasi statis -->
      <circle :cx="A_CENTER[0]" :cy="A_CENTER[1]" r="190" fill="none" stroke="var(--accent)" stroke-opacity=".12" stroke-dasharray="3 9" />
      <line v-for="g in gears.slice(1)" :key="'l' + g.id"
        :x1="A_CENTER[0]" :y1="A_CENTER[1]" :x2="g.cx" :y2="g.cy"
        stroke="var(--accent)" stroke-opacity=".25" stroke-dasharray="4 6" />

      <g v-for="(g, i) in gears" :key="g.id" :ref="el => (gRefs[i] = el)">
        <path :d="g.path" :fill="g.fill" :stroke="g.stroke" stroke-width="2" stroke-linejoin="round" />
        <circle :r="0.66 * g.r" fill="none" :stroke="g.stroke" stroke-opacity=".35" />
        <line v-for="(s, j) in g.spokeLines" :key="j" v-bind="s" :stroke="g.stroke" stroke-width="4" stroke-linecap="round" />
        <circle :r="0.24 * g.r" fill="var(--navy)" :stroke="g.stroke" stroke-width="2" />
        <circle :r="g.dot" :fill="g.stroke" />
      </g>
    </svg>

    <div class="gear-readout" aria-live="off">
      <div v-for="(l, i) in labels" :key="l.name" class="gear-chip">
        <span class="gear-dot" :style="{ background: l.color, borderColor: l.border }" aria-hidden="true"></span>
        <span class="gear-chip-label">{{ l.name }}</span>
        <span class="gear-chip-val">{{ readout[i] }}</span>
      </div>
    </div>

    <div class="gear-foot">
      <span class="mono-caption">RASIO A:B = 2:1 · A:C = 3:2 · SERET GIR UNTUK MEMUTAR</span>
      <button type="button" class="btn btn-ghost btn-sm" :aria-pressed="paused" @click="togglePause">
        {{ paused ? 'Putar' : 'Jeda' }}
      </button>
    </div>
  </div>
</template>

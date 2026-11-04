<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'
import '../../../css/landing.css'

// ---- Konstanta kinematik (sama basis dengan GearRatio) ----
const BASE_SPEED = 20 // °/s
const M = 9
const N_A = 24
const VB_W = 460
const VB_H = 460
const MAX_FLING = 720
const A_CENTER = [230, 230]

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

function makeGear(id, n, fill, stroke, center, theta) {
  const r = (M * n) / 2
  let cx = center?.[0], cy = center?.[1]
  if (theta != null) {
    const dist = (M * (N_A + n)) / 2
    const rad = (theta * Math.PI) / 180
    cx = A_CENTER[0] + dist * Math.cos(rad)
    cy = A_CENTER[1] + dist * Math.sin(rad)
  }
  return { id, n, r, cx, cy, theta, fill, stroke, path: gearPath(n, r, M) }
}

// Sun gear (tengah, pegang lever wrench) + 2 planet gear (hex-bolt cap)
const gears = [
  makeGear('sun', 24, 'var(--navy-panel)', 'var(--accent)', A_CENTER, null),
  makeGear('p1', 12, 'var(--accent)', 'var(--navy)', null, -35),
  makeGear('p2', 12, 'var(--accent)', 'var(--navy)', null, 150),
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
const onState = ref(true) // posisi saklar: ON/OFF mengikuti sudut sun gear

let a = 0
let base = BASE_SPEED
let vel = BASE_SPEED
let raf = 0
let last = 0
let drag = null
let inView = true
let io = null

const norm = d => ((d + 540) % 360) - 180

function applyTransforms() {
  gears.forEach((g, i) => {
    const el = gRefs.value[i]
    if (el) el.setAttribute('transform', `translate(${g.cx.toFixed(2)} ${g.cy.toFixed(2)}) rotate(${gearAngle(g, a).toFixed(2)})`)
  })
  // Ring luar statis tidak ikut berputar; hanya knob indikator kecil mengikuti sun
  const knob = svgRef.value?.querySelector('.ring-indicator')
  if (knob) knob.setAttribute('transform', `rotate(${(a * 0.15).toFixed(2)} ${A_CENTER[0]} ${A_CENTER[1]})`)
}

function frame(now) {
  const dt = Math.min(0.05, (now - last) / 1000 || 0)
  last = now
  if (!dragging.value) {
    vel += (base - vel) * Math.min(1, dt * 1.2)
    a += vel * dt
  }
  a = ((a % 360) + 360) % 360
  onState.value = Math.cos((a * Math.PI) / 180) > 0
  applyTransforms()
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
  onState.value = Math.cos((a * Math.PI) / 180) > 0
  if (!raf) applyTransforms()
}

function onUp(e) {
  if (!drag) return
  try { svgRef.value.releasePointerCapture(e.pointerId) } catch {}
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
</script>

<template>
  <div class="gear-wrap">
    <svg
      ref="svgRef"
      class="gear-svg plate-svg"
      :class="{ 'is-dragging': dragging }"
      :viewBox="`0 0 ${VB_W} ${VB_H}`"
      role="img"
      aria-label="Mekanisme gear transmisi: seret gir untuk memutar"
      tabindex="0"
      @pointerdown="onDown"
      @pointermove="onMove"
      @pointerup="onUp"
      @pointercancel="onUp"
      @keydown="onKey"
    >
      <!-- Plate dasar bulat -->
      <circle :cx="A_CENTER[0]" :cy="A_CENTER[1]" r="215" fill="var(--navy)" stroke="rgba(124,200,255,.2)" stroke-width="2" />
      <circle :cx="A_CENTER[0]" :cy="A_CENTER[1]" r="198" fill="none" stroke="var(--accent)" stroke-opacity=".18" stroke-dasharray="2 7" />

      <!-- Ring luar bergerigi statis (housing) -->
      <circle :cx="A_CENTER[0]" :cy="A_CENTER[1]" r="186" fill="none" stroke="var(--navy-panel)" stroke-width="22" />
      <g v-for="i in 28" :key="'tooth' + i"
         :transform="`rotate(${(i * 360) / 28} ${A_CENTER[0]} ${A_CENTER[1]})`">
        <rect :x="A_CENTER[0] - 5" :y="A_CENTER[1] - 207" width="10" height="14" rx="2" fill="var(--navy-panel)" />
      </g>

      <!-- Indikator kecil pada ring (bergerak lambat, estetik) -->
      <g class="ring-indicator">
        <circle :cx="A_CENTER[0]" :cy="A_CENTER[1] - 186" r="7" fill="var(--accent)" />
      </g>

      <!-- 4 baut pemasangan plate -->
      <g v-for="(ang, i) in [45, 135, 225, 315]" :key="'bolt' + i">
        <circle :cx="A_CENTER[0] + 196 * Math.cos(ang * Math.PI / 180)"
                :cy="A_CENTER[1] + 196 * Math.sin(ang * Math.PI / 180)"
                r="9" fill="#12285A" stroke="rgba(255,255,255,.25)" stroke-width="1.5" />
        <path v-for="k in 6" :key="k"
          :d="`M ${A_CENTER[0] + 196 * Math.cos(ang * Math.PI / 180) + 5 * Math.cos(k * 60 * Math.PI / 180)} ${A_CENTER[1] + 196 * Math.sin(ang * Math.PI / 180) + 5 * Math.sin(k * 60 * Math.PI / 180)} L ${A_CENTER[0] + 196 * Math.cos(ang * Math.PI / 180) + 5 * Math.cos((k + 1) * 60 * Math.PI / 180)} ${A_CENTER[1] + 196 * Math.sin(ang * Math.PI / 180) + 5 * Math.sin((k + 1) * 60 * Math.PI / 180)}`"
          stroke="rgba(255,255,255,.4)" stroke-width="1" fill="none" />
      </g>

      <!-- garis penghubung sun-planet -->
      <line v-for="g in gears.slice(1)" :key="'l' + g.id"
        :x1="A_CENTER[0]" :y1="A_CENTER[1]" :x2="g.cx" :y2="g.cy"
        stroke="var(--accent)" stroke-opacity=".2" stroke-dasharray="4 6" />

      <!-- Planet gears (hex-bolt style) -->
      <g v-for="(g, i) in gears.filter(g => g.id !== 'sun')" :key="g.id"
         :ref="el => (gRefs[gears.indexOf(g)] = el)">
        <path :d="g.path" :fill="g.fill" :stroke="g.stroke" stroke-width="2" stroke-linejoin="round" />
        <circle :r="0.5 * g.r" fill="#12285A" :stroke="g.stroke" stroke-width="1.5" />
        <path v-for="k in 6" :key="k"
          :d="`M ${0.32 * g.r * Math.cos(k * 60 * Math.PI / 180)} ${0.32 * g.r * Math.sin(k * 60 * Math.PI / 180)} L ${0.32 * g.r * Math.cos((k + 1) * 60 * Math.PI / 180)} ${0.32 * g.r * Math.sin((k + 1) * 60 * Math.PI / 180)}`"
          stroke="rgba(10,26,58,.65)" stroke-width="1.5" fill="none" />
      </g>

      <!-- Sun gear (tengah) + lever wrench -->
      <g :ref="el => (gRefs[0] = el)">
        <path :d="gears[0].path" :fill="gears[0].fill" :stroke="gears[0].stroke" stroke-width="2" stroke-linejoin="round" />
        <circle :r="0.62 * gears[0].r" fill="none" :stroke="gears[0].stroke" stroke-opacity=".3" />

        <!-- Lever wrench (seperti open-end wrench) -->
        <g>
          <rect x="-72" y="-7" width="144" height="14" rx="6" fill="var(--accent)" stroke="var(--navy)" stroke-width="1.5" />
          <path d="M -72 -14 L -58 -14 L -58 14 L -72 14 Z" fill="var(--accent)" stroke="var(--navy)" stroke-width="1.5" />
          <path d="M 72 -14 L 58 -14 L 58 14 L 72 14 Z" fill="var(--accent)" stroke="var(--navy)" stroke-width="1.5" />
        </g>

        <circle r="16" fill="#12285A" :stroke="gears[0].stroke" stroke-width="2" />
        <circle r="6" :fill="onState ? 'var(--accent)' : 'rgba(255,255,255,.25)'" />
      </g>
    </svg>

    <div class="gear-foot">
      <span class="mono-caption">SERET GIR UNTUK MEMUTAR MEKANISME · STATUS {{ onState ? 'ON' : 'OFF' }}</span>
      <button type="button" class="btn btn-ghost btn-sm" :aria-pressed="paused" @click="togglePause">
        {{ paused ? 'Putar' : 'Jeda' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import '../../../css/landing.css'

/* =========================================================
   GEOMETRI SLIDER-CRANK — satuan SVG, viewBox 67 106 474 530
   ========================================================= */
const VB = { x: 67, y: 106, w: 474, h: 530 }

const G = {
  cx: 310, cy: 500,        // pusat poros engkol (main journal)
  r: 58,                    // crank throw
  L: 152,                   // panjang conrod
  deck: 242,                // deck height (dasar kepala silinder)
  crownOff: 32,             // mahkota piston -> pena piston
  pistonTop: 258,           // mahkota piston saat TMA
  pistonH: 110,             // tinggi piston
  valveLift: 16,
}

const CYCLE = 720
const SPEED = 90

const wrap = (a, c) => ((a % c) + c) % c
const clamp01 = (x) => (x < 0 ? 0 : x > 1 ? 1 : x)
const mix = (a, b, t) => a + (b - a) * t
const mixCol = (c1, c2, t) => [
  Math.round(mix(c1[0], c2[0], t)),
  Math.round(mix(c1[1], c2[1], t)),
  Math.round(mix(c1[2], c2[2], t)),
]
const rgb = (c) => `rgb(${c[0]},${c[1]},${c[2]})`

function geom(deg) {
  const t = (deg * Math.PI) / 180
  const s = Math.sin(t)
  const c = Math.cos(t)
  const px = G.cx + G.r * s
  const py = G.cy - G.r * c
  const d = G.r * c + Math.sqrt(G.L * G.L - G.r * G.r * s * s)
  const wristY = G.cy - d
  return { px, py, wristY, crownY: wristY - G.crownOff }
}

function liftBell(open, dur, deg) {
  const p = wrap(deg - open, CYCLE)
  return p < dur ? Math.sin((Math.PI * p) / dur) : 0
}

function angDist(a, b) {
  const d = wrap(a - b, CYCLE)
  return d > CYCLE / 2 ? d - CYCLE : d
}

/* =========================================================
   WARNA GAS
   ========================================================= */
const C_FRESH = [124, 200, 255]
const C_DEEP = [78, 150, 210]
const C_BURN = [255, 140, 60]
const C_COOL = [190, 120, 86]
const C_EXH = [148, 163, 184]

const GAS_STOPS = [
  { th: 0, col: C_FRESH, a: 0.10 },
  { th: 50, col: C_FRESH, a: 0.55 },
  { th: 180, col: C_FRESH, a: 0.55 },
  { th: 340, col: C_DEEP, a: 0.60 },
  { th: 360, col: C_BURN, a: 0.80 },
  { th: 410, col: C_BURN, a: 0.72 },
  { th: 520, col: C_COOL, a: 0.62 },
  { th: 570, col: C_EXH, a: 0.48 },
  { th: 718, col: C_EXH, a: 0.10 },
]

function gasAt(deg) {
  if (deg <= GAS_STOPS[0].th) return GAS_STOPS[0]
  for (let i = 0; i < GAS_STOPS.length - 1; i++) {
    if (deg <= GAS_STOPS[i + 1].th) {
      const t = (deg - GAS_STOPS[i].th) / (GAS_STOPS[i + 1].th - GAS_STOPS[i].th)
      return {
        color: rgb(mixCol(GAS_STOPS[i].col, GAS_STOPS[i + 1].col, t)),
        op: mix(GAS_STOPS[i].a, GAS_STOPS[i + 1].a, t),
      }
    }
  }
  const last = GAS_STOPS[GAS_STOPS.length - 1]
  return { color: rgb(last.col), op: last.a }
}

const STROKES = [
  { key: 'hisap', label: 'Hisap', lo: 0, note: 'Piston turun, klep hisap terbuka — campuran udara & bahan bakar terhisap masuk.' },
  { key: 'kompresi', label: 'Kompresi', lo: 180, note: 'Kedua klep tertutup, piston naik memampatkan campuran.' },
  { key: 'usaha', label: 'Usaha', lo: 360, note: 'Busi memercikkan api, gas memuai mendorong piston turun.' },
  { key: 'buang', label: 'Buang', lo: 540, note: 'Klep buang terbuka, piston naik mendorong gas sisa keluar.' },
]

/* =========================================================
   STATE
   ========================================================= */
const cycle = ref(0)
const paused = ref(false)
const dragging = ref(false)
const svgRef = ref(null)

let raf = null
let last = 0
let drag = null

const th = computed(() => wrap(cycle.value, CYCLE))
const pos = computed(() => geom(th.value))
const pistonOffset = computed(() => pos.value.crownY - G.pistonTop)
const gasH = computed(() => Math.max(0, pos.value.crownY - G.deck))
const gas = computed(() => gasAt(th.value))

const intakeLift = computed(() => liftBell(710, 200, th.value))
const exhaustLift = computed(() => liftBell(530, 200, th.value))
const spark = computed(() => Math.exp(-Math.pow(angDist(th.value, 360) / 15, 2)))

const phase = computed(() => Math.min(3, Math.floor(th.value / 180)))
const active = computed(() => STROKES[phase.value] || STROKES[0])
const progress = computed(() => clamp01((th.value - active.value.lo) / 180))

const movingDown = computed(() => geom(wrap(th.value + 2, CYCLE)).crownY > pos.value.crownY)
const arrowOpacity = computed(() => {
  const v = Math.abs(geom(wrap(th.value + 2, CYCLE)).crownY - pos.value.crownY) / 1.3
  return Math.min(1, v * 1.9)
})

/* =========================================================
   CONROD — taper I-beam, dihitung tiap frame
   ========================================================= */
const rod = computed(() => {
  const p = pos.value
  const x1 = p.px            // big end di crankpin
  const y1 = p.py
  const x2 = G.cx            // small end di pena piston
  const y2 = p.wristY
  const dx = x2 - x1
  const dy = y2 - y1
  const len = Math.hypot(dx, dy) || 1
  const nx = -dy / len
  const ny = dx / len
  const wb = 11
  const ws = 7.5
  const f = (n) => n.toFixed(2)

  return {
    body:
      `M${f(x1 + nx * wb)},${f(y1 + ny * wb)}` +
      `L${f(x2 + nx * ws)},${f(y2 + ny * ws)}` +
      `L${f(x2 - nx * ws)},${f(y2 - ny * ws)}` +
      `L${f(x1 - nx * wb)},${f(y1 - ny * wb)}Z`,
    hl:
      `M${f(x1 + nx * (wb - 4.5))},${f(y1 + ny * (wb - 4.5))}` +
      `L${f(x2 + nx * (ws - 3))},${f(y2 + ny * (ws - 3))}`,
    boltA: [x1 + nx * 15, y1 + ny * 15],
    boltB: [x1 - nx * 15, y1 - ny * 15],
  }
})

/* =========================================================
   LOOP
   ========================================================= */
function frame(now) {
  const dt = Math.min(0.05, (now - last) / 1000) || 0
  last = now
  if (!dragging.value && !paused.value && !document.hidden && Number.isFinite(dt)) {
    cycle.value = (cycle.value + SPEED * dt) % CYCLE
  }
  raf = requestAnimationFrame(frame)
}

onMounted(() => {
  if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) paused.value = true
  last = performance.now()
  raf = requestAnimationFrame(frame)
})

onBeforeUnmount(() => {
  if (raf !== null) cancelAnimationFrame(raf)
  raf = null
})

/* =========================================================
   INTERAKSI
   ========================================================= */
function toLocal(e) {
  const r = svgRef.value.getBoundingClientRect()
  return {
    x: VB.x + ((e.clientX - r.left) * VB.w) / r.width,
    y: VB.y + ((e.clientY - r.top) * VB.h) / r.height,
  }
}
const normDeg = (d) => ((d + 540) % 360) - 180

function onDown(e) {
  if (!svgRef.value) return
  e.preventDefault()
  svgRef.value.setPointerCapture?.(e.pointerId)
  const p = toLocal(e)
  drag = { ang: (Math.atan2(p.x - G.cx, G.cy - p.y) * 180) / Math.PI }
  dragging.value = true
}

function onMove(e) {
  if (!drag) return
  const p = toLocal(e)
  const ang = (Math.atan2(p.x - G.cx, G.cy - p.y) * 180) / Math.PI
  cycle.value = wrap(cycle.value + normDeg(ang - drag.ang), CYCLE)
  drag.ang = ang
}

function onUp(e) {
  if (!drag) return
  try { svgRef.value.releasePointerCapture?.(e.pointerId) } catch {}
  drag = null
  dragging.value = false
}

function onKey(e) {
  if (e.key === 'ArrowRight') { e.preventDefault(); cycle.value = wrap(cycle.value + 10, CYCLE) }
  if (e.key === 'ArrowLeft') { e.preventDefault(); cycle.value = wrap(cycle.value - 10, CYCLE) }
  if (e.key === ' ') { e.preventDefault(); paused.value = !paused.value }
}

function jump(i) {
  cycle.value = STROKES[i].lo + 2
  paused.value = false
}

function step(delta) {
  jump(wrap(phase.value + delta, STROKES.length))
}
</script>

<template>
  <div class="gear-wrap engine-wrap">
    <svg
      ref="svgRef"
      class="gear-svg engine-svg"
      :class="{ 'is-dragging': dragging }"
      :viewBox="`${VB.x} ${VB.y} ${VB.w} ${VB.h}`"
      role="img"
      aria-label="Animasi potongan mesin 4 tak: putar engkol untuk menjalankan siklus"
      tabindex="0"
      @pointerdown="onDown"
      @pointermove="onMove"
      @pointerup="onUp"
      @pointercancel="onUp"
      @keydown="onKey"
    >
      <defs>
        <linearGradient id="fse-steel" x1="0" y1="0" x2="1" y2="0.45">
          <stop offset="0" stop-color="#2B3947" />
          <stop offset="0.45" stop-color="#33414F" />
          <stop offset="1" stop-color="#1E2731" />
        </linearGradient>
        <radialGradient id="fse-hub" cx="0.36" cy="0.3" r="0.82">
          <stop offset="0" stop-color="#6B8097" />
          <stop offset="0.5" stop-color="#3C4B5C" />
          <stop offset="1" stop-color="#1B2530" />
        </radialGradient>
        <radialGradient id="fse-pinmetal" cx="0.35" cy="0.3" r="0.8">
          <stop offset="0" stop-color="#A9BACB" />
          <stop offset="0.55" stop-color="#6E8296" />
          <stop offset="1" stop-color="#33414F" />
        </radialGradient>
        <linearGradient id="fse-pistonmetal" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0" stop-color="#39485A" />
          <stop offset="0.16" stop-color="#7B8FA3" />
          <stop offset="0.42" stop-color="#93A6BA" />
          <stop offset="0.72" stop-color="#61748A" />
          <stop offset="1" stop-color="#35434F" />
        </linearGradient>
        <radialGradient id="fse-burn">
          <stop offset="0" stop-color="#FFB454" stop-opacity="0.92" />
          <stop offset="0.4" stop-color="#FF6A1F" stop-opacity="0.38" />
          <stop offset="1" stop-color="#FF4D00" stop-opacity="0" />
        </radialGradient>
        <radialGradient id="fse-arc">
          <stop offset="0" stop-color="#FFF6D8" stop-opacity="1" />
          <stop offset="0.35" stop-color="#FFD166" stop-opacity="0.65" />
          <stop offset="1" stop-color="#FFB020" stop-opacity="0" />
        </radialGradient>
        <filter id="fse-blur3"><feGaussianBlur stdDeviation="3.4" /></filter>
        <filter id="fse-blur1"><feGaussianBlur stdDeviation="0.9" /></filter>
        <pattern id="fse-grid" width="26" height="26" patternUnits="userSpaceOnUse">
          <path d="M26 0H0V26" fill="none" stroke="#111A2C" stroke-width="0.8" />
        </pattern>
      </defs>

      <!-- panggung -->
      <rect x="67" y="106" width="474" height="530" fill="#070F22" />
      <rect x="67" y="106" width="474" height="530" fill="url(#fse-grid)" opacity="0.62" />

      <!-- crankcase -->
      <path class="fse-case" d="M226,446 V528 Q226,584 282,584 H338 Q394,584 394,528 V446 Z" />
      <line x1="226" y1="452" x2="394" y2="452" stroke="#2A3648" stroke-width="1" />
      <circle cx="240" cy="466" r="3" fill="#2B3947" />
      <circle cx="380" cy="466" r="3" fill="#2B3947" />
      <circle cx="240" cy="566" r="3" fill="#2B3947" />
      <circle cx="380" cy="566" r="3" fill="#2B3947" />

      <!-- ============ CRANKSHAFT: satu throw, tampak samping ============ -->
      <g :transform="`rotate(${th} ${G.cx} ${G.cy})`">
        <!-- counterweight: lobus massa berbenteng di sisi bawah -->
        <path class="fse-cw" d="M380,533 A78,78 0 0 1 240,533 L310,500 Z" />
        <path d="M370,527 A67,67 0 0 1 250,527" fill="none" stroke="#3F5163" stroke-width="1.1" opacity="0.45" />
        <circle cx="358" cy="518" r="4.6" fill="#0E151D" stroke="#3F5163" stroke-width="0.9" />
        <circle cx="262" cy="518" r="4.6" fill="#0E151D" stroke="#3F5163" stroke-width="0.9" />

        <!-- crank web: plat menyambung journal ke crankpin, lebar sebaras-warna -->
        <path class="fse-web" d="M338,500 L330,458 A36,36 0 0 0 290,458 L282,500 Z" />
        <path d="M332,494 L326,456" stroke="#4A5C70" stroke-width="1.2" fill="none" opacity="0.55" />

        <!-- crankpin boss: mengejek big-end conrod -->
        <circle class="fse-crankpin" :cx="G.cx" :cy="G.cy - G.r" r="18" />
        <circle :cx="G.cx" :cy="G.cy - G.r" r="6.5" fill="#141C26" stroke="#4A5C70" stroke-width="0.9" />

        <!-- main journal + bearing bore -->
        <circle class="fse-journal" :cx="G.cx" :cy="G.cy" r="27" />
        <circle class="fse-journal-bore" :cx="G.cx" :cy="G.cy" r="14" />
        <circle :cx="G.cx" :cy="G.cy" r="4.5" fill="#0A1A3A" />
        <path d="M283,500 A27,27 0 0 1 337,500" fill="none" stroke="#7B8FA3" stroke-width="1" opacity="0.35" />
      </g>

      <!-- ============ CONROD ============ -->
      <path class="fse-rod" :d="rod.body" />
      <path :d="rod.hl" stroke="#A9BACB" stroke-width="1.3" fill="none" opacity="0.4" />
      <!-- big end: ring bearing mengelilingi crankpin -->
      <circle class="fse-rodend" :cx="pos.px" :cy="pos.py" r="22" />
      <circle class="fse-rodend-bore" :cx="pos.px" :cy="pos.py" r="13" />
      <circle :cx="rod.boltA[0]" :cy="rod.boltA[1]" r="2.6" fill="#0E151D" stroke="#7B8EA3" stroke-width="0.7" />
      <circle :cx="rod.boltB[0]" :cy="rod.boltB[1]" r="2.6" fill="#0E151D" stroke="#7B8EA3" stroke-width="0.7" />
      <!-- small end ring mengelilingi pena piston -->
      <circle class="fse-rodend-sm" :cx="G.cx" :cy="pos.wristY" r="15" />
      <circle class="fse-rodend-bore" :cx="G.cx" :cy="pos.wristY" r="9.5" />

      <!-- ============ PISTON ============ -->
      <g :transform="`translate(0 ${pistonOffset.toFixed(2)})`">
        <rect class="fse-piston" x="263" y="258" width="94" height="110" rx="6" />
        <!-- alur ring -->
        <line class="fse-ring" x1="264" y1="272" x2="356" y2="272" />
        <line class="fse-ring" x1="264" y1="283" x2="356" y2="283" />
        <line class="fse-ring" x1="264" y1="294" x2="356" y2="294" />
        <!-- relief klep di mahkota -->
        <ellipse cx="288" cy="259.5" rx="13" ry="3" fill="#2A3745" opacity="0.8" />
        <ellipse cx="332" cy="259.5" rx="13" ry="3" fill="#2A3745" opacity="0.8" />
        <path d="M263,344 H357" stroke="#5A6F87" stroke-width="1" opacity="0.5" />
        <path d="M292,366 H328" stroke="#2B3745" stroke-width="6" opacity="0.45" />
        <!-- pena piston -->
        <circle class="fse-wrist" :cx="G.cx" :cy="(pos.wristY - pistonOffset).toFixed(2)" r="12" />
        <circle :cx="G.cx" :cy="(pos.wristY - pistonOffset).toFixed(2)" r="5" fill="#10171F" />
        <text class="fse-piston-txt" x="310" y="332" text-anchor="middle">PISTON</text>
      </g>

      <!-- dinding silinder -->
      <rect class="fse-wall" x="254" y="242" width="10" height="228" />
      <rect class="fse-wall" x="356" y="242" width="10" height="228" />
      <path d="M254,466 H264 M356,466 H366" stroke="#4A5C70" stroke-width="1.2" />

      <!-- gas ruang bakar -->
      <rect :x="265" :y="G.deck" width="90" :height="gasH" :fill="gas.color" :fill-opacity="gas.op" rx="1.5" />
      <rect
        x="264" :y="G.deck - 8" width="92" :height="Math.max(20, gasH + 12)"
        fill="url(#fse-burn)" :opacity="(spark * 0.9).toFixed(3)" filter="url(#fse-blur3)"
      />

      <!-- kepala silinder -->
      <path class="fse-head" d="M254,242 V206 Q254,191 269,191 H351 Q366,191 366,206 V242 Z" />
      <path d="M270,242 H306 M314,242 H350" stroke="#67798E" stroke-width="2" />

      <!-- saluran hisap -->
      <path class="fse-duct" stroke="#2A4A5E" stroke-width="22" d="M154,208 C196,208 246,203 288,218" />
      <path
        class="fse-duct fse-flow" stroke="#7CC8FF" stroke-width="16"
        :opacity="(intakeLift * 0.97).toFixed(3)" d="M154,208 C196,208 246,203 288,218"
      />
      <rect class="fse-port" x="118" y="194" width="38" height="27" rx="5" />
      <path d="M124,201 H150 M124,208 H150 M124,215 H150" stroke="#2B3947" stroke-width="1.2" />
      <text class="fse-lbl" x="137" y="184" text-anchor="middle">KARBURATOR</text>

      <!-- saluran buang -->
      <path class="fse-duct" stroke="#495868" stroke-width="22" d="M466,208 C424,208 374,203 332,218" />
      <path
        class="fse-duct fse-flow" stroke="#C3CED9" stroke-width="16"
        :opacity="(exhaustLift * 0.97).toFixed(3)" d="M332,218 C374,203 424,208 466,208"
      />
      <rect class="fse-port" x="464" y="194" width="46" height="27" rx="5" />
      <path d="M470,201 H504 M470,208 H504 M470,215 H504" stroke="#2B3947" stroke-width="1.2" />
      <text class="fse-lbl" x="487" y="184" text-anchor="middle">KNALPOT</text>

      <!-- klep -->
      <g :transform="`translate(0 ${(intakeLift * G.valveLift).toFixed(2)})`">
        <rect x="285.6" y="168" width="4.8" height="70" rx="2.2" fill="#AEBFD1" stroke="#8FA3B8" stroke-width="0.6" />
        <path d="M274,238 H302 Q301,247 288,247 Q275,247 274,238 Z" fill="#C6D5E4" stroke="#8FA3B8" stroke-width="0.9" />
      </g>
      <g :transform="`translate(0 ${(exhaustLift * G.valveLift).toFixed(2)})`">
        <rect x="329.6" y="168" width="4.8" height="70" rx="2.2" fill="#AEBFD1" stroke="#8FA3B8" stroke-width="0.6" />
        <path d="M318,238 H346 Q345,247 332,247 Q319,247 318,238 Z" fill="#C6D5E4" stroke="#8FA3B8" stroke-width="0.9" />
      </g>

      <!-- busi + percikan -->
      <rect x="301" y="148" width="18" height="34" rx="3.5" fill="#39485A" stroke="#67798E" stroke-width="1.2" />
      <rect x="305" y="136" width="10" height="14" rx="3" fill="#4A5C70" stroke="#67798E" stroke-width="1.2" />
      <line x1="310" y1="182" x2="310" y2="240" stroke="#8595A9" stroke-width="2.5" />
      <line x1="303" y1="242" x2="317" y2="242" stroke="#93A6BA" stroke-width="2" stroke-linecap="round" />
      <g :opacity="spark.toFixed(3)">
        <circle cx="310" cy="250" r="28" fill="url(#fse-arc)" filter="url(#fse-blur1)" />
        <path d="M310,240 l-5,10 l9,1 l-7,12" stroke="#FFF3CF" stroke-width="2.5" fill="none" stroke-linecap="round" filter="url(#fse-blur1)" />
      </g>

      <!-- anotasi -->
      <g class="fse-anno">
        <path class="fse-lead" d="M276,172 L284,200" />
        <text class="fse-lbl" x="272" y="168" text-anchor="end">KLEP HISAP</text>
        <path class="fse-lead" d="M344,172 L336,200" />
        <text class="fse-lbl" x="348" y="168">KLEP BUANG</text>
        <text class="fse-lbl" x="310" y="130" text-anchor="middle">BUSI</text>
        <text class="fse-lbl" x="310" y="600" text-anchor="middle">POROS ENGKOL</text>
      </g>

      <!-- panah arah piston -->
      <g
        :transform="`translate(384 ${(pos.crownY + 62).toFixed(1)}) rotate(${movingDown ? 0 : 180})`"
        :opacity="arrowOpacity.toFixed(3)"
      >
        <path d="M-8,-9 L0,0 L8,-9" fill="none" stroke="#F59E0B" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
      </g>

      <!-- readout -->
      <text class="fse-readout" x="120" y="620">{{ Math.round(th) }}°</text>
      <text class="fse-readout-sub" x="120" y="632">{{ active.label.toUpperCase() }}</text>

      
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

    <p class="engine-note">{{ active.note }}</p>

    <div class="engine-bar">
      <div class="engine-ctl">
        <button type="button" class="engine-btn" aria-label="Langkah sebelumnya" @click="step(-1)">‹</button>
        <button type="button" class="engine-btn engine-btn-play" :aria-pressed="paused" @click="paused = !paused">
          {{ paused ? 'Putar' : 'Jeda' }}
        </button>
        <button type="button" class="engine-btn" aria-label="Langkah berikutnya" @click="step(1)">›</button>
      </div>
      <div class="engine-progress" aria-hidden="true"><span :style="{ width: progress * 100 + '%' }" /></div>
    </div>
  </div>
</template>

<style scoped>
.engine-wrap { gap: 12px; }

.engine-svg {
  touch-action: none;
  border-radius: 16px;
  border: 1px solid rgba(124, 200, 255, 0.14);
  cursor: grab;
}
.engine-svg.is-dragging { cursor: grabbing; }
.engine-svg:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }

/* crankshaft */
.fse-case { fill: #0D1420; stroke: #2A3648; stroke-width: 1; }
.fse-cw { fill: #1B2530; stroke: #38495C; stroke-width: 1.2; stroke-linejoin: round; }
.fse-web { fill: #1F2A36; stroke: #44576B; stroke-width: 1.2; stroke-linejoin: round; }
.fse-journal { fill: url(#fse-hub); stroke: #55697F; stroke-width: 1.4; }
.fse-journal-bore { fill: #0F1620; stroke: #3C4A5A; stroke-width: 1.2; }
.fse-crankpin { fill: url(#fse-pinmetal); stroke: #55697F; stroke-width: 1.2; }

/* conrod */
.fse-rod { fill: #4A5C70; stroke: #8296AA; stroke-width: 2; stroke-linejoin: round; }
.fse-rodend { fill: none; stroke: #8296AA; stroke-width: 8.5; }
.fse-rodend-sm { fill: none; stroke: #8296AA; stroke-width: 6; }
.fse-rodend-bore { fill: none; stroke: #22303C; stroke-width: 1.4; }

/* piston & silinder */
.fse-piston { fill: url(#fse-pistonmetal); stroke: #A2B5C8; stroke-width: 1.2; }
.fse-ring { stroke: #4E6076; stroke-width: 2; opacity: 0.9; }
.fse-wrist { fill: url(#fse-pinmetal); stroke: #93A6BA; stroke-width: 1.1; }
.fse-wall { fill: url(#fse-steel); stroke: #4A5C70; stroke-width: 1.2; }
.fse-head { fill: url(#fse-steel); stroke: #67798E; stroke-width: 1.3; }
.fse-port { fill: #141C26; stroke: #2A3648; stroke-width: 1.2; }

/* saluran gas */
.fse-duct { fill: none; stroke-linecap: round; stroke-linejoin: round; opacity: 0.3; }
.fse-flow { stroke-dasharray: 12 14; opacity: 0.3; animation: fse-flow 1.1s linear infinite; }
@keyframes fse-flow { to { stroke-dashoffset: -26; } }

/* teks */
.fse-lbl {
  font-family: ui-monospace, 'SFMono-Regular', Consolas, monospace;
  font-size: 10px;
  fill: #7C8FA6;
  letter-spacing: 0.05em;
}
.fse-lead { stroke: #31404F; stroke-width: 0.8; fill: none; }
.fse-piston-txt {
  font-family: ui-monospace, 'SFMono-Regular', Consolas, monospace;
  font-size: 9.5px;
  fill: #22303C;
  letter-spacing: 0.11em;
  font-weight: 700;
}
.fse-readout {
  font-family: ui-monospace, 'SFMono-Regular', Consolas, monospace;
  font-size: 14px;
  fill: #E8EEF7;
}
.fse-readout-sub {
  font-family: ui-monospace, 'SFMono-Regular', Consolas, monospace;
  font-size: 8.5px;
  fill: #F59E0B;
  letter-spacing: 0.12em;
}

/* kontrol */
.stroke-tabs { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; }
.stroke-tabs button {
  font: inherit;
  font-size: 12px;
  padding: 7px 3px;
  border-radius: 9px;
  cursor: pointer;
  color: var(--on-navy-muted);
  background: rgba(18, 40, 90, 0.78);
  border: 1px solid rgba(124, 200, 255, 0.2);
  transition: background 0.12s, color 0.12s, border-color 0.12s;
}
.stroke-tabs button:hover { border-color: rgba(124, 200, 255, 0.38); }
.stroke-tabs button.on {
  background: var(--accent);
  color: #0A1A3A;
  border-color: var(--accent);
  font-weight: 700;
}

.engine-note {
  font-size: 12.5px;
  line-height: 1.45;
  color: #E2E8F0;
  margin: 4px 0 0;
  min-height: 2.5em;
}

.engine-bar { display: flex; align-items: center; gap: 10px; }
.engine-ctl { display: flex; gap: 5px; }
.engine-btn {
  font: inherit;
  font-size: 12.5px;
  min-height: 32px;
  padding: 0 10px;
  border-radius: 8px;
  cursor: pointer;
  color: var(--on-navy-muted);
  background: rgba(18, 40, 90, 0.78);
  border: 1px solid rgba(124, 200, 255, 0.2);
}
.engine-btn:hover { color: #fff; border-color: rgba(124, 200, 255, 0.38); }
.engine-btn-play { min-width: 68px; color: #fff; }
.engine-progress {
  flex: 1;
  height: 2.5px;
  border-radius: 2px;
  background: rgba(124, 200, 255, 0.14);
  overflow: hidden;
}
.engine-progress span {
  display: block;
  height: 100%;
  background: #F59E0B;
  transition: width 0.18s linear;
}

@media (prefers-reduced-motion: reduce) {
  .fse-flow { animation: none; }
}
</style>

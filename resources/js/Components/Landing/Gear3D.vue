<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'

const svgRef = ref(null)
let animationFrame = null

// Transmisi gear 3D dengan dog-teeth/spline shading
function drawGear3D(svg, x, y, radius, teeth, angle, color, lightDir = [1, 1, 1]) {
  const g = document.createElementNS('http://www.w3.org/2000/svg', 'g')
  g.setAttribute('transform', `translate(${x} ${y}) rotate(${angle})`)

  const P = (2 * Math.PI) / teeth
  const m = 4 // modul
  const ra = radius + m // addendum radius
  const rf = radius - 1.25 * m // root radius

  // Shadow blur (isometric depth cue)
  const defs = svg.querySelector('defs') || document.createElementNS('http://www.w3.org/2000/svg', 'defs')
  if (!svg.querySelector('defs')) svg.prepend(defs)

  const filterId = `gear-shadow-${Math.random().toString(36).slice(2)}`
  const filter = document.createElementNS('http://www.w3.org/2000/svg', 'filter')
  filter.setAttribute('id', filterId)
  filter.setAttribute('x', '-50%')
  filter.setAttribute('y', '-50%')
  filter.setAttribute('width', '200%')
  filter.setAttribute('height', '200%')

  const feGaussianBlur = document.createElementNS('http://www.w3.org/2000/svg', 'feGaussianBlur')
  feGaussianBlur.setAttribute('in', 'SourceGraphic')
  feGaussianBlur.setAttribute('stdDeviation', '2')
  filter.appendChild(feGaussianBlur)
  defs.appendChild(filter)

  // Tooth path involute-like approximation
  let toothPath = ''
  for (let k = 0; k < teeth; k++) {
    const t0 = k * P
    const t1 = (k + 0.25) * P
    const t2 = (k + 0.5) * P
    const t3 = (k + 0.75) * P
    const t4 = (k + 1) * P

    const pts = [
      [rf * Math.cos(t0 + 0.15), rf * Math.sin(t0 + 0.15)], // root start
      [ra * Math.cos(t1), ra * Math.sin(t1)], // addendum tip left
      [ra * Math.cos(t2), ra * Math.sin(t2)], // addendum peak
      [ra * Math.cos(t3), ra * Math.sin(t3)], // addendum tip right
      [rf * Math.cos(t4 - 0.15), rf * Math.sin(t4 - 0.15)], // root end
    ]

    for (let i = 0; i < pts.length; i++) {
      const [px, py] = pts[i]
      toothPath += (k === 0 && i === 0 ? 'M' : 'L') + px.toFixed(1) + ' ' + py.toFixed(1) + ' '
    }
  }
  toothPath += 'Z'

  // Back face (darker for 3D)
  const backPath = document.createElementNS('http://www.w3.org/2000/svg', 'path')
  backPath.setAttribute('d', toothPath)
  backPath.setAttribute('fill', `rgba(${hexToRgb(color).join(',')}, 0.35)`)
  backPath.setAttribute('stroke', 'none')
  backPath.setAttribute('transform', 'translate(3 3)')
  g.appendChild(backPath)

  // Front face (main)
  const frontPath = document.createElementNS('http://www.w3.org/2000/svg', 'path')
  frontPath.setAttribute('d', toothPath)
  frontPath.setAttribute('fill', color)
  frontPath.setAttribute('stroke', '#0A1A3A')
  frontPath.setAttribute('stroke-width', '1.5')
  frontPath.setAttribute('stroke-linejoin', 'round')
  frontPath.setAttribute('filter', `url(#${filterId})`)
  g.appendChild(frontPath)

  // Inner circles (binding/spline effect)
  const innerRadius = 0.4 * radius
  const innerCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle')
  innerCircle.setAttribute('r', innerRadius)
  innerCircle.setAttribute('fill', 'none')
  innerCircle.setAttribute('stroke', 'rgba(255, 255, 255, 0.15)')
  innerCircle.setAttribute('stroke-width', '1.5')
  g.appendChild(innerCircle)

  // Spline teeth (3x around)
  const splineTeeth = 6
  for (let i = 0; i < splineTeeth; i++) {
    const angle = (i / splineTeeth) * Math.PI * 2
    const x1 = innerRadius * 0.8 * Math.cos(angle)
    const y1 = innerRadius * 0.8 * Math.sin(angle)
    const x2 = innerRadius * 1.2 * Math.cos(angle)
    const y2 = innerRadius * 1.2 * Math.sin(angle)

    const splinePath = document.createElementNS('http://www.w3.org/2000/svg', 'path')
    splinePath.setAttribute('d', `M ${x1} ${y1} L ${x2} ${y2}`)
    splinePath.setAttribute('stroke', 'rgba(255, 255, 255, 0.25)')
    splinePath.setAttribute('stroke-width', '1')
    splinePath.setAttribute('stroke-linecap', 'round')
    g.appendChild(splinePath)
  }

  // Center hub
  const hub = document.createElementNS('http://www.w3.org/2000/svg', 'circle')
  hub.setAttribute('r', innerRadius * 0.5)
  hub.setAttribute('fill', '#12285A')
  hub.setAttribute('stroke', '#7CC8FF')
  hub.setAttribute('stroke-width', '1')
  g.appendChild(hub)

  svg.appendChild(g)
  return g
}

function hexToRgb(hex) {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
  return result ? [
    parseInt(result[1], 16),
    parseInt(result[2], 16),
    parseInt(result[3], 16),
  ] : [0, 0, 0]
}

onMounted(() => {
  if (!svgRef.value) return

  // Clear
  svgRef.value.innerHTML = '<defs></defs>'

  // Gears: transmisi manual 3-gear (12, 18, 24 teeth)
  const gearConfigs = [
    { x: 100, y: 120, r: 60, teeth: 24, offset: 0, color: '#0A1A3A', label: 'Input' },
    { x: 220, y: 120, r: 45, teeth: 18, offset: 0, color: '#1E4FA8', label: 'Idler' },
    { x: 330, y: 120, r: 35, teeth: 12, offset: 0, color: '#7CC8FF', label: 'Output' },
  ]

  let angle = 0

  const animate = () => {
    gearConfigs.forEach((cfg, idx) => {
      const el = svgRef.value.querySelector(`[data-gear-${idx}]`)
      if (el) el.remove()
    })

    gearConfigs.forEach((cfg, idx) => {
      const g = drawGear3D(svgRef.value, cfg.x, cfg.y, cfg.r, cfg.teeth, angle * (idx % 2 === 0 ? 1 : -0.75))
      g.setAttribute(`data-gear-${idx}`, 'true')
    })

    angle += 1
    animationFrame = requestAnimationFrame(animate)
  }

  animate()
})

onBeforeUnmount(() => {
  if (animationFrame) cancelAnimationFrame(animationFrame)
})
</script>

<template>
  <svg
    ref="svgRef"
    width="100%"
    height="280"
    viewBox="0 0 420 250"
    class="gear-3d-svg"
    aria-label="Transmisi gear motor 3D interaktif"
  >
    <defs></defs>
  </svg>
</template>

<style scoped>
.gear-3d-svg {
  display: block;
  max-width: 100%;
  height: auto;
  background: linear-gradient(135deg, rgba(10, 26, 58, 0.5) 0%, rgba(30, 79, 168, 0.3) 100%);
  border-radius: 12px;
  padding: 12px;
}
</style>
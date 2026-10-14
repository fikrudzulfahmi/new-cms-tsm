<script setup>
import { onMounted, ref } from 'vue'

const containerRef = ref(null)
const canvasRef = ref(null)
let scene, camera, renderer, gears = []
let mouse = { x: 0, y: 0 }

onMounted(() => {
  if (!containerRef.value) return
  
  import('three').then(THREE => {
    initThreeScene(THREE)
  }).catch(() => {
    // Fallback to canvas animation if Three.js fails
    initCanvasAnimation()
  })
})

const initThreeScene = (THREE) => {
  const width = containerRef.value.clientWidth
  const height = containerRef.value.clientHeight

  scene = new THREE.Scene()
  scene.background = new THREE.Color(0x0f172a)

  camera = new THREE.PerspectiveCamera(75, width / height, 0.1, 1000)
  camera.position.z = 8

  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false })
  renderer.setSize(width, height)
  renderer.setPixelRatio(window.devicePixelRatio || 1)
  containerRef.value.appendChild(renderer.domElement)

  const ambientLight = new THREE.AmbientLight(0xffffff, 0.7)
  scene.add(ambientLight)

  const pointLight = new THREE.PointLight(0x3b82f6, 1.5)
  pointLight.position.set(8, 8, 8)
  scene.add(pointLight)

  const pointLight2 = new THREE.PointLight(0x60a5fa, 1)
  pointLight2.position.set(-8, -8, 8)
  scene.add(pointLight2)

  const configs = [
    { x: -3, y: 0, z: 0, size: 1.5, teeth: 32, speed: 0.008, color: 0x1e3a8a },
    { x: 0.5, y: 1.5, z: -1, size: 0.9, teeth: 20, speed: -0.012, color: 0x3b82f6 },
    { x: 2.2, y: -1, z: 0.5, size: 1.1, teeth: 26, speed: 0.010, color: 0x60a5fa },
    { x: 3.8, y: 0.8, z: -0.8, size: 0.6, teeth: 14, speed: -0.015, color: 0x1e40af },
  ]

  configs.forEach(cfg => {
    const gear = createGear(THREE, cfg.size, cfg.teeth, cfg.color)
    gear.position.set(cfg.x, cfg.y, cfg.z)
    gear.userData = { speed: cfg.speed }
    scene.add(gear)
    gears.push(gear)
  })

  document.addEventListener('mousemove', (e) => {
    mouse.x = (e.clientX / window.innerWidth) * 2 - 1
    mouse.y = -(e.clientY / window.innerHeight) * 2 + 1
  })

  const animate = () => {
    requestAnimationFrame(animate)
    gears.forEach(g => g.rotation.z += g.userData.speed)
    scene.rotation.x = mouse.y * 0.3
    scene.rotation.y = mouse.x * 0.3
    renderer.render(scene, camera)
  }
  animate()

  window.addEventListener('resize', () => {
    const w = containerRef.value?.clientWidth || 400
    const h = containerRef.value?.clientHeight || 300
    camera.aspect = w / h
    camera.updateProjectionMatrix()
    renderer.setSize(w, h)
  })
}

const createGear = (THREE, radius, teeth, color) => {
  const geometry = new THREE.BufferGeometry()
  const positions = [], indices = []
  const toothH = 0.25

  for (let i = 0; i < teeth * 2; i++) {
    const angle = (i / (teeth * 2)) * Math.PI * 2
    const r = i % 2 === 0 ? radius + toothH : radius - toothH * 0.3
    positions.push(Math.cos(angle) * r, Math.sin(angle) * r, 0)
  }

  const center = positions.length / 3
  positions.push(0, 0, 0)

  for (let i = 0; i < teeth * 2; i++) {
    const next = (i + 1) % (teeth * 2)
    indices.push(i, center, next)
  }

  geometry.setAttribute('position', new THREE.BufferAttribute(new Float32Array(positions), 3))
  geometry.setIndex(new THREE.BufferAttribute(new Uint32Array(indices), 1))
  geometry.computeVertexNormals()

  const material = new THREE.MeshPhongMaterial({
    color,
    shininess: 120,
    emissive: color,
    emissiveIntensity: 0.15
  })

  return new THREE.Mesh(geometry, material)
}

const initCanvasAnimation = () => {
  const canvas = canvasRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  canvas.width = containerRef.value.clientWidth
  canvas.height = containerRef.value.clientHeight

  let time = 0
  const animate = () => {
    time += 0.02
    ctx.fillStyle = '#0f172a'
    ctx.fillRect(0, 0, canvas.width, canvas.height)

    const gears = [
      { x: canvas.width * 0.25, y: canvas.height / 2, r: 50, teeth: 12, t: time },
      { x: canvas.width * 0.65, y: canvas.height * 0.35, r: 35, teeth: 8, t: -time * 1.5 },
      { x: canvas.width * 0.55, y: canvas.height * 0.7, r: 40, teeth: 10, t: time * 0.8 },
    ]

    gears.forEach(g => {
      ctx.save()
      ctx.translate(g.x, g.y)
      ctx.rotate(g.t)
      ctx.fillStyle = '#1e3a8a'
      ctx.strokeStyle = '#3b82f6'
      ctx.lineWidth = 2
      ctx.beginPath()
      for (let i = 0; i < g.teeth * 2; i++) {
        const a = (i / (g.teeth * 2)) * Math.PI * 2
        const r = i % 2 === 0 ? g.r : g.r * 0.7
        const x = Math.cos(a) * r
        const y = Math.sin(a) * r
        i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y)
      }
      ctx.closePath()
      ctx.fill()
      ctx.stroke()
      ctx.fillStyle = '#3b82f6'
      ctx.beginPath()
      ctx.arc(0, 0, g.r * 0.2, 0, Math.PI * 2)
      ctx.fill()
      ctx.restore()
    })

    requestAnimationFrame(animate)
  }
  animate()
}
</script>

<template>
  <div 
    ref="containerRef" 
    class="w-full h-96 rounded-2xl overflow-hidden shadow-2xl border border-blue-400/20 relative"
    style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%)"
  >
    <canvas 
      ref="canvasRef" 
      class="absolute inset-0 w-full h-full"
    ></canvas>
    <div class="absolute inset-0 flex items-center justify-center text-center pointer-events-none opacity-40">
      <p class="text-xs text-blue-300">Gerakkan mouse untuk interaksi</p>
    </div>
  </div>
</template>

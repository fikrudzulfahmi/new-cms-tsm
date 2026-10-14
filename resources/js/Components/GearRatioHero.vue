<script setup>
import { onMounted, ref } from 'vue'
import * as THREE from 'three'

const containerRef = ref(null)
const scene = ref(null)
const camera = ref(null)
const renderer = ref(null)
const gears = ref([])
const mouse = ref({ x: 0, y: 0 })

onMounted(() => {
  if (!containerRef.value) return

  // Scene setup
  scene.value = new THREE.Scene()
  scene.value.background = new THREE.Color(0x0f172a) // Navy dark
  
  const width = containerRef.value.clientWidth
  const height = containerRef.value.clientHeight

  camera.value = new THREE.PerspectiveCamera(75, width / height, 0.1, 1000)
  camera.value.position.z = 8

  renderer.value = new THREE.WebGLRenderer({ antialias: true, alpha: true })
  renderer.value.setSize(width, height)
  renderer.value.setPixelRatio(window.devicePixelRatio)
  containerRef.value.appendChild(renderer.value.domElement)

  // Lighting
  const ambientLight = new THREE.AmbientLight(0xffffff, 0.6)
  scene.value.add(ambientLight)

  const pointLight = new THREE.PointLight(0x3b82f6, 1)
  pointLight.position.set(10, 10, 10)
  scene.value.add(pointLight)

  const pointLight2 = new THREE.PointLight(0x60a5fa, 0.5)
  pointLight2.position.set(-10, -10, 10)
  scene.value.add(pointLight2)

  // Create gears
  const gearConfigs = [
    { x: -3, y: 0, z: 0, size: 1.5, teeth: 32, speed: 0.01, color: 0x1e3a8a },
    { x: 0.5, y: 1.5, z: -1, size: 0.9, teeth: 20, speed: -0.015, color: 0x3b82f6 },
    { x: 2, y: -1, z: 0.5, size: 1.2, teeth: 28, speed: 0.012, color: 0x60a5fa },
    { x: 3.5, y: 0.5, z: -0.8, size: 0.7, teeth: 16, speed: -0.018, color: 0x1e40af },
  ]

  gearConfigs.forEach(config => {
    const gear = createGear(config.size, config.teeth, config.color)
    gear.position.set(config.x, config.y, config.z)
    gear.userData = { speed: config.speed, rotationAxis: new THREE.Vector3(0, 0, 1) }
    scene.value.add(gear)
    gears.value.push(gear)
  })

  // Mouse movement
  document.addEventListener('mousemove', (e) => {
    mouse.value.x = (e.clientX / window.innerWidth) * 2 - 1
    mouse.value.y = -(e.clientY / window.innerHeight) * 2 + 1
  })

  // Animation loop
  const animate = () => {
    requestAnimationFrame(animate)

    // Rotate gears
    gears.value.forEach(gear => {
      gear.rotation.z += gear.userData.speed
    })

    // Tilt scene based on mouse
    scene.value.rotation.x = mouse.value.y * 0.5
    scene.value.rotation.y = mouse.value.x * 0.5

    renderer.value.render(scene.value, camera.value)
  }

  animate()

  // Handle resize
  const handleResize = () => {
    const w = containerRef.value.clientWidth
    const h = containerRef.value.clientHeight
    camera.value.aspect = w / h
    camera.value.updateProjectionMatrix()
    renderer.value.setSize(w, h)
  }

  window.addEventListener('resize', handleResize)

  return () => {
    window.removeEventListener('resize', handleResize)
    renderer.value?.dispose()
  }
})

const createGear = (radius, teeth, color) => {
  const geometry = new THREE.BufferGeometry()
  const positions = []
  const indices = []

  const toothHeight = 0.3
  const toothWidth = 0.5

  // Create gear teeth
  for (let i = 0; i < teeth * 2; i++) {
    const angle = (i / (teeth * 2)) * Math.PI * 2
    const r = i % 2 === 0 ? radius + toothHeight : radius - toothHeight * 0.5
    const x = Math.cos(angle) * r
    const y = Math.sin(angle) * r
    positions.push(x, y, 0)
  }

  // Center
  const centerIdx = positions.length / 3
  positions.push(0, 0, 0)

  // Create faces
  for (let i = 0; i < teeth * 2; i++) {
    const next = (i + 1) % (teeth * 2)
    indices.push(i, centerIdx, next)
  }

  geometry.setAttribute('position', new THREE.BufferAttribute(new Float32Array(positions), 3))
  geometry.setIndex(new THREE.BufferAttribute(new Uint32Array(indices), 1))
  geometry.computeVertexNormals()

  const material = new THREE.MeshPhongMaterial({ 
    color: color,
    shininess: 100,
    emissive: color,
    emissiveIntensity: 0.2
  })

  const mesh = new THREE.Mesh(geometry, material)
  return mesh
}
</script>

<template>
  <div 
    ref="containerRef" 
    class="w-full h-96 rounded-2xl overflow-hidden shadow-2xl border border-blue-400/20"
    style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%)"
  >
    <div class="absolute inset-0 flex items-center justify-center text-center pointer-events-none">
      <div class="text-blue-200/40">
        <p class="text-sm">Gerakkan mouse untuk interaksi</p>
      </div>
    </div>
  </div>
</template>

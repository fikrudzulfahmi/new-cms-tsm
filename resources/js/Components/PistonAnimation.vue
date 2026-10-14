<script setup>
import { onMounted } from 'vue'

onMounted(() => {
  const canvas = document.getElementById('pistonCanvas')
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  
  let time = 0
  const animate = () => {
    time += 0.03
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    
    // Engine block
    ctx.fillStyle = '#1e3a8a'
    ctx.fillRect(40, 120, 200, 80)
    ctx.strokeStyle = '#0f172a'
    ctx.lineWidth = 2
    ctx.strokeRect(40, 120, 200, 80)
    
    // Cylinder 1
    drawCylinder(ctx, 80, 120, Math.sin(time) * 15)
    
    // Cylinder 2
    drawCylinder(ctx, 140, 120, Math.sin(time + Math.PI / 2) * 15)
    
    // Cylinder 3
    drawCylinder(ctx, 200, 120, Math.sin(time + Math.PI) * 15)
    
    // Crankshaft
    ctx.strokeStyle = '#0f172a'
    ctx.lineWidth = 3
    ctx.beginPath()
    ctx.arc(120, 200, 6, 0, Math.PI * 2)
    ctx.stroke()
    ctx.fillStyle = '#1e3a8a'
    ctx.fill()
    
    requestAnimationFrame(animate)
  }
  
  const drawCylinder = (ctx, x, y, pistonOffset) => {
    // Piston rod
    ctx.strokeStyle = '#3b82f6'
    ctx.lineWidth = 3
    ctx.beginPath()
    ctx.moveTo(x, y + 40 + pistonOffset)
    ctx.lineTo(x, y + 70)
    ctx.stroke()
    
    // Piston head
    ctx.fillStyle = '#60a5fa'
    ctx.fillRect(x - 12, y + 35 + pistonOffset, 24, 12)
    ctx.strokeStyle = '#0f172a'
    ctx.lineWidth = 1.5
    ctx.strokeRect(x - 12, y + 35 + pistonOffset, 24, 12)
    
    // Cylinder wall
    ctx.strokeStyle = '#1e3a8a'
    ctx.lineWidth = 2
    ctx.beginPath()
    ctx.moveTo(x - 8, y + 40)
    ctx.lineTo(x - 8, y + 80)
    ctx.moveTo(x + 8, y + 40)
    ctx.lineTo(x + 8, y + 80)
    ctx.stroke()
  }
  
  animate()
})
</script>

<template>
  <div class="flex items-center justify-center p-4">
    <canvas id="pistonCanvas" width="280" height="260" class="drop-shadow-2xl"></canvas>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'

onMounted(() => {
  const canvas = document.getElementById('gearCanvas')
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  
  let angle = 0
  const animate = () => {
    angle += 2
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    
    // Gear 1 (left)
    ctx.save()
    ctx.translate(80, 80)
    ctx.rotate((angle * Math.PI) / 180)
    drawGear(ctx, 30, 8, 8, 15)
    ctx.restore()
    
    // Gear 2 (right, opposite rotation)
    ctx.save()
    ctx.translate(200, 80)
    ctx.rotate((-angle * Math.PI) / 180)
    drawGear(ctx, 25, 8, 6, 12)
    ctx.restore()
    
    requestAnimationFrame(animate)
  }
  
  const drawGear = (ctx, radius, teeth, toothHeight, toothWidth) => {
    ctx.fillStyle = '#1e3a8a'
    ctx.strokeStyle = '#0f172a'
    ctx.lineWidth = 2
    
    ctx.beginPath()
    for (let i = 0; i < teeth * 2; i++) {
      const angle = (i * Math.PI) / teeth
      const r = i % 2 === 0 ? radius + toothHeight : radius
      const x = r * Math.cos(angle)
      const y = r * Math.sin(angle)
      i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y)
    }
    ctx.closePath()
    ctx.fill()
    ctx.stroke()
    
    // Center
    ctx.fillStyle = '#0f172a'
    ctx.beginPath()
    ctx.arc(0, 0, radius / 3, 0, Math.PI * 2)
    ctx.fill()
  }
  
  animate()
})
</script>

<template>
  <div class="flex items-center justify-center p-4">
    <canvas id="gearCanvas" width="280" height="160" class="drop-shadow-lg"></canvas>
  </div>
</template>

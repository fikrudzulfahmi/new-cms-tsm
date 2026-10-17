<script setup>
import { img, hideImg, initials } from '@/lib/site'
defineProps({ teacher: { type: Object, required: true } })
</script>

<template>
  <article class="card p-5 text-center flex flex-col items-center">
    <div class="w-full aspect-square rounded-2xl overflow-hidden bg-ice mb-4 flex items-center justify-center">
      <img v-if="teacher.photo" :src="img(teacher.photo)" :alt="teacher.name" loading="lazy" class="w-full h-full object-cover" @error="hideImg" />
      <span v-else class="font-display font-bold text-4xl text-label" aria-hidden="true">{{ initials(teacher.name) }}</span>
    </div>
    <h3 class="font-display font-bold text-navy leading-snug">{{ teacher.name }}</h3>
    <p v-if="teacher.position" class="font-mono text-[11px] tracking-wider uppercase text-label mt-1">{{ teacher.position }}</p>
    <p v-if="teacher.subject" class="text-sm text-slate-500 mt-1">{{ teacher.subject }}</p>
    <slot />
  </article>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
defineProps({
  eyebrow: String,
  title: { type: String, required: true },
  subtitle: String,
  // [{ label, href? }] — item terakhir = halaman saat ini
  crumbs: { type: Array, default: () => [] },
})
</script>

<template>
  <header class="grid-dark page-hero border-b border-accent/20">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <nav v-if="crumbs.length" aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 font-mono text-xs tracking-wider uppercase text-on-navy-subtle mb-6">
        <Link href="/" class="hover:text-accent">Beranda</Link>
        <template v-for="(c, i) in crumbs" :key="i">
          <span aria-hidden="true">/</span>
          <Link v-if="c.href && i < crumbs.length - 1" :href="c.href" class="hover:text-accent">{{ c.label }}</Link>
          <span v-else class="text-white truncate max-w-[60vw]" aria-current="page">{{ c.label }}</span>
        </template>
      </nav>
      <span v-if="eyebrow" class="badge-status mb-5">{{ eyebrow }}</span>
      <h1 class="font-display font-extrabold text-white mb-4 max-w-3xl">{{ title }}</h1>
      <p v-if="subtitle" class="text-lg max-w-2xl">{{ subtitle }}</p>
      <slot />
    </div>
  </header>
</template>

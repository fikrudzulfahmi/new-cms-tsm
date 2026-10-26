<script setup>
import { router } from '@inertiajs/vue3'
import LmsShell from '@/Components/Lms/LmsShell.vue'
const props = defineProps({ course: Object, lesson: Object, done: Boolean })
function complete() { router.post(`/belajar/materi/${props.lesson.id}/selesai`, {}, { preserveScroll: true }) }
</script>

<template>
  <LmsShell :title="lesson.title" :back="`/belajar/mapel/${course.id}`" :backLabel="course.title">
    <article class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4">
      <div v-if="lesson.type==='video' && lesson.video_url" class="aspect-video">
        <iframe :src="lesson.video_url" class="w-full h-full rounded-xl" allowfullscreen referrerpolicy="no-referrer"
                sandbox="allow-scripts allow-same-origin allow-presentation" allow="fullscreen; picture-in-picture"></iframe>
      </div>
      <div v-if="lesson.type==='file' && lesson.has_file">
        <a :href="`/berkas-materi/${lesson.id}`" target="_blank" rel="noopener" class="inline-block rounded-xl bg-navy text-white text-sm font-semibold px-4 py-2">Buka {{ lesson.attachment_name }}</a>
      </div>
      <p v-if="lesson.content" class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">{{ lesson.content }}</p>
      <div class="pt-2">
        <span v-if="done" class="text-sm text-emerald-700 font-semibold">&#10003; Sudah selesai</span>
        <button v-else class="rounded-xl bg-accent text-navy text-sm font-bold px-4 py-2" @click="complete">Tandai selesai</button>
      </div>
    </article>
  </LmsShell>
</template>

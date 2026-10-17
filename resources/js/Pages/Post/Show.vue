<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps(['post', 'relatedPosts'])

const commentForm = useForm({ name: '', email: '', body: '', parent_id: null, website: '' })
const replyingTo = ref(null)

const submitComment = () => {
  commentForm.parent_id = replyingTo.value
  commentForm.post(`/berita/${props.post.id}/comment`, {
    preserveScroll: true,
    onSuccess: () => { commentForm.reset(); replyingTo.value = null }
  })
}

const likes = ref(props.post.likes_count || 0)
const dislikes = ref(props.post.dislikes_count || 0)
const myReaction = ref(props.myReaction || 0)
const shareUrl = typeof window !== 'undefined' ? window.location.href : ''
const reacting = ref(false)

const react = async (type) => {
  if (reacting.value) return
  reacting.value = true
  try {
    const { data } = await axios.post(`/berita/${props.post.id}/react`, { value: type === 'like' ? 1 : -1 })
    likes.value = data.likes
    dislikes.value = data.dislikes
    myReaction.value = data.my_reaction
  } catch (e) {
    // Diamkan: pengunjung bisa mencoba lagi tanpa halaman error.
  } finally {
    reacting.value = false
  }
}

const trackShare = () => {
  axios.post(`/berita/${props.post.id}/share`).catch(() => {})
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' }) : ''
</script>

<template>
  <AppLayout>
    <Head><title>{{ post.title }} — TSM</title></Head>
    <div class="grid-dark h-24 border-b border-accent/20"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 font-mono text-xs tracking-wider uppercase text-slate-500 mb-6">
        <Link href="/" class="hover:text-label">Beranda</Link>
        <span>/</span>
        <Link href="/berita" class="hover:text-label">Berita</Link>
        <span>/</span>
        <span class="text-slate-800 truncate">{{ post.title }}</span>
      </div>

      <!-- Category & Title -->
      <div v-if="post.category" class="inline-block px-3 py-1 rounded-full text-xs font-semibold mb-3" :style="`background:${post.category.color}20;color:${post.category.color}`">{{ post.category.name }}</div>
      <h1 class="font-display text-3xl sm:text-[44px] font-extrabold text-navy mb-5 leading-[1.1] tracking-[-1px]">{{ post.title }}</h1>
      <div class="flex flex-wrap items-center gap-4 text-sm text-slate-500 pb-6 border-b border-slate-200 mb-8">
        <span>Oleh <strong>{{ post.user?.name || 'Admin' }}</strong></span>
        <span>·</span>
        <span>{{ formatDate(post.published_at) }}</span>
        <span>·</span>
        <span class="flex items-center gap-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>{{ post.views_count }} dilihat</span>
        <span>·</span>
        <span>Rating: <strong>{{ post.rating }}%</strong></span>
      </div>

      <!-- Cover Image -->
      <div v-if="post.cover_image" class="mb-8 rounded-[20px] overflow-hidden border border-line max-h-[450px]">
        <img :src="`/storage/${post.cover_image}`" class="w-full h-full object-cover" :alt="post.cover_alt || post.title" />
      </div>

      <!-- Content -->
      <div class="prose prose-blue max-w-none text-slate-700 leading-relaxed mb-10" v-html="post.body"></div>

      <!-- Reactions & Share -->
      <div class="flex flex-wrap items-center justify-between gap-4 py-6 border-y border-slate-200 mb-12">
        <div class="flex items-center gap-3">
          <button @click="react('like')" :disabled="reacting" :aria-pressed="myReaction === 1" :class="['flex items-center gap-2 px-4 py-2 rounded-full border font-semibold text-sm transition disabled:opacity-60', myReaction === 1 ? 'bg-blue-900 border-blue-900 text-white' : 'border-line bg-ice text-blue-900 hover:bg-blue-100']">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
            Suka ({{ likes }})
          </button>
          <button @click="react('unlike')" :disabled="reacting" :aria-pressed="myReaction === -1" :class="['flex items-center gap-2 px-4 py-2 rounded-full border font-semibold text-sm transition disabled:opacity-60', myReaction === -1 ? 'bg-slate-800 border-slate-800 text-white' : 'border-line bg-white text-slate-700 hover:bg-slate-200']">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.737 3h4.018c.163 0 .326.02.485.06L17 4m-7 10v5a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"/></svg>
            Tidak Suka ({{ dislikes }})
          </button>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm text-slate-500 font-medium">Bagikan:</span>
          <a :href="`https://wa.me/?text=${encodeURIComponent(post.title + ' ' + shareUrl)}`" target="_blank" rel="noopener noreferrer" @click="trackShare" aria-label="Bagikan ke WhatsApp" class="w-9 h-9 bg-navy text-accent rounded-full flex items-center justify-center hover:bg-green-600 transition">WA</a>
          <a :href="`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`" target="_blank" rel="noopener noreferrer" @click="trackShare" aria-label="Bagikan ke Facebook" class="w-9 h-9 bg-navy text-accent rounded-full flex items-center justify-center hover:bg-blue-700 transition">FB</a>
        </div>
      </div>

      <!-- Comments Section -->
      <div class="mb-16">
        <h3 class="font-bold text-xl text-slate-800 mb-6">Komentar ({{ post.comments_count ?? post.comments?.length ?? 0 }})</h3>
        
        <!-- Comment List -->
        <div class="space-y-4 mb-8">
          <div v-for="c in post.comments" :key="c.id" class="card p-5">
            <div class="flex items-center justify-between mb-2">
              <div class="font-bold text-sm text-slate-800">{{ c.name }}</div>
              <div class="text-xs text-slate-400">{{ formatDate(c.created_at) }}</div>
            </div>
            <p class="text-slate-600 text-sm mb-3">{{ c.body }}</p>
            <button @click="replyingTo = c.id" class="text-xs text-blue-700 font-semibold hover:underline">Balas</button>

            <!-- Replies -->
            <div v-if="c.replies?.length" class="mt-4 pl-4 border-l-2 border-slate-200 space-y-3">
              <div v-for="reply in c.replies" :key="reply.id" class="bg-slate-50 p-3 rounded-xl">
                <div class="flex items-center justify-between mb-1">
                  <div class="font-bold text-xs text-slate-800">{{ reply.name }}</div>
                  <div class="text-xs text-slate-400">{{ formatDate(reply.created_at) }}</div>
                </div>
                <p class="text-slate-600 text-xs">{{ reply.body }}</p>
              </div>
            </div>
          </div>
          <div v-if="!post.comments?.length" class="text-slate-400 text-sm italic">Belum ada komentar. Jadilah yang pertama berkomentar!</div>
        </div>

        <!-- Add Comment Form -->
        <div class="card p-6 bg-slate-50">
          <h4 class="font-bold text-slate-800 mb-4">{{ replyingTo ? 'Balas Komentar' : 'Tinggalkan Komentar' }}</h4>
          <button v-if="replyingTo" @click="replyingTo=null" class="text-xs text-red-500 mb-3 block">Batalkan balasan</button>
          <form @submit.prevent="submitComment" class="space-y-4">
            <input v-model="commentForm.website" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="hidden" />
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="form-label">Nama *</label>
                <input v-model="commentForm.name" type="text" placeholder="Nama Anda" class="form-input" />
                <p v-if="commentForm.errors.name" class="text-red-500 text-xs mt-1">{{ commentForm.errors.name }}</p>
              </div>
              <div>
                <label class="form-label">Email <span class="text-slate-400 font-normal">(opsional, tidak ditampilkan)</span></label>
                <input v-model="commentForm.email" type="email" placeholder="email@anda.com" class="form-input" />
                <p v-if="commentForm.errors.email" class="text-red-500 text-xs mt-1">{{ commentForm.errors.email }}</p>
              </div>
            </div>
            <div>
              <label class="form-label">Komentar *</label>
              <textarea v-model="commentForm.body" rows="3" placeholder="Tulis komentar..." class="form-input resize-none"></textarea>
              <p v-if="commentForm.errors.body" class="text-red-500 text-xs mt-1">{{ commentForm.errors.body }}</p>
            </div>
            <button type="submit" :disabled="commentForm.processing" class="btn-primary text-sm">
              {{ commentForm.processing ? 'Mengirim...' : 'Kirim Komentar' }}
            </button>
          </form>
        </div>
      </div>

      <!-- Related Posts -->
      <div v-if="relatedPosts?.length">
        <h3 class="font-bold text-xl text-slate-800 mb-6">Berita Terkait</h3>
        <div class="grid sm:grid-cols-3 gap-6">
          <Link v-for="rp in relatedPosts" :key="rp.id" :href="`/berita/${rp.slug}`" class="card hover:border-accent transition block group">
            <div class="h-36 overflow-hidden">
              <img v-if="rp.cover_image" :src="`/storage/${rp.cover_image}`" :alt="rp.title" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition" />
              <div v-else class="w-full h-full bg-slate-100 flex items-center justify-center text-slate-300">TSM</div>
            </div>
            <div class="p-4">
              <h4 class="font-bold text-slate-800 text-sm line-clamp-2 group-hover:text-label">{{ rp.title }}</h4>
              <p class="text-xs text-slate-400 mt-2">{{ formatDate(rp.published_at) }}</p>
            </div>
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

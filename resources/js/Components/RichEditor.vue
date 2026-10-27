<script setup>
import { promptDialog, alertDialog } from '@/dialog'
import { ref, watch, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import TextAlign from '@tiptap/extension-text-align'
import { TableKit } from '@tiptap/extension-table'
import Youtube from '@tiptap/extension-youtube'
import Image from '@tiptap/extension-image'
import { TextStyle, Color } from '@tiptap/extension-text-style'
import Highlight from '@tiptap/extension-highlight'
import Subscript from '@tiptap/extension-subscript'
import Superscript from '@tiptap/extension-superscript'
import { Placeholder, CharacterCount } from '@tiptap/extensions'
import axios from 'axios'

const props = defineProps({ modelValue: { type: String, default: '' }, uploadUrl: { type: String, default: '/admin/pengaturan/upload' } })
const emit = defineEmits(['update:modelValue'])

const sourceMode = ref(false)
const source = ref(props.modelValue)
const fileInput = ref(null)
const uploading = ref(false)

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit.configure({
      heading: { levels: [2, 3, 4] },
      link: { openOnClick: false, autolink: true, defaultProtocol: 'https' },
    }),
    TextAlign.configure({ types: ['heading', 'paragraph'] }),
    TableKit.configure({ table: { resizable: true } }),
    Youtube.configure({ controls: true, nocookie: true, width: 640, height: 360, HTMLAttributes: { class: 'yt-embed' } }),
    Image.configure({ HTMLAttributes: { class: 'rounded-xl' } }),
    TextStyle, Color, Highlight.configure({ multicolor: false }), Subscript, Superscript,
    Placeholder.configure({ placeholder: 'Tulis isi berita di sini… (pakai toolbar di atas untuk format)' }),
    CharacterCount,
  ],
  editorProps: { attributes: { class: 'prose prose-blue max-w-none min-h-[360px] px-5 py-4 focus:outline-none' } },
  onUpdate: ({ editor }) => emit('update:modelValue', editor.isEmpty ? '' : editor.getHTML()),
})

watch(() => props.modelValue, (v) => {
  if (editor.value && !sourceMode.value && v !== editor.value.getHTML() && !(v === '' && editor.value.isEmpty)) {
    editor.value.commands.setContent(v || '', { emitUpdate: false })
  }
})
onBeforeUnmount(() => editor.value?.destroy())

const e = () => editor.value.chain().focus()
const is = (n, a) => editor.value?.isActive(n, a)

const toggleSource = () => {
  if (sourceMode.value) {
    editor.value.commands.setContent(source.value || '', { emitUpdate: true })
    emit('update:modelValue', source.value)
  } else {
    source.value = editor.value.getHTML()
  }
  sourceMode.value = !sourceMode.value
}

const setLink = async () => {
  const prev = editor.value.getAttributes('link').href || ''
  const url = await promptDialog('URL link (kosongkan untuk hapus):', { value: prev || 'https://' })
  if (url === null) return
  if (url === '' || url === 'https://') return e().extendMarkRange('link').unsetLink().run()
  const external = /^https?:\/\//.test(url) && !url.includes(window.location.host)
  e().extendMarkRange('link').setLink({ href: url, target: external ? '_blank' : null, rel: external ? 'noopener noreferrer' : null }).run()
}

const addYoutube = async () => {
  const url = await promptDialog('Tempel link YouTube (youtube.com/watch?v=… atau youtu.be/…):')
  if (!url) return
  const ok = editor.value.chain().focus().setYoutubeVideo({ src: url.trim() }).run()
  if (!ok) alertDialog('Link YouTube tidak valid.')
}

const pickImage = () => fileInput.value.click()
const uploadImage = async (ev) => {
  const file = ev.target.files[0]; ev.target.value = ''
  if (!file) return
  uploading.value = true
  try {
    const fd = new FormData(); fd.append('image', file)
    const { data } = await axios.post(props.uploadUrl, fd)
    const alt = await promptDialog('Teks alternatif gambar (alt, penting untuk SEO):', { value: '' }) || ''
    e().setImage({ src: data.url, alt }).run()
  } catch (err) {
    alertDialog('Upload gagal: ' + (err.response?.data?.message || err.message))
  } finally { uploading.value = false }
}
const imageByUrl = async () => {
  const url = await promptDialog('URL gambar:'); if (!url) return
  const alt = await promptDialog('Teks alternatif (alt):', { value: '' }) || ''
  e().setImage({ src: url, alt }).run()
}
const setColor = (ev) => e().setColor(ev.target.value).run()

const groups = [
  [
    { t: 'Undo', i: 'M9 14L4 9l5-5M4 9h11a5 5 0 010 10h-3', a: () => e().undo().run() },
    { t: 'Redo', i: 'M15 14l5-5-5-5M20 9H9a5 5 0 000 10h3', a: () => e().redo().run() },
  ],
  [
    { t: 'Tebal (Ctrl+B)', l: 'B', cls: 'font-bold', a: () => e().toggleBold().run(), on: () => is('bold') },
    { t: 'Miring (Ctrl+I)', l: 'I', cls: 'italic font-serif', a: () => e().toggleItalic().run(), on: () => is('italic') },
    { t: 'Garis bawah (Ctrl+U)', l: 'U', cls: 'underline', a: () => e().toggleUnderline().run(), on: () => is('underline') },
    { t: 'Coret', l: 'S', cls: 'line-through', a: () => e().toggleStrike().run(), on: () => is('strike') },
    { t: 'Stabilo', l: 'H', cls: 'bg-yellow-200 px-1', a: () => e().toggleHighlight().run(), on: () => is('highlight') },
    { t: 'Subscript', l: 'x₂', a: () => e().toggleSubscript().run(), on: () => is('subscript') },
    { t: 'Superscript', l: 'x²', a: () => e().toggleSuperscript().run(), on: () => is('superscript') },
    { t: 'Kode', i: 'M8 9l-3 3 3 3M16 9l3 3-3 3', a: () => e().toggleCode().run(), on: () => is('code') },
    { t: 'Hapus format', i: 'M6 6l12 12M9 4h9M13 4l-4 16', a: () => e().unsetAllMarks().clearNodes().run() },
  ],
  [
    { t: 'Paragraf', l: 'P', a: () => e().setParagraph().run(), on: () => is('paragraph') },
    { t: 'Judul 2', l: 'H2', a: () => e().toggleHeading({ level: 2 }).run(), on: () => is('heading', { level: 2 }) },
    { t: 'Judul 3', l: 'H3', a: () => e().toggleHeading({ level: 3 }).run(), on: () => is('heading', { level: 3 }) },
    { t: 'Judul 4', l: 'H4', a: () => e().toggleHeading({ level: 4 }).run(), on: () => is('heading', { level: 4 }) },
    { t: 'Kutipan', i: 'M7 8h4v4H8a1 1 0 00-1 1v3M14 8h4v4h-3a1 1 0 00-1 1v3', a: () => e().toggleBlockquote().run(), on: () => is('blockquote') },
    { t: 'Blok kode', i: 'M4 5h16v14H4zM8 10l-2 2 2 2M16 10l2 2-2 2', a: () => e().toggleCodeBlock().run(), on: () => is('codeBlock') },
    { t: 'Garis pemisah', i: 'M4 12h16', a: () => e().setHorizontalRule().run() },
  ],
  [
    { t: 'Rata kiri', i: 'M4 6h16M4 10h10M4 14h16M4 18h10', a: () => e().setTextAlign('left').run(), on: () => is({ textAlign: 'left' }) },
    { t: 'Rata tengah', i: 'M4 6h16M7 10h10M4 14h16M7 18h10', a: () => e().setTextAlign('center').run(), on: () => is({ textAlign: 'center' }) },
    { t: 'Rata kanan', i: 'M4 6h16M10 10h10M4 14h16M10 18h10', a: () => e().setTextAlign('right').run(), on: () => is({ textAlign: 'right' }) },
    { t: 'Rata kiri-kanan', i: 'M4 6h16M4 10h16M4 14h16M4 18h16', a: () => e().setTextAlign('justify').run(), on: () => is({ textAlign: 'justify' }) },
  ],
  [
    { t: 'Daftar bullet', i: 'M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01', a: () => e().toggleBulletList().run(), on: () => is('bulletList') },
    { t: 'Daftar bernomor', i: 'M10 6h10M10 12h10M10 18h10M4 5h1v3M4 11h2l-2 3h2M4 17h2v3H4', a: () => e().toggleOrderedList().run(), on: () => is('orderedList') },
    { t: 'Masuk (indent)', i: 'M4 6h16M10 10h10M10 14h10M4 18h16M4 10l3 2-3 2', a: () => e().sinkListItem('listItem').run() },
    { t: 'Keluar (outdent)', i: 'M4 6h16M10 10h10M10 14h10M4 18h16M7 10l-3 2 3 2', a: () => e().liftListItem('listItem').run() },
  ],
  [
    { t: 'Link', i: 'M10 14a4 4 0 005.66 0l3-3a4 4 0 00-5.66-5.66l-1 1M14 10a4 4 0 00-5.66 0l-3 3a4 4 0 005.66 5.66l1-1', a: setLink, on: () => is('link') },
    { t: 'Upload gambar', i: 'M4 5h16v14H4zM4 15l4-4 4 4 3-3 5 5M15 9h.01', a: pickImage },
    { t: 'Gambar dari URL', i: 'M4 5h16v14H4zM9 12h6M12 9v6', a: imageByUrl },
    { t: 'Video YouTube', i: 'M3 8a3 3 0 013-3h12a3 3 0 013 3v8a3 3 0 01-3 3H6a3 3 0 01-3-3zM10 9l5 3-5 3z', a: addYoutube, on: () => is('youtube') },
    { t: 'Sisipkan tabel 3×3', i: 'M4 5h16v14H4zM4 10h16M4 15h16M10 5v14M15 5v14', a: () => e().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run() },
  ],
]
const tableOps = [
  { l: '+ Baris', a: () => e().addRowAfter().run() },
  { l: '− Baris', a: () => e().deleteRow().run() },
  { l: '+ Kolom', a: () => e().addColumnAfter().run() },
  { l: '− Kolom', a: () => e().deleteColumn().run() },
  { l: 'Gabung/Pisah sel', a: () => e().mergeOrSplit().run() },
  { l: 'Header', a: () => e().toggleHeaderRow().run() },
  { l: 'Hapus tabel', a: () => e().deleteTable().run() },
]
</script>

<template>
  <div class="rich-editor border border-line rounded-2xl bg-white overflow-hidden focus-within:border-accent">
    <div class="sticky top-0 z-10 flex flex-wrap items-center gap-1 px-2 py-2 border-b border-line bg-ice-section">
      <template v-for="(g, gi) in groups" :key="gi">
        <button v-for="b in g" :key="b.t" type="button" :title="b.t" :aria-label="b.t" @click="b.a" :disabled="sourceMode || !editor"
          :class="['h-8 min-w-8 px-1.5 rounded-lg text-sm flex items-center justify-center border transition disabled:opacity-40',
                   b.on && b.on() ? 'bg-accent/30 border-accent text-label font-semibold' : 'border-transparent text-navy hover:bg-ice']">
          <svg v-if="b.i" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path :d="b.i"/></svg>
          <span v-else :class="b.cls">{{ b.l }}</span>
        </button>
        <span class="w-px h-6 bg-line mx-1"></span>
      </template>
      <label title="Warna teks" class="h-8 w-8 rounded-lg hover:bg-ice flex items-center justify-center cursor-pointer">
        <span class="font-bold text-sm" :style="{ color: editor?.getAttributes('textStyle').color || '#0A1A3A' }">A</span>
        <input type="color" class="sr-only" @input="setColor" :disabled="sourceMode" />
      </label>
      <button type="button" @click="toggleSource" :class="['ml-auto h-8 px-3 rounded-lg font-mono text-xs', sourceMode ? 'bg-navy text-accent' : 'text-label hover:bg-ice']">&lt;/&gt; HTML</button>
      <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="uploadImage" />
    </div>

    <div v-if="editor && is('table') && !sourceMode" class="flex flex-wrap gap-1 px-2 py-1.5 border-b border-line bg-white">
      <span class="font-mono text-[11px] uppercase tracking-wider text-label self-center mr-1">Tabel:</span>
      <button v-for="o in tableOps" :key="o.l" type="button" @click="o.a" class="px-2 py-1 rounded-md text-xs text-navy hover:bg-ice border border-line">{{ o.l }}</button>
    </div>

    <p v-if="uploading" class="px-4 py-2 text-xs text-label bg-ice">Mengunggah gambar…</p>
    <EditorContent v-show="!sourceMode" :editor="editor" />
    <textarea v-if="sourceMode" v-model="source" @input="emit('update:modelValue', source)" rows="18" class="w-full px-5 py-4 font-mono text-xs text-navy focus:outline-none border-0"></textarea>

    <div class="flex justify-between px-4 py-1.5 border-t border-line text-[11px] font-mono text-muted bg-ice-section">
      <span>{{ editor?.storage.characterCount.words() || 0 }} kata</span>
      <span>{{ editor?.storage.characterCount.characters() || 0 }} karakter</span>
    </div>
  </div>
</template>

<style>
.rich-editor .tiptap p.is-editor-empty:first-child::before { content: attr(data-placeholder); color: #94A3B8; float: left; height: 0; pointer-events: none; }
.rich-editor .tiptap table { border-collapse: collapse; width: 100%; table-layout: fixed; margin: 1rem 0; }
.rich-editor .tiptap td, .rich-editor .tiptap th { border: 1px solid #DCEBFA; padding: 6px 10px; vertical-align: top; position: relative; min-width: 60px; }
.rich-editor .tiptap th { background: #EAF5FF; font-weight: 700; text-align: left; }
.rich-editor .tiptap .selectedCell::after { content: ''; position: absolute; inset: 0; background: rgba(124,200,255,.25); pointer-events: none; }
.rich-editor .tiptap .column-resize-handle { position: absolute; right: -2px; top: 0; bottom: 0; width: 4px; background: #7CC8FF; pointer-events: none; }
.rich-editor .tiptap.resize-cursor { cursor: col-resize; }
.rich-editor .tiptap div[data-youtube-video] { margin: 1rem 0; }
.rich-editor .tiptap div[data-youtube-video] iframe { width: 100%; aspect-ratio: 16/9; height: auto; border-radius: 16px; border: 0; }
.rich-editor .tiptap .ProseMirror-selectednode iframe, .rich-editor .tiptap img.ProseMirror-selectednode { outline: 3px solid #7CC8FF; }
.rich-editor .tiptap img { max-width: 100%; height: auto; }
.rich-editor .tiptap mark { background: #FEF08A; padding: 0 2px; }
</style>

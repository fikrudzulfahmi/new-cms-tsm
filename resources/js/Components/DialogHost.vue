<script setup>
import { ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { dialog, closeDialog } from '@/dialog'

const input = ref(null)
const okBtn = ref(null)
const cancel = () => closeDialog(dialog.type === 'prompt' ? null : false)
const ok = () => closeDialog(dialog.type === 'prompt' ? dialog.value : true)
const onKey = (e) => { if (dialog.open && e.key === 'Escape') cancel() }

watch(() => dialog.open, (v) => { if (v) nextTick(() => (dialog.type === 'prompt' ? input.value : okBtn.value)?.focus()) })
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>

<template>
  <Teleport to="body">
    <transition name="dlg">
      <div v-if="dialog.open" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/50 p-4" @mousedown.self="cancel">
        <div role="dialog" aria-modal="true" class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
          <div class="flex gap-3">
            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full" :class="dialog.danger ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-700'">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="dialog.danger ? 'M12 9v4m0 4h.01M10.3 3.9L2.4 17.5A2 2 0 004.1 20.5h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z' : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'" /></svg>
            </div>
            <div class="min-w-0 flex-1">
              <h3 v-if="dialog.title" class="font-semibold text-slate-900">{{ dialog.title }}</h3>
              <p class="whitespace-pre-line break-words text-sm text-slate-700" :class="dialog.title ? 'mt-1' : 'pt-1.5'">{{ dialog.message }}</p>
              <input v-if="dialog.type === 'prompt'" ref="input" v-model="dialog.value" :placeholder="dialog.placeholder" class="mt-3 w-full rounded border px-3 py-1.5 text-sm" @keydown.enter.prevent="ok" />
            </div>
          </div>
          <div class="mt-5 flex justify-end gap-2">
            <button v-if="dialog.type !== 'alert'" type="button" class="rounded border px-4 py-1.5 text-sm hover:bg-slate-50" @click="cancel">Batal</button>
            <button ref="okBtn" type="button" class="rounded px-4 py-1.5 text-sm font-medium text-white" :class="dialog.danger ? 'bg-red-600 hover:bg-red-700' : 'bg-navy hover:opacity-90'" @click="ok">{{ dialog.okText }}</button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<style>
.dlg-enter-active, .dlg-leave-active { transition: opacity .15s }
.dlg-enter-from, .dlg-leave-to { opacity: 0 }
</style>

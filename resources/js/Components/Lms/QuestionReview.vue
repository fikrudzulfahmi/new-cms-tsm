<script setup>
defineProps({ item: Object, index: Number, showKey: { type: Boolean, default: true } })
const picked = (it, id) => it.answer?.option === id || (it.answer?.options || []).includes(id)
</script>

<template>
  <div class="bg-white rounded-2xl border border-slate-200 p-4 mb-3">
    <div class="flex items-start gap-2">
      <span class="text-xs font-bold text-slate-400 mt-0.5">{{ index + 1 }}</span>
      <p class="flex-1 text-sm whitespace-pre-line">{{ item.body }}</p>
      <span class="text-xs rounded px-1.5 py-0.5" :class="item.is_correct === true ? 'bg-emerald-100 text-emerald-700' : item.is_correct === false ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'">
        {{ item.awarded ?? '?' }} / {{ item.max }}
      </span>
    </div>
    <ul v-if="['pg','benar_salah','pg_kompleks'].includes(item.type)" class="mt-2 space-y-1 text-sm">
      <li v-for="o in item.options" :key="o.id" class="rounded-lg px-2 py-1 border"
          :class="o.is_correct && showKey ? 'border-emerald-300 bg-emerald-50' : picked(item, o.id) ? 'border-red-200 bg-red-50' : 'border-slate-100'">
        <span v-if="picked(item, o.id)" class="font-semibold">&#9679; </span>{{ o.body }}
      </li>
    </ul>
    <div v-else-if="item.type === 'menjodohkan'" class="mt-2 text-sm space-y-1">
      <div v-for="o in item.options" :key="o.id">
        {{ o.body }} &rarr; <b>{{ (item.options.find(x => x.id === item.answer?.pairs?.[o.id]) || {}).match_body || '-' }}</b>
        <span v-if="showKey" class="text-emerald-700"> (kunci: {{ o.match_body }})</span>
      </div>
    </div>
    <div v-else class="mt-2 text-sm">
      <p class="rounded-lg bg-slate-50 p-2 whitespace-pre-line">{{ item.answer?.text || '(kosong)' }}</p>
      <p v-if="item.type === 'isian' && showKey" class="text-emerald-700 mt-1">Kunci: {{ item.options.map(o => o.body).join(' / ') }}</p>
    </div>
    <p v-if="item.feedback" class="mt-2 text-xs text-slate-600">Catatan guru: {{ item.feedback }}</p>
    <p v-if="item.explanation" class="mt-2 text-xs text-slate-500 whitespace-pre-line">Pembahasan: {{ item.explanation }}</p>
    <slot />
  </div>
</template>

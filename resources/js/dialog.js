import { reactive } from 'vue'

export const dialog = reactive({ open: false, type: 'confirm', title: '', message: '', value: '', placeholder: '', okText: 'Ya', danger: false, resolve: null })

function show(opts) {
  return new Promise((resolve) => {
    if (dialog.resolve) dialog.resolve(dialog.type === 'prompt' ? null : false)
    Object.assign(dialog, { title: '', value: '', placeholder: '', okText: 'Ya', danger: false }, opts, { open: true, resolve })
  })
}

const isDanger = (m) => /^(hapus|nonaktifkan|reset)/i.test(m)

export const confirmDialog = (message, opts = {}) =>
  show({ type: 'confirm', message, danger: isDanger(message), okText: isDanger(message) ? (/^hapus/i.test(message) ? 'Hapus' : 'Ya, lanjutkan') : 'Ya, lanjutkan', ...opts })

export const alertDialog = (message, opts = {}) => show({ type: 'alert', message, okText: 'Mengerti', ...opts })

export const promptDialog = (message, opts = {}) => show({ type: 'prompt', message, okText: 'Simpan', ...opts })

export function closeDialog(result) {
  const r = dialog.resolve
  dialog.open = false
  dialog.resolve = null
  r && r(result)
}

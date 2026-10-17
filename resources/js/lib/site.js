// Helper bersama halaman publik.
export const img = (p) => (p ? (/^https?:\/\//.test(p) ? p : `/storage/${p}`) : null)
export const hideImg = (e) => { e.target.style.visibility = 'hidden' }
export const initials = (name = '') =>
  String(name || '').replace(/,.*$/, '').split(/\s+/).filter((w) => w && !/\.$/.test(w)).slice(0, 2).map((w) => w[0]).join('').toUpperCase()

const iconPaths = {
  'wrench-screwdriver': 'M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z',
  bolt: 'M13 2L4 14h7l-1 8 9-12h-7z',
  'cpu-chip': 'M9 9h6v6H9zM4 4h16v16H4zM9 2v2M15 2v2M9 20v2M15 20v2M2 9h2M2 15h2M20 9h2M20 15h2',
  'shield-check': 'M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6zM8.5 12l2.5 2.5 4.5-5',
  'building-storefront': 'M3 9l1.5-5h15L21 9M3 9v11h18V9M3 9h18M9 20v-6h6v6',
  briefcase: 'M3 7h18v13H3zM8 7V4h8v3M3 13h18',
  'document-check': 'M6 2h9l5 5v15H6zM14 2v6h6M9 15l2 2 4-4',
  users: 'M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 20v-2a4 4 0 0 0-3-3.9M16 2.1a4 4 0 0 1 0 7.8',
}
export const iconFor = (name) => iconPaths[name] || 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM12 9.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z'
// Teks biasa -> paragraf (dirender sebagai teks, bukan v-html, jadi aman dari XSS)
export const paragraphs = (t = '') => String(t || '').split(/\n\s*\n/).map((p) => p.trim()).filter(Boolean)

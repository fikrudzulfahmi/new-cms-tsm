// Analisis SEO & keterbacaan ala Yoast (disesuaikan untuk Bahasa Indonesia)
const strip = (html) => (html || '').replace(/<(script|style)[\s\S]*?<\/\1>/gi, ' ').replace(/<[^>]+>/g, ' ').replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').replace(/\s+/g, ' ').trim()
const words = (t) => (t.toLowerCase().match(/[\p{L}\p{N}]+(?:[-'][\p{L}\p{N}]+)*/gu) || [])
const norm = (t) => words(t).join(' ')
const slugify = (t) => (t || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
const countPhrase = (text, kw) => {
  if (!kw) return 0
  const t = ` ${norm(text)} `, k = ` ${norm(kw)} `
  let n = 0, i = 0
  while ((i = t.indexOf(k, i)) !== -1) { n++; i += k.length - 1 }
  return n
}
const has = (text, kw) => countPhrase(text, kw) > 0
const sentences = (t) => (t.match(/[^.!?…]+[.!?…]+|[^.!?…]+$/g) || []).map(s => s.trim()).filter(s => words(s).length)

const TRANSITIONS = ['selain itu', 'oleh karena itu', 'oleh sebab itu', 'karena itu', 'dengan demikian', 'namun', 'akan tetapi', 'tetapi', 'sedangkan', 'meskipun', 'walaupun', 'misalnya', 'contohnya', 'sebagai contoh', 'seperti', 'pertama', 'kedua', 'ketiga', 'selanjutnya', 'kemudian', 'lalu', 'akhirnya', 'kesimpulannya', 'singkatnya', 'pada akhirnya', 'sementara itu', 'di samping itu', 'bahkan', 'justru', 'sebaliknya', 'akibatnya', 'sehingga', 'maka', 'jadi', 'terlebih', 'apalagi', 'begitu pula', 'demikian pula', 'dengan kata lain', 'yaitu', 'yakni', 'adapun', 'sebelumnya', 'setelah itu', 'pada dasarnya', 'oleh karenanya', 'di sisi lain', 'terakhir', 'intinya']
// pasif Bahasa Indonesia: kata ber-prefiks di- / ter- (heuristik, abaikan kata umum)
const NOT_PASSIVE = new Set(['dia', 'diri', 'dini', 'dinas', 'didik', 'dinding', 'diskusi', 'digital', 'dimensi', 'diploma', 'direktur', 'direksi', 'distrik', 'disiplin', 'dialog', 'diesel', 'diet', 'dirinya', 'terus', 'termasuk', 'terbaik', 'terbesar', 'terbaru', 'tertinggi', 'terakhir', 'terhadap', 'tertentu', 'ternyata', 'terutama', 'terkait', 'teras', 'terang', 'terampil', 'terpadu', 'tersebut'])
const isPassive = (s) => words(s).some(w => w.length > 4 && /^(di|ter)[a-z]{3,}/.test(w) && !NOT_PASSIVE.has(w))

export function analyzeSeo(f) {
  const html = f.body || ''
  const text = strip(html)
  const wl = words(text)
  const wc = wl.length
  const kw = (f.focus_keyword || '').trim()
  const kwWords = words(kw)
  const seoTitle = (f.meta_title || f.title || '').trim()
  const desc = (f.meta_description || f.excerpt || '').trim()
  const slug = f.slug || slugify(f.title)
  const doc = typeof DOMParser !== 'undefined' ? new DOMParser().parseFromString(`<div>${html}</div>`, 'text/html') : null
  const q = (s) => doc ? [...doc.querySelectorAll(s)] : []
  const headings = q('h2,h3,h4')
  const imgs = q('img')
  const links = q('a[href]').map(a => a.getAttribute('href'))
  const host = typeof window !== 'undefined' ? window.location.host : ''
  const internal = links.filter(h => h.startsWith('/') || h.startsWith('#') || (host && h.includes(host)))
  const outbound = links.filter(h => /^https?:\/\//.test(h) && !(host && h.includes(host)))
  const paras = q('p').map(p => p.textContent.trim()).filter(Boolean)
  const firstPara = paras[0] || text.split(/\s+/).slice(0, 60).join(' ')
  const videos = q('[data-youtube-video], iframe').length

  const seo = []
  const add = (list, status, label, text) => list.push({ status, label, text })

  // ---------- SEO ----------
  if (!kw) {
    add(seo, 'bad', 'Kata kunci fokus', 'Belum diisi. Isi kata kunci fokus agar analisis berjalan penuh.')
  } else {
    add(seo, kwWords.length <= 4 ? 'good' : 'ok', 'Panjang kata kunci', kwWords.length <= 4 ? `Bagus (${kwWords.length} kata).` : `${kwWords.length} kata — terlalu panjang, idealnya ≤ 4 kata.`)
    const tPos = norm(seoTitle).indexOf(norm(kw))
    add(seo, tPos === 0 ? 'good' : tPos > 0 ? 'ok' : 'bad', 'Kata kunci di judul SEO', tPos === 0 ? 'Muncul di awal judul. Sempurna!' : tPos > 0 ? 'Ada di judul, lebih baik letakkan di awal.' : 'Tidak ada di judul SEO.')
    add(seo, has(desc, kw) ? 'good' : 'bad', 'Kata kunci di meta deskripsi', has(desc, kw) ? 'Ada di meta deskripsi.' : 'Tidak ada di meta deskripsi.')
    const slugHas = kwWords.every(w => slug.includes(slugify(w)))
    add(seo, slugHas ? 'good' : 'ok', 'Kata kunci di slug (URL)', slugHas ? 'Ada di slug.' : 'Slug belum memuat kata kunci.')
    add(seo, has(firstPara, kw) ? 'good' : 'bad', 'Kata kunci di pendahuluan', has(firstPara, kw) ? 'Muncul di paragraf pertama.' : 'Tidak muncul di paragraf pertama.')
    const n = countPhrase(text, kw)
    const dens = wc ? (n * kwWords.length / wc) * 100 : 0
    add(seo, dens >= 0.5 && dens <= 3 ? 'good' : dens > 3 ? 'bad' : n ? 'ok' : 'bad', 'Kepadatan kata kunci',
      `Ditemukan ${n}× (${dens.toFixed(1)}%). ` + (dens > 3 ? 'Terlalu sering (keyword stuffing).' : dens < 0.5 ? 'Terlalu jarang, ideal 0,5–3%.' : 'Pas.'))
    if (headings.length) {
      const hk = headings.filter(h => has(h.textContent, kw) || kwWords.filter(w => words(h.textContent).includes(w)).length >= Math.ceil(kwWords.length / 2)).length
      const ratio = hk / headings.length
      add(seo, ratio >= 0.3 && ratio <= 0.75 ? 'good' : hk ? 'ok' : 'bad', 'Kata kunci di subjudul', `${hk} dari ${headings.length} subjudul memuat kata kunci` + (hk ? '.' : '. Tambahkan ke beberapa subjudul (H2/H3).'))
    }
    if (imgs.length) {
      const ak = imgs.filter(i => has(i.getAttribute('alt') || '', kw)).length
      add(seo, ak ? 'good' : 'ok', 'Kata kunci di alt gambar', ak ? `${ak} gambar memakai kata kunci di alt.` : 'Belum ada alt gambar yang memuat kata kunci.')
    }
  }
  add(seo, wc >= 300 ? 'good' : wc >= 200 ? 'ok' : 'bad', 'Panjang teks', `${wc} kata. ` + (wc >= 300 ? 'Bagus.' : 'Minimal 300 kata disarankan.'))
  const tl = seoTitle.length
  add(seo, tl >= 40 && tl <= 60 ? 'good' : tl > 0 && tl <= 70 ? 'ok' : 'bad', 'Panjang judul SEO', `${tl} karakter. ` + (tl > 60 ? 'Bisa terpotong di Google (ideal 40–60).' : tl < 40 ? 'Terlalu pendek (ideal 40–60).' : 'Pas.'))
  const dl = desc.length
  add(seo, dl >= 120 && dl <= 156 ? 'good' : dl > 0 ? 'ok' : 'bad', 'Panjang meta deskripsi', dl ? `${dl} karakter. ` + (dl > 156 ? 'Terlalu panjang (ideal 120–156).' : dl < 120 ? 'Terlalu pendek (ideal 120–156).' : 'Pas.') : 'Belum diisi.')
  add(seo, internal.length ? 'good' : 'ok', 'Link internal', internal.length ? `${internal.length} link ke halaman situs ini.` : 'Belum ada link ke halaman lain di situs ini.')
  add(seo, outbound.length ? 'good' : 'ok', 'Link keluar', outbound.length ? `${outbound.length} link ke situs lain.` : 'Belum ada link ke sumber luar.')
  add(seo, imgs.length || videos || f.has_cover ? 'good' : 'ok', 'Gambar & video', imgs.length || videos || f.has_cover ? `${imgs.length} gambar, ${videos} video${f.has_cover ? ', + cover' : ''}.` : 'Tambahkan gambar atau video.')
  const noAlt = imgs.filter(i => !(i.getAttribute('alt') || '').trim()).length
  if (imgs.length) add(seo, noAlt ? 'ok' : 'good', 'Atribut alt gambar', noAlt ? `${noAlt} gambar belum punya alt.` : 'Semua gambar punya alt.')
  if (f.has_cover) add(seo, (f.cover_alt || '').trim() ? 'good' : 'ok', 'Alt gambar cover', (f.cover_alt || '').trim() ? 'Terisi.' : 'Isi teks alt untuk gambar cover.')

  // ---------- Keterbacaan ----------
  const read = []
  const sents = sentences(text)
  if (wc) {
    const long = sents.filter(s => words(s).length > 20).length
    const pl = sents.length ? long / sents.length * 100 : 0
    add(read, pl <= 25 ? 'good' : pl <= 35 ? 'ok' : 'bad', 'Panjang kalimat', `${pl.toFixed(0)}% kalimat lebih dari 20 kata` + (pl <= 25 ? '. Bagus.' : ' (maks. 25%). Pecah kalimat panjang.'))
    const longP = paras.filter(p => words(p).length > 150).length
    add(read, longP ? 'bad' : 'good', 'Panjang paragraf', longP ? `${longP} paragraf lebih dari 150 kata.` : 'Tidak ada paragraf terlalu panjang.')
    // distribusi subjudul
    if (wc > 300) {
      const segs = html.split(/<h[2-4][^>]*>/i).map(s => words(strip(s)).length)
      const over = segs.filter(n => n > 300).length
      add(read, !headings.length ? 'bad' : over ? 'ok' : 'good', 'Distribusi subjudul', !headings.length ? 'Belum ada subjudul. Tambahkan H2/H3.' : over ? `${over} bagian lebih dari 300 kata tanpa subjudul.` : 'Subjudul tersebar baik.')
    }
    const pas = sents.filter(isPassive).length
    const pp = sents.length ? pas / sents.length * 100 : 0
    add(read, pp <= 40 ? 'good' : pp <= 55 ? 'ok' : 'bad', 'Kalimat pasif', `±${pp.toFixed(0)}% kalimat pasif (di-/ter-)` + (pp <= 40 ? '. Wajar untuk Bahasa Indonesia.' : '. Gunakan lebih banyak kalimat aktif.'))
    const tr = sents.filter(s => { const l = ` ${norm(s)} `; return TRANSITIONS.some(t => l.includes(` ${t} `)) }).length
    const tp = sents.length ? tr / sents.length * 100 : 0
    add(read, tp >= 30 ? 'good' : tp >= 20 ? 'ok' : 'bad', 'Kata transisi', `${tp.toFixed(0)}% kalimat memakai kata transisi (selain itu, namun, oleh karena itu…)` + (tp >= 30 ? '. Bagus.' : ', minimal 30%.'))
    let run = 1, worst = 1
    for (let i = 1; i < sents.length; i++) {
      run = words(sents[i])[0] === words(sents[i - 1])[0] ? run + 1 : 1
      worst = Math.max(worst, run)
    }
    add(read, worst < 3 ? 'good' : 'bad', 'Variasi awal kalimat', worst < 3 ? 'Awal kalimat bervariasi.' : `${worst} kalimat berurutan diawali kata yang sama.`)
  } else {
    add(read, 'bad', 'Konten', 'Belum ada isi untuk dianalisis.')
  }

  const score = (list) => list.length ? Math.round(list.reduce((s, c) => s + (c.status === 'good' ? 1 : c.status === 'ok' ? 0.5 : 0), 0) / list.length * 100) : 0
  return { seo, read, seoScore: score(seo), readScore: score(read), wordCount: wc, slug, seoTitle, desc }
}

export { slugify }

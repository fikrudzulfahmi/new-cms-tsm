<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps(['settings'])

const imgUrl = (p) => (p ? (/^https?:\/\//.test(p) ? p : `/storage/${p}`) : null)
const current = (k) => props.settings?.[k] || ''

// Setiap bagian pengaturan menjadi satu tab, bukan satu halaman panjang.
const tabs = [
  { key: 'identitas', label: 'Identitas & Hero' },
  { key: 'profil', label: 'Tentang & Profil' },
  { key: 'kontak', label: 'Kontak & Statistik' },
  { key: 'sosial', label: 'Media Sosial' },
  { key: 'spmb', label: 'SPMB' },
]

const activeTab = ref('identitas')

// Kunci error dari server dipetakan ke tab asalnya, agar pengguna langsung
// dibawa ke isian yang bermasalah.
const SOSIAL_KEYS = ['instagram_url', 'facebook_url', 'youtube_url', 'tiktok_url']
const IDENTITAS_KEYS = ['site_name', 'department_name', 'site_tagline', 'hero_title', 'hero_subtitle', 'site_logo', 'site_favicon', 'hero_bg']

const tabOf = (key) => {
  if (key.startsWith('profile_') || key === 'about_text' || key === 'about_image') return 'profil'
  if (key.startsWith('spmb_')) return 'spmb'
  if (SOSIAL_KEYS.includes(key)) return 'sosial'
  if (IDENTITAS_KEYS.includes(key)) return 'identitas'
  return 'kontak'
}

const form = useForm({
  site_name: props.settings?.site_name || '',
  site_tagline: props.settings?.site_tagline || '',
  hero_title: props.settings?.hero_title || '',
  hero_subtitle: props.settings?.hero_subtitle || '',
  site_logo: null,
  site_favicon: null,
  hero_bg: null,
  school_name: props.settings?.school_name || '',
  address: props.settings?.address || '',
  phone: props.settings?.phone || '',
  email: props.settings?.email || '',
  about_text: props.settings?.about_text || '',
  spmb_open: props.settings?.spmb_open === '1',
  spmb_info: props.settings?.spmb_info || '',
  spmb_url: props.settings?.spmb_url || '',
  footer_text: props.settings?.footer_text || '',
  department_name: props.settings?.department_name || '',
  instagram_url: props.settings?.instagram_url || '',
  tiktok_url: props.settings?.tiktok_url || '',
  facebook_url: props.settings?.facebook_url || '',
  youtube_url: props.settings?.youtube_url || '',
  maps_embed: props.settings?.maps_embed || '',
  whatsapp: props.settings?.whatsapp || '',
  stat_students: props.settings?.stat_students || '',
  stat_graduates: props.settings?.stat_graduates || '',
  stat_absorption: props.settings?.stat_absorption || '',
  about_image: null,
  profile_head_name: props.settings?.profile_head_name || '',
  profile_head_position: props.settings?.profile_head_position || '',
  profile_head_photo: null,
  profile_speech: props.settings?.profile_speech || '',
  profile_history: props.settings?.profile_history || '',
  profile_vision: props.settings?.profile_vision || '',
  profile_mission: props.settings?.profile_mission || '',
  profile_structure_image: null,
  remove_images: [],
})

const errorCounts = computed(() => {
  const counts = {}
  for (const key of Object.keys(form.errors || {})) {
    const tab = tabOf(key)
    counts[tab] = (counts[tab] || 0) + 1
  }
  return counts
})

const submit = () =>
  form.transform((d) => ({ ...d, spmb_open: d.spmb_open ? '1' : '0' })).post('/admin/pengaturan', {
    preserveScroll: true,
    onError: (errors) => {
      const first = Object.keys(errors || {})[0]
      if (first) activeTab.value = tabOf(first)
    },
    onSuccess: () => {
      form.remove_images = []
      form.site_logo = null
      form.site_favicon = null
      form.hero_bg = null
      form.about_image = null
      form.profile_head_photo = null
      form.profile_structure_image = null
    },
  })

onMounted(() => {
  // Dukungan tautan lama seperti /admin/pengaturan#profil
  const hash = (window.location.hash || '').replace('#', '')
  if (tabs.some((t) => t.key === hash)) activeTab.value = hash
})
</script>

<template>
  <AdminLayout>
    <title>Pengaturan Website - Admin TSM</title>
    <div class="max-w-4xl mx-auto space-y-6">
      <div>
        <h1 class="font-display text-[28px] font-bold text-navy tracking-[-0.5px]"><span class="block font-mono text-[11px] font-normal tracking-[2px] uppercase text-label mb-1">Panel Kendali · TSM</span>Pengaturan Website</h1>
        <p class="text-slate-500 text-sm">Ubah seluruh tulisan, profil, alamat, dan media sosial website</p>
      </div>

      <form @submit.prevent="submit" class="card p-0 overflow-hidden">
        <!-- Tab bagian -->
        <div class="flex flex-wrap gap-1.5 p-3 border-b border-line bg-slate-50/80" role="tablist" aria-label="Bagian pengaturan">
          <button
            v-for="t in tabs"
            :key="t.key"
            type="button"
            role="tab"
            :aria-selected="activeTab === t.key"
            :class="[
              'inline-flex items-center gap-2 px-3.5 py-2 rounded-full text-sm font-semibold transition-colors',
              activeTab === t.key ? 'bg-navy text-white' : 'text-slate-600 hover:bg-white hover:text-navy',
            ]"
            @click="activeTab = t.key"
          >
            {{ t.label }}
            <span v-if="errorCounts[t.key]" class="min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[11px] font-bold flex items-center justify-center" :aria-label="`${errorCounts[t.key]} isian bermasalah`">{{ errorCounts[t.key] }}</span>
          </button>
        </div>

        <div class="p-8 space-y-6">
          <div v-if="Object.keys(form.errors).length" class="rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="text-sm font-bold text-red-700">Ada isian yang belum benar ({{ Object.keys(form.errors).length }}):</p>
            <ul class="mt-2 list-disc pl-5 text-xs text-red-700 space-y-1">
              <li v-for="(msg, key) in form.errors" :key="key">
                <button type="button" class="text-left hover:underline" @click="activeTab = tabOf(key)">
                  <span class="font-mono">{{ key }}</span> — {{ msg }}
                </button>
              </li>
            </ul>
          </div>

          <!-- IDENTITAS & HERO -->
          <section v-show="activeTab === 'identitas'" id="identitas" role="tabpanel" aria-label="Identitas & Hero" class="space-y-6">
            <div>
              <h2 class="font-bold text-slate-800">Identitas Situs & Hero</h2>
              <p class="text-xs text-slate-500">Nama yang tampil di navbar, judul besar halaman depan, logo, dan favicon.</p>
            </div>
            <div class="grid sm:grid-cols-2 gap-6">
              <div>
                <label class="form-label">Nama Jurusan (navbar)</label>
                <input v-model="form.site_name" type="text" class="form-input" placeholder="Teknik Sepeda Motor" />
                <p class="text-xs text-slate-500 mt-1">Kosongkan untuk memakai "Teknik Sepeda Motor".</p>
              </div>
              <div>
                <label class="form-label">Nama Jurusan (footer, hero & meta)</label>
                <input v-model="form.department_name" type="text" maxlength="200" class="form-input" placeholder="Teknik Sepeda Motor" />
                <p class="text-xs text-slate-500 mt-1">Teks cadangan bila "Nama Jurusan (navbar)" dikosongkan; dipakai juga di footer dan judul halaman.</p>
              </div>
              <div>
                <label class="form-label">Tagline (bawah judul hero)</label>
                <input v-model="form.site_tagline" type="text" class="form-input" />
              </div>
              <div>
                <label class="form-label">Judul Hero</label>
                <input v-model="form.hero_title" type="text" class="form-input" placeholder="Teknik Sepeda Motor" />
                <p class="text-xs text-slate-500 mt-1">Dipakai bila belum ada slider hero aktif.</p>
              </div>
              <div>
                <label class="form-label">Subjudul Hero (kata berwarna)</label>
                <input v-model="form.hero_subtitle" type="text" class="form-input" />
              </div>
            </div>
            <div class="grid sm:grid-cols-3 gap-6">
              <div>
                <label class="form-label">Logo Sekolah</label>
                <img v-if="current('site_logo') && !form.remove_images.includes('site_logo')" :src="imgUrl(current('site_logo'))" alt="Logo saat ini" class="h-14 w-auto mb-2 rounded-lg border border-slate-200 p-1 bg-white" />
                <label v-if="current('site_logo')" class="flex items-center gap-2 text-xs text-slate-500 mt-2"><input v-model="form.remove_images" type="checkbox" value="site_logo" /> Hapus logo</label>
                <input type="file" accept="image/*" @input="form.site_logo = $event.target.files[0]" class="form-input text-sm file:mr-4 file:py-1 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-900" />
                <p class="text-xs text-slate-500 mt-1">Tampil di navbar publik. PNG transparan, maks 5 MB.</p>
                <p v-if="form.errors.site_logo" class="text-xs text-red-600 mt-1">{{ form.errors.site_logo }}</p>
              </div>
              <div>
                <label class="form-label">Favicon</label>
                <img v-if="current('site_favicon') && !form.remove_images.includes('site_favicon')" :src="imgUrl(current('site_favicon'))" alt="Favicon saat ini" class="h-10 w-10 mb-2 rounded border border-slate-200 p-1 bg-white" />
                <label v-if="current('site_favicon')" class="flex items-center gap-2 text-xs text-slate-500 mt-2"><input v-model="form.remove_images" type="checkbox" value="site_favicon" /> Hapus favicon</label>
                <input type="file" accept="image/*" @input="form.site_favicon = $event.target.files[0]" class="form-input text-sm file:mr-4 file:py-1 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-900" />
                <p class="text-xs text-slate-500 mt-1">Ikon tab browser, maks 1 MB.</p>
                <p v-if="form.errors.site_favicon" class="text-xs text-red-600 mt-1">{{ form.errors.site_favicon }}</p>
              </div>
              <div>
                <label class="form-label">Gambar Latar Hero</label>
                <img v-if="current('hero_bg') && !form.remove_images.includes('hero_bg')" :src="imgUrl(current('hero_bg'))" alt="Hero saat ini" class="h-14 w-full object-cover mb-2 rounded-lg border border-slate-200" />
                <label v-if="current('hero_bg')" class="flex items-center gap-2 text-xs text-slate-500 mt-2"><input v-model="form.remove_images" type="checkbox" value="hero_bg" /> Hapus gambar hero</label>
                <input type="file" accept="image/*" @input="form.hero_bg = $event.target.files[0]" class="form-input text-sm file:mr-4 file:py-1 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-900" />
                <p class="text-xs text-slate-500 mt-1">Dipakai bila slider hero kosong. Maks 5 MB.</p>
                <p v-if="form.errors.hero_bg" class="text-xs text-red-600 mt-1">{{ form.errors.hero_bg }}</p>
              </div>
            </div>
          </section>

          <!-- TENTANG & PROFIL -->
          <section v-show="activeTab === 'profil'" id="profil" role="tabpanel" aria-label="Tentang & Profil" class="space-y-6">
            <div>
              <h2 class="font-bold text-slate-800">Tentang Kami & Halaman Profil Jurusan <a href="/profil" target="_blank" rel="noopener" class="ml-2 text-xs font-semibold text-label hover:underline">lihat ↗</a></h2>
              <p class="text-xs text-slate-500">Bagian yang dikosongkan tidak ditampilkan. Pisahkan paragraf dengan satu baris kosong. Data guru diatur di menu <b>Konten Halaman → Guru</b>.</p>
            </div>
            <div>
              <label class="form-label">Teks Tentang Kami (Tentang Jurusan)</label>
              <textarea v-model="form.about_text" rows="4" class="form-input resize-none"></textarea>
            </div>
            <div class="grid sm:grid-cols-2 gap-6">
              <div>
                <label class="form-label">Foto Tentang Kami</label>
                <input type="file" @input="form.about_image = $event.target.files[0]" class="form-input text-sm file:mr-4 file:py-1 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-900" />
                <img v-if="current('about_image') && !form.remove_images.includes('about_image')" :src="imgUrl(current('about_image'))" alt="Foto saat ini" class="mt-2 h-24 rounded-xl object-cover" />
                <label v-if="current('about_image')" class="flex items-center gap-2 text-xs text-slate-500 mt-2"><input v-model="form.remove_images" type="checkbox" value="about_image" /> Hapus foto</label>
                <p v-if="form.errors.about_image" class="text-xs text-red-600 mt-1">{{ form.errors.about_image }}</p>
              </div>
              <div>
                <label class="form-label">Bagan Struktur Organisasi</label>
                <input type="file" accept="image/*" @input="form.profile_structure_image = $event.target.files[0]" class="form-input text-sm file:mr-4 file:py-1 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-900" />
                <img v-if="current('profile_structure_image') && !form.remove_images.includes('profile_structure_image')" :src="imgUrl(current('profile_structure_image'))" alt="" class="mt-2 h-24 rounded-xl object-contain bg-slate-50" />
                <label v-if="current('profile_structure_image')" class="flex items-center gap-2 text-xs text-slate-500 mt-2"><input v-model="form.remove_images" type="checkbox" value="profile_structure_image" /> Hapus gambar</label>
                <p v-if="form.errors.profile_structure_image" class="text-xs text-red-600 mt-1">{{ form.errors.profile_structure_image }}</p>
              </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-6">
              <div>
                <label class="form-label">Nama Kepala Program / Kajur</label>
                <input v-model="form.profile_head_name" type="text" maxlength="150" class="form-input" placeholder="Kosong = guru urutan pertama" />
              </div>
              <div>
                <label class="form-label">Jabatan</label>
                <input v-model="form.profile_head_position" type="text" maxlength="150" class="form-input" />
              </div>
              <div>
                <label class="form-label">Foto Kepala Program</label>
                <img v-if="current('profile_head_photo') && !form.remove_images.includes('profile_head_photo')" :src="imgUrl(current('profile_head_photo'))" alt="" class="h-24 rounded-xl mb-2 object-cover" />
                <input type="file" accept="image/*" @input="form.profile_head_photo = $event.target.files[0]" class="form-input text-sm file:mr-4 file:py-1 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-900" />
                <label v-if="current('profile_head_photo')" class="flex items-center gap-2 text-xs text-slate-500 mt-2"><input v-model="form.remove_images" type="checkbox" value="profile_head_photo" /> Hapus foto</label>
                <p v-if="form.errors.profile_head_photo" class="text-xs text-red-600 mt-1">{{ form.errors.profile_head_photo }}</p>
              </div>
            </div>
            <div>
              <label class="form-label">Sambutan</label>
              <textarea v-model="form.profile_speech" rows="6" maxlength="5000" class="form-input"></textarea>
              <p class="text-xs text-slate-400 mt-1">Paragraf pertama setelah salam juga dikutip di Beranda.</p>
            </div>
            <div>
              <label class="form-label">Sejarah Jurusan</label>
              <textarea v-model="form.profile_history" rows="6" maxlength="10000" class="form-input" placeholder="Kosong = memakai teks Tentang Kami"></textarea>
            </div>
            <div>
              <label class="form-label">Visi</label>
              <textarea v-model="form.profile_vision" rows="2" maxlength="1000" class="form-input"></textarea>
            </div>
            <div>
              <label class="form-label">Misi (satu poin per baris)</label>
              <textarea v-model="form.profile_mission" rows="5" maxlength="3000" class="form-input"></textarea>
            </div>
          </section>

          <!-- KONTAK & STATISTIK -->
          <section v-show="activeTab === 'kontak'" id="kontak" role="tabpanel" aria-label="Kontak & Statistik" class="space-y-6">
            <div>
              <h2 class="font-bold text-slate-800">Kontak, Statistik Halaman Depan & Footer</h2>
              <p class="text-xs text-slate-500">Nomor WhatsApp dipakai tombol "Tanya via WhatsApp"; angka statistik tampil di bawah hero.</p>
            </div>
            <div class="grid sm:grid-cols-2 gap-6">
              <div>
                <label class="form-label">Nama Sekolah / Jurusan</label>
                <input v-model="form.school_name" type="text" class="form-input" />
              </div>
              <div>
                <label class="form-label">Email Kontak</label>
                <input v-model="form.email" type="email" class="form-input" />
              </div>
              <div>
                <label class="form-label">No. Telepon / WhatsApp</label>
                <input v-model="form.phone" type="text" class="form-input" />
              </div>
              <div>
                <label class="form-label">Alamat Lengkap</label>
                <input v-model="form.address" type="text" class="form-input" />
              </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-6">
              <div>
                <label class="form-label">Nomor WhatsApp</label>
                <input v-model="form.whatsapp" type="text" placeholder="081234567890" class="form-input" />
                <p class="text-xs text-slate-500 mt-1">Boleh 08… atau 62…; otomatis dirapikan. Kosong = pakai No. Telepon.</p>
              </div>
              <div>
                <label class="form-label">Jumlah Siswa Aktif</label>
                <input v-model="form.stat_students" type="text" placeholder="150+" class="form-input" />
              </div>
              <div>
                <label class="form-label">Jumlah Alumni</label>
                <input v-model="form.stat_graduates" type="text" placeholder="500+" class="form-input" />
              </div>
              <div>
                <label class="form-label">Lulusan Terserap Kerja</label>
                <input v-model="form.stat_absorption" type="text" placeholder="85%" class="form-input" />
              </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-6">
              <div>
                <label class="form-label">Embed Google Maps (HTML)</label>
                <textarea v-model="form.maps_embed" rows="3" class="form-input text-xs font-mono resize-none"></textarea>
              </div>
              <div>
                <label class="form-label">Teks Footer Copyright</label>
                <input v-model="form.footer_text" type="text" class="form-input" />
              </div>
            </div>
          </section>

          <!-- MEDIA SOSIAL -->
          <section v-show="activeTab === 'sosial'" id="sosial" role="tabpanel" aria-label="Media Sosial" class="space-y-6">
            <div>
              <h2 class="font-bold text-slate-800">Media Sosial</h2>
              <p class="text-xs text-slate-500">Tautan ini tampil sebagai ikon di footer. Kosongkan yang belum dipakai.</p>
            </div>
            <div class="grid sm:grid-cols-2 gap-6">
              <div>
                <label class="form-label">URL Instagram</label>
                <input v-model="form.instagram_url" type="text" class="form-input" />
              </div>
              <div>
                <label class="form-label">URL Facebook</label>
                <input v-model="form.facebook_url" type="text" class="form-input" />
              </div>
              <div>
                <label class="form-label">URL YouTube</label>
                <input v-model="form.youtube_url" type="text" class="form-input" />
              </div>
              <div>
                <label class="form-label">URL TikTok</label>
                <input v-model="form.tiktok_url" type="text" class="form-input" placeholder="https://tiktok.com/@..." />
                <p v-if="form.errors.tiktok_url" class="text-xs text-red-600 mt-1">{{ form.errors.tiktok_url }}</p>
              </div>
            </div>
          </section>

          <!-- SPMB -->
          <section v-show="activeTab === 'spmb'" id="spmb" role="tabpanel" aria-label="SPMB" class="space-y-6">
            <div>
              <h2 class="font-bold text-slate-800">SPMB (Sistem Penerimaan Murid Baru)</h2>
              <p class="text-xs text-slate-500">Pendaftaran dilakukan di aplikasi SPMB sekolah. Semua tombol "Daftar SPMB" di website diarahkan ke link di bawah (dibuka di tab baru).</p>
            </div>
            <div>
              <label class="form-label">Link Aplikasi SPMB Sekolah</label>
              <input v-model="form.spmb_url" type="url" placeholder="https://spmb.sekolah.sch.id" class="form-input" />
              <p v-if="form.errors.spmb_url" class="text-xs text-red-600 mt-1">{{ form.errors.spmb_url }}</p>
              <p v-else-if="!form.spmb_url" class="text-xs text-amber-600 mt-1">Belum diisi: tombol "Daftar SPMB" hanya mengarah ke section SPMB di halaman depan.</p>
              <a v-else :href="form.spmb_url" target="_blank" rel="noopener" class="text-xs text-blue-700 hover:underline mt-1 inline-block">Uji buka link →</a>
            </div>
            <div class="grid sm:grid-cols-3 gap-6 items-start">
              <label class="flex items-center gap-3 text-sm font-semibold text-slate-700 cursor-pointer sm:pt-7">
                <input v-model="form.spmb_open" type="checkbox" class="w-5 h-5 rounded accent-blue-900" />
                Pendaftaran sedang dibuka
              </label>
              <div class="sm:col-span-2">
                <label class="form-label">Info / Pengumuman SPMB</label>
                <input v-model="form.spmb_info" type="text" placeholder="Pendaftaran SPMB Tahun Pelajaran 2026/2027 sudah dibuka!" class="form-input" />
                <p class="text-xs text-slate-500 mt-1">Ditampilkan di section SPMB halaman depan saat pendaftaran dibuka.</p>
              </div>
            </div>
          </section>
        </div>

        <!-- Simpan -->
        <div class="sticky bottom-0 z-10 px-8 py-4 border-t border-line bg-white/95 backdrop-blur flex flex-wrap items-center justify-between gap-3">
          <p class="text-xs">
            <span v-if="form.isDirty" class="font-semibold text-amber-600">● Ada perubahan yang belum disimpan</span>
            <span v-else class="text-slate-500">Semua perubahan tersimpan.</span>
          </p>
          <div class="flex items-center gap-3">
            <button v-if="form.isDirty" type="button" @click="form.reset()" class="text-xs font-semibold text-slate-500 hover:text-navy">Batalkan perubahan</button>
            <button type="submit" :disabled="form.processing" class="btn-primary px-8 py-3">
              {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

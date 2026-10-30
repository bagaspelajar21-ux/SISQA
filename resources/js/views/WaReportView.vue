<template>
  <div class="space-y-6">
    <!-- Modern Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-950 via-green-900 to-teal-950 p-6 text-white shadow-lg border border-emerald-800/80">
      <OfficeSkylineBg />
      <div class="absolute -right-12 -top-12 w-56 h-56 rounded-full bg-green-400/20 blur-2xl pointer-events-none"></div>
      <div class="absolute right-36 -bottom-16 w-44 h-44 rounded-full bg-teal-400/15 blur-2xl pointer-events-none"></div>

      <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-green-500/20 border border-green-400/30 text-green-300 text-[11px] font-extrabold uppercase tracking-wider mb-2">
            <span>💬 Daily QA WhatsApp Report Generator</span>
          </div>
          <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">
            Report WA — QA Daily Testing &amp; Progress Report
          </h2>
          <p class="text-xs text-emerald-100/90 mt-1 max-w-2xl">
            Susun laporan harian QA berformat standar WhatsApp secara otomatis dari database atau sesuaikan langsung per modul, temuan bug, dan hasil retest.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
          <router-link
            to="/reports"
            class="px-3.5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-xs font-bold text-white transition-all flex items-center space-x-1.5"
          >
            <span>📑 Ke Menu QA Report (Excel/PDF)</span>
          </router-link>

          <button
            type="button"
            @click="copyWaText"
            class="px-4 py-2.5 rounded-xl bg-white text-emerald-950 hover:bg-emerald-50 text-xs font-extrabold shadow-lg transition-all flex items-center space-x-1.5 cursor-pointer"
          >
            <span>{{ copied ? '✅ Teks Berhasil Disalin!' : '📋 Salin Teks WA' }}</span>
          </button>

          <button
            type="button"
            @click="sendToWhatsApp"
            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-xs font-extrabold text-white shadow-lg shadow-green-600/30 transition-all flex items-center space-x-1.5 cursor-pointer"
          >
            <span>📲 Kirim ke WhatsApp</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Filter Sinkronisasi Otomatis dari Database -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 mb-3 border-b border-slate-100">
        <div>
          <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
            <span>🔄 Filter Sumber Data Otomatis (Database SISQA)</span>
          </h3>
          <p class="text-[11px] text-slate-500">
            Pilih aplikasi atau rentang tanggal untuk menarik data Progress Testing, Temuan Bug, dan Retest secara otomatis ke dalam template WA di bawah.
          </p>
        </div>
        <button
          type="button"
          @click="fetchReportData"
          class="px-3.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold transition-colors cursor-pointer shrink-0"
        >
          🔄 Sinkronkan Ulang dari Database
        </button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Aplikasi Target</label>
          <select
            v-model="filters.application_id"
            @change="handleAppChange"
            class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
          >
            <option value="">Semua Aplikasi</option>
            <option v-for="app in apps" :key="app.id" :value="app.id">{{ app.name }} ({{ app.environment }})</option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Menu Utama / Modul</label>
          <select
            v-model="filters.module_id"
            @change="fetchReportData"
            :disabled="!filters.application_id"
            class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50"
          >
            <option value="">Semua Modul</option>
            <option v-for="m in moduleOptions" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal Mulai</label>
          <input
            type="date"
            v-model="filters.start_date"
            @change="fetchReportData"
            class="w-full text-xs py-1.5 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>

        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal Selesai</label>
          <input
            type="date"
            v-model="filters.end_date"
            @change="fetchReportData"
            class="w-full text-xs py-1.5 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>
      </div>
    </div>

    <LoadingSpinner v-if="loading" text="Menarik data progres pengujian & bug dari database..." />

    <!-- Main 2-Column Workspace: Interactive Builder (Left) & WhatsApp Live Preview (Right) -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- LEFT COLUMN: Form Builder Interaktif (7 cols) -->
      <div class="lg:col-span-7 space-y-5">
        <!-- 1. Informasi Header Laporan -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">
              1️⃣ Identitas &amp; Server Environment
            </h3>
            <span class="text-[11px] text-emerald-700 font-semibold bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
              Header Laporan
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
              <label class="block text-[11px] font-bold text-slate-700 mb-1">📅 Tanggal Laporan</label>
              <input
                v-model="form.tanggal"
                type="text"
                placeholder="Contoh: Kamis, 29 Oktober 2026"
                class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
            </div>

            <div>
              <label class="block text-[11px] font-bold text-slate-700 mb-1">👤 Nama QA</label>
              <input
                v-model="form.qaName"
                type="text"
                placeholder="Nama QA Tester"
                class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
            </div>

            <div class="sm:col-span-2">
              <div class="flex flex-wrap items-center justify-between gap-1 mb-1">
                <label class="block text-[11px] font-bold text-slate-700">🖥️ Server / Environment</label>
                <div class="flex flex-wrap items-center gap-1">
                  <button
                    v-for="srv in serverPresets"
                    :key="srv"
                    type="button"
                    @click="form.serverEnv = srv"
                    :class="[
                      'px-2 py-0.5 rounded-md text-[10px] font-bold transition-colors cursor-pointer border',
                      form.serverEnv === srv
                        ? 'bg-emerald-600 text-white border-emerald-600'
                        : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'
                    ]"
                  >
                    {{ srv }}
                  </button>
                </div>
              </div>
              <input
                v-model="form.serverEnv"
                type="text"
                placeholder="Server QA / Server Baru / ASIS 5173"
                class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
            </div>
          </div>
        </div>

        <!-- 2. 📌 Progress Testing -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">
              2️⃣ 📌 Progress Testing
            </h3>
            <span class="text-[11px] text-slate-500">Otomatis dari database / dapat disesuaikan</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            <div>
              <label class="block text-[11px] font-bold text-emerald-700 mb-1">• Complete</label>
              <input
                v-model="form.progressComplete"
                type="text"
                placeholder="Contoh: 18 TC / 5 Modul"
                class="w-full text-xs py-2 px-3 rounded-xl border border-emerald-200 bg-emerald-50/30 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
            </div>

            <div>
              <label class="block text-[11px] font-bold text-amber-700 mb-1">• On Progress</label>
              <input
                v-model="form.progressOnProgress"
                type="text"
                placeholder="Contoh: 4 TC / 2 Modul"
                class="w-full text-xs py-2 px-3 rounded-xl border border-amber-200 bg-amber-50/30 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500"
              />
            </div>

            <div>
              <label class="block text-[11px] font-bold text-rose-700 mb-1">• Temuan Bug</label>
              <input
                v-model="form.progressBugCount"
                type="text"
                placeholder="Contoh: 3 Temuan"
                class="w-full text-xs py-2 px-3 rounded-xl border border-rose-200 bg-rose-50/30 focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500"
              />
            </div>
          </div>
        </div>

        <!-- 3. 📋 Modul yang Diuji -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
            <div>
              <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">
                3️⃣ 📋 Modul yang Diuji (Persentase Progress %)
              </h3>
              <p class="text-[11px] text-slate-500 mt-0.5">
                Daftar 9 modul standar otomatis ditampilkan. Geser slider atau ketik angka persentase.
              </p>
            </div>
            <div class="flex items-center gap-1.5">
              <button
                type="button"
                @click="resetStandardModules"
                class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold cursor-pointer"
              >
                🔄 9 Modul Standar
              </button>
              <button
                type="button"
                @click="addModuleRow"
                class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold cursor-pointer"
              >
                + Tambah Modul
              </button>
            </div>
          </div>

          <div class="space-y-2.5">
            <div
              v-for="(mod, idx) in form.modules"
              :key="idx"
              class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200/80 bg-slate-50/60 hover:bg-white transition-colors"
            >
              <input
                type="checkbox"
                v-model="mod.enabled"
                class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer shrink-0"
                title="Sertakan modul ini di laporan WA"
              />
              <input
                v-model="mod.name"
                type="text"
                placeholder="Nama Modul"
                class="w-36 sm:w-44 text-xs font-bold text-slate-800 py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-emerald-500"
              />
              <input
                v-model.number="mod.progress"
                type="range"
                min="0"
                max="100"
                step="5"
                class="flex-1 accent-emerald-600 cursor-pointer hidden sm:block"
              />
              <div class="flex items-center gap-1 shrink-0">
                <input
                  v-model.number="mod.progress"
                  type="number"
                  min="0"
                  max="100"
                  class="w-16 text-center text-xs font-extrabold text-emerald-700 py-1.5 px-2 rounded-lg border border-emerald-200 bg-emerald-50/40 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                />
                <span class="text-xs font-bold text-slate-500">%</span>
              </div>
              <button
                v-if="form.modules.length > 1"
                type="button"
                @click="form.modules.splice(idx, 1)"
                class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 text-xs cursor-pointer"
                title="Hapus baris modul"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <!-- 4. 🐞 Temuan Bug -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">
                4️⃣ 🐞 Daftar Temuan Bug
              </h3>
              <p class="text-[11px] text-slate-500 mt-0.5">
                Format: <code>[Nama Menu/Submenu] – [Deskripsi bug singkat]</code> beserta status perbaikan.
              </p>
            </div>
            <button
              type="button"
              @click="addBugRow"
              class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-bold cursor-pointer shrink-0"
            >
              + Tambah Temuan Bug
            </button>
          </div>

          <div v-if="form.bugs.length === 0" class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-center text-xs text-slate-500">
            Tidak ada temuan bug yang ditambahkan. Klik <strong>+ Tambah Temuan Bug</strong> jika ada temuan.
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(bug, bIdx) in form.bugs"
              :key="bIdx"
              class="p-3.5 rounded-xl border border-rose-200/70 bg-rose-50/20 space-y-2.5"
            >
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold text-rose-700 uppercase">Temuan Bug #{{ bIdx + 1 }}</span>
                <button
                  type="button"
                  @click="form.bugs.splice(bIdx, 1)"
                  class="text-[11px] font-bold text-red-600 hover:underline cursor-pointer"
                >
                  Hapus
                </button>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
                <div class="sm:col-span-4">
                  <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Nama Menu / Submenu</label>
                  <input
                    v-model="bug.menu"
                    type="text"
                    placeholder="Contoh: Faktur Penjualan"
                    class="w-full text-xs py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-rose-500"
                  />
                </div>
                <div class="sm:col-span-5">
                  <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Deskripsi Bug Singkat</label>
                  <input
                    v-model="bug.description"
                    type="text"
                    placeholder="Contoh: Selisih pembulatan PPN 11%"
                    class="w-full text-xs py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-rose-500"
                  />
                </div>
                <div class="sm:col-span-3">
                  <label class="block text-[10px] font-bold text-slate-600 mb-0.5">Status</label>
                  <select
                    v-model="bug.status"
                    class="w-full text-xs py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white font-semibold focus:outline-none focus:ring-1 focus:ring-rose-500"
                  >
                    <option value="Belum Fix">Belum Fix</option>
                    <option value="On Progress">On Progress</option>
                    <option value="Fixed">Fixed</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 5. 🔄 Retest -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
              <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">
                5️⃣ 🔄 Daftar Retest
              </h3>
              <p class="text-[11px] text-slate-500 mt-0.5">
                Format: <code>• [Menu/Fitur]: [Hasil retest]</code>
              </p>
            </div>
            <button
              type="button"
              @click="addRetestRow"
              class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold cursor-pointer shrink-0"
            >
              + Tambah Item Retest
            </button>
          </div>

          <div v-if="form.retests.length === 0" class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-center text-xs text-slate-500">
            Belum ada item retest. Klik <strong>+ Tambah Item Retest</strong> untuk menambahkan baris.
          </div>

          <div v-else class="space-y-2.5">
            <div
              v-for="(rt, rIdx) in form.retests"
              :key="rIdx"
              class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 p-2.5 rounded-xl border border-blue-200/60 bg-blue-50/20"
            >
              <input
                v-model="rt.menu"
                type="text"
                placeholder="Menu / Fitur (Contoh: Master Barang)"
                class="sm:w-5/12 text-xs py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
              <input
                v-model="rt.result"
                type="text"
                placeholder="Hasil retest (Contoh: OK / Passed sesuai ekspektasi)"
                class="flex-1 text-xs py-1.5 px-2.5 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
              />
              <button
                type="button"
                @click="form.retests.splice(rIdx, 1)"
                class="px-2.5 py-1.5 rounded-lg text-red-600 hover:bg-red-50 text-xs font-bold cursor-pointer shrink-0"
              >
                Hapus
              </button>
            </div>
          </div>
        </div>

        <!-- 6. 📝 Catatan, 🎯 Rencana Selanjutnya & Status QA Hari Ini -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-4">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">
              6️⃣ Catatan, Rencana Selanjutnya &amp; Status QA Hari Ini
            </h3>
          </div>

          <div>
            <label class="block text-[11px] font-bold text-slate-700 mb-1">📝 Catatan</label>
            <textarea
              v-model="form.catatan"
              rows="2"
              placeholder="Catatan penting terkait testing, kendala, atau follow-up..."
              class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-[11px] font-bold text-slate-700 mb-1">🎯 Rencana Testing Selanjutnya</label>
            <textarea
              v-model="form.rencanaSelanjutnya"
              rows="2"
              placeholder="Modul/menu yang akan dilanjutkan atau diretest..."
              class="w-full text-xs p-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-[11px] font-bold text-slate-700 mb-1.5">🚦 Status QA Hari Ini</label>
            <div class="grid grid-cols-3 gap-2.5">
              <button
                v-for="st in ['AMAN', 'ON PROGRESS', 'ADA TEMUAN']"
                :key="st"
                type="button"
                @click="form.statusHariIni = st"
                :class="[
                  'py-2.5 px-3 rounded-xl text-xs font-extrabold border transition-all cursor-pointer text-center',
                  form.statusHariIni === st
                    ? st === 'AMAN'
                      ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/25'
                      : st === 'ON PROGRESS'
                      ? 'bg-amber-500 text-white border-amber-500 shadow-md shadow-amber-500/25'
                      : 'bg-rose-600 text-white border-rose-600 shadow-md shadow-rose-600/25'
                    : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
                ]"
              >
                {{ st === 'AMAN' ? '✅ AMAN' : st === 'ON PROGRESS' ? '⏳ ON PROGRESS' : '🐞 ADA TEMUAN' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: Sticky WhatsApp Live Preview & Editor (5 cols) -->
      <div class="lg:col-span-5 lg:sticky lg:top-6 space-y-4">
        <div class="rounded-2xl overflow-hidden border border-emerald-800/40 shadow-xl bg-[#0B141A]">
          <!-- WhatsApp Chat Header Bar -->
          <div class="bg-[#202C33] px-4 py-3 flex items-center justify-between border-b border-slate-700/60">
            <div class="flex items-center space-x-3">
              <div class="w-9 h-9 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white font-extrabold text-sm shadow-xs">
                💬
              </div>
              <div>
                <div class="text-xs font-extrabold text-white">Preview Pesan WhatsApp (Live)</div>
                <div class="text-[10px] text-emerald-400 font-medium">QA Daily Testing &amp; Progress Report</div>
              </div>
            </div>
            <button
              type="button"
              @click="rebuildWaTextFromForm"
              class="px-2.5 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-200 text-[10px] font-bold cursor-pointer"
              title="Susun ulang teks dari form di sebelah kiri"
            >
              🔄 Sync Form
            </button>
          </div>

          <!-- WhatsApp Message Bubble Area -->
          <div class="p-4 bg-[#0B141A]">
            <div class="bg-[#005C4B] text-[#E9EDEF] rounded-2xl rounded-tl-none p-3.5 shadow-md">
              <textarea
                v-model="waReportText"
                rows="26"
                class="w-full bg-transparent text-[#E9EDEF] font-mono text-xs leading-relaxed focus:outline-none resize-y"
              ></textarea>
              <div class="flex items-center justify-end gap-1.5 text-[10px] text-emerald-200/80 mt-1 pt-1 border-t border-emerald-400/20">
                <span>{{ currentTimeStr }}</span>
                <span>✓✓</span>
              </div>
            </div>
          </div>

          <!-- Action Footer Inside WA Card -->
          <div class="bg-[#202C33] px-4 py-3.5 border-t border-slate-700/60 flex flex-col sm:flex-row items-center gap-2.5">
            <button
              type="button"
              @click="copyWaText"
              class="w-full sm:w-1/2 py-2.5 px-4 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-extrabold text-xs transition-all cursor-pointer flex items-center justify-center gap-1.5 shadow-sm"
            >
              <span>{{ copied ? '✅ Berhasil Disalin!' : '📋 Salin Teks WA' }}</span>
            </button>
            <button
              type="button"
              @click="sendToWhatsApp"
              class="w-full sm:w-1/2 py-2.5 px-4 rounded-xl bg-[#00A884] hover:bg-[#008f6f] text-white font-extrabold text-xs transition-all cursor-pointer flex items-center justify-center gap-1.5 shadow-sm"
            >
              <span>📲 Kirim ke WhatsApp</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api';
import { useAuth } from '../auth';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const route = useRoute();
const { currentUser } = useAuth();

const loading = ref(false);
const copied = ref(false);
const apps = ref([]);
const moduleOptions = ref([]);
const reportData = ref(null);
const waReportText = ref('');

const serverPresets = [
  'Server QA / Server Baru / ASIS 5173',
  'Server QA',
  'Server Baru',
  'ASIS 5173',
  'Staging Server',
];

const formatIndonesianDate = (dateObj = new Date()) => {
  return dateObj.toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
};

const currentTimeStr = new Date().toLocaleTimeString('id-ID', {
  hour: '2-digit',
  minute: '2-digit',
});

const defaultStandardModules = () => [
  { name: 'Master Data', progress: 0, enabled: true },
  { name: 'Pembelian', progress: 0, enabled: true },
  { name: 'Penjualan', progress: 0, enabled: true },
  { name: 'Stock', progress: 0, enabled: true },
  { name: 'Service', progress: 0, enabled: true },
  { name: 'MRP', progress: 0, enabled: true },
  { name: 'Keuangan', progress: 0, enabled: true },
  { name: 'Laporan', progress: 0, enabled: true },
  { name: 'Perbaikan', progress: 0, enabled: true },
];

const filters = reactive({
  application_id: route.query.application_id || '',
  module_id: route.query.module_id || '',
  start_date: route.query.start_date || '',
  end_date: route.query.end_date || '',
});

const form = reactive({
  tanggal: formatIndonesianDate(),
  qaName: currentUser.value?.name || 'QA Tester',
  serverEnv: 'Server QA / Server Baru / ASIS 5173',
  progressComplete: '0 TC / 0 Modul',
  progressOnProgress: '0 TC / 0 Modul',
  progressBugCount: '0',
  modules: defaultStandardModules(),
  bugs: [],
  retests: [],
  catatan: 'Pengujian berjalan lancar, beberapa temuan bug sedang dikoordinasikan bersama tim developer.',
  rencanaSelanjutnya: 'Melanjutkan pengujian modul yang masih On Progress dan melakukan retest pada perbaikan bug.',
  statusHariIni: 'ON PROGRESS',
});

const mapBugStatusToWa = (st) => {
  if (['Fixed', 'Ready for Retest', 'Closed'].includes(st)) return 'Fixed';
  if (['In Progress'].includes(st)) return 'On Progress';
  return 'Belum Fix';
};

const resetStandardModules = () => {
  form.modules = defaultStandardModules();
  syncModulePercentagesFromReport();
};

const addModuleRow = () => {
  form.modules.push({ name: 'Modul Baru', progress: 0, enabled: true });
};

const addBugRow = () => {
  form.bugs.push({
    menu: 'Nama Menu/Submenu',
    description: 'Deskripsi bug singkat',
    status: 'Belum Fix',
  });
};

const addRetestRow = () => {
  form.retests.push({
    menu: 'Menu/Fitur',
    result: 'Passed / Sesuai ekspektasi',
  });
};

const syncModulePercentagesFromReport = () => {
  if (!reportData.value) return;
  const dbModProgress = reportData.value.module_progress || [];

  // Map matching module names (or partial matches like Inventori -> Stock, Keuangan & Akuntansi -> Keuangan)
  form.modules.forEach((m) => {
    const lowerName = m.name.toLowerCase();
    const matched = dbModProgress.find((dbm) => {
      const dbLower = (dbm.module_name || '').toLowerCase();
      if (dbLower.includes(lowerName) || lowerName.includes(dbLower)) return true;
      if (lowerName === 'stock' && (dbLower.includes('inventori') || dbLower.includes('gudang') || dbLower.includes('stok'))) return true;
      return false;
    });
    if (matched) {
      m.progress = matched.progress;
    }
  });
};

const populateFormFromReportData = () => {
  if (!reportData.value) return;
  const d = reportData.value;
  const s = d.summary || {};
  const modProg = d.module_progress || [];

  const completedMods = modProg.filter((m) => m.progress === 100).length;
  const inProgMods = modProg.filter((m) => m.progress > 0 && m.progress < 100).length;

  // Update serverEnv if a specific app is selected
  if (filters.application_id) {
    const selectedApp = apps.value.find((a) => String(a.id) === String(filters.application_id));
    if (selectedApp) {
      form.serverEnv = `${selectedApp.name} (${selectedApp.environment || 'Server QA'})`;
    }
  }

  form.progressComplete = `${s.pass || 0} TC (${completedMods} Modul)`;
  form.progressOnProgress = `${(s.pending || 0) + (s.not_tested || 0)} TC (${inProgMods} Modul)`;
  form.progressBugCount = `${s.active_bugs ?? s.total_bugs ?? 0}`;

  // Sync module progress
  syncModulePercentagesFromReport();

  // Populate Bugs
  if (d.bugs && d.bugs.length > 0) {
    form.bugs = d.bugs.slice(0, 10).map((b) => ({
      menu: b.menu?.name || b.module?.name || b.application?.name || 'Menu Umum',
      description: b.title || 'Kendala fungsi',
      status: mapBugStatusToWa(b.status),
    }));
  } else {
    form.bugs = [];
  }

  // Populate Retests
  if (d.retests && d.retests.length > 0) {
    form.retests = d.retests.slice(0, 10).map((rt) => ({
      menu: rt.bug?.menu?.name || rt.bug?.module?.name || rt.test_case?.menu?.name || rt.bug?.title || 'Fitur Aplikasi',
      result: `${rt.status}${rt.actual_result ? ' - ' + rt.actual_result : ''}`,
    }));
  } else {
    form.retests = [];
  }

  // Determine Status QA Hari Ini automatically
  if ((s.active_bugs || 0) > 0 || (s.failed || 0) > 0) {
    form.statusHariIni = 'ADA TEMUAN';
  } else if ((s.pending || 0) > 0 || (s.not_tested || 0) > 0) {
    form.statusHariIni = 'ON PROGRESS';
  } else {
    form.statusHariIni = 'AMAN';
  }

  rebuildWaTextFromForm();
};

const rebuildWaTextFromForm = () => {
  const lines = [];
  lines.push(`*QA DAILY TESTING & PROGRESS REPORT*`);
  lines.push(``);
  lines.push(`📅 *Tanggal:* ${form.tanggal || '-'}`);
  lines.push(`👤 *QA:* ${form.qaName || '-'}`);
  lines.push(`🖥️ *Server/Environment:* ${form.serverEnv || '-'}`);
  lines.push(``);
  lines.push(`*📌 Progress Testing*`);
  lines.push(`• *Complete:* ${form.progressComplete || '0'}`);
  lines.push(`• *On Progress:* ${form.progressOnProgress || '0'}`);
  lines.push(`• *Temuan Bug:* ${form.progressBugCount || '0'}`);
  lines.push(``);
  lines.push(`*📋 Modul yang Diuji*`);
  const activeModules = form.modules.filter((m) => m.enabled);
  if (activeModules.length === 0) {
    lines.push(`• -`);
  } else {
    activeModules.forEach((m) => {
      lines.push(`• ${m.name}: ${m.progress ?? 0}%`);
    });
  }
  lines.push(``);
  lines.push(`*🐞 Temuan Bug*`);
  if (!form.bugs || form.bugs.length === 0) {
    lines.push(`• Tidak ada temuan bug baru hari ini.`);
  } else {
    form.bugs.forEach((b, idx) => {
      lines.push(`${idx + 1}. ${b.menu || '-'} – ${b.description || '-'}`);
      lines.push(`   Status: ${b.status || 'Belum Fix'}`);
      if (idx < form.bugs.length - 1) {
        lines.push(``);
      }
    });
  }
  lines.push(``);
  lines.push(`*🔄 Retest*`);
  if (!form.retests || form.retests.length === 0) {
    lines.push(`• Belum ada antrean retest hari ini.`);
  } else {
    form.retests.forEach((rt) => {
      lines.push(`• ${rt.menu || '-'}: ${rt.result || '-'}`);
    });
  }
  lines.push(``);
  lines.push(`*📝 Catatan*`);
  lines.push(`${form.catatan || '-'}`);
  lines.push(``);
  lines.push(`*🎯 Rencana Testing Selanjutnya*`);
  lines.push(`${form.rencanaSelanjutnya || '-'}`);
  lines.push(``);
  lines.push(`*Status QA Hari Ini:* ${form.statusHariIni || 'ON PROGRESS'}`);

  waReportText.value = lines.join('\n');
};

// Auto-update WA preview whenever any form field changes
watch(
  form,
  () => {
    rebuildWaTextFromForm();
  },
  { deep: true }
);

const fetchLookups = async () => {
  try {
    const res = await api.get('/cascade/apps');
    apps.value = res.data;
    if (filters.application_id) {
      const mRes = await api.get(`/cascade/modules?application_id=${filters.application_id}`);
      moduleOptions.value = mRes.data;
    }
  } catch (e) {}
};

const handleAppChange = async () => {
  filters.module_id = '';
  moduleOptions.value = [];
  if (filters.application_id) {
    try {
      const res = await api.get(`/cascade/modules?application_id=${filters.application_id}`);
      moduleOptions.value = res.data;
    } catch (e) {}
  }
  fetchReportData();
};

const fetchReportData = async () => {
  loading.value = true;
  try {
    const res = await api.get('/reports/summary', { params: filters });
    reportData.value = res.data;
    populateFormFromReportData();
  } catch (e) {
    console.error('Fetch WA report data error:', e);
    rebuildWaTextFromForm();
  } finally {
    loading.value = false;
  }
};

const copyWaText = async () => {
  try {
    await navigator.clipboard.writeText(waReportText.value);
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2500);
  } catch (e) {
    const ta = document.createElement('textarea');
    ta.value = waReportText.value;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2500);
  }
};

const sendToWhatsApp = () => {
  const encoded = encodeURIComponent(waReportText.value);
  window.open(`https://wa.me/?text=${encoded}`, '_blank');
};

onMounted(() => {
  fetchLookups();
  fetchReportData();
});
</script>

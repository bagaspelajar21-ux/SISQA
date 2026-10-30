<template>
  <div class="space-y-6">
    <!-- Modern Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-rose-950 to-slate-900 p-6 text-white shadow-lg border border-slate-800">
      <OfficeSkylineBg />
      <div class="absolute -right-12 -top-12 w-56 h-56 rounded-full bg-rose-500/20 blur-2xl pointer-events-none"></div>
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-full bg-rose-500/20 border border-rose-400/30 text-rose-300 text-[10px] font-extrabold uppercase tracking-wider mb-2">
            <span>🐞 Defect &amp; Issue Tracking</span>
          </div>
          <h2 class="text-lg font-black tracking-tight text-white">Bug Tracking &amp; Defect Management</h2>
          <p class="text-xs text-slate-300 mt-0.5">Laporkan temuan bug, unggah bukti (evidence), tetapkan developer penanggung jawab, dan lacak siklus penyelesaian.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
          <button
            v-if="hasFeatureAccess('import_bug_excel')"
            type="button"
            @click="downloadExcelTemplate"
            class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs font-bold text-white shadow-md shadow-emerald-600/25 transition-all flex items-center space-x-1.5 cursor-pointer"
            title="Unduh file template Excel (.xlsx) untuk melaporkan temuan bug secara massal"
          >
            <span>📥 Template Excel</span>
          </button>

          <button
            v-if="hasFeatureAccess('import_bug_excel')"
            type="button"
            @click="openImportExcelModal"
            class="px-3.5 py-2 rounded-xl bg-teal-600 hover:bg-teal-500 text-xs font-bold text-white shadow-md shadow-teal-600/25 transition-all flex items-center space-x-1.5 cursor-pointer"
            title="Unggah file Excel (.xlsx/.xls/.csv) berisi daftar temuan bug"
          >
            <span>📤 Upload Excel</span>
          </button>

          <button
            v-if="hasFeatureAccess('create_bug')"
            @click="openCreateModal"
            class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-xs font-bold text-white shadow-md shadow-rose-600/30 transition-all flex items-center space-x-1.5 cursor-pointer"
          >
            <span>+ Laporkan Bug Baru</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Bug Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[11px] font-bold text-slate-400 uppercase">Total Tiket Bug</div>
          <div class="text-2xl font-black text-slate-900 mt-0.5">{{ pagination.total || bugs.length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg">🐞</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-red-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-red-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-red-700 uppercase">Critical &amp; Major</div>
          <div class="text-2xl font-black text-red-700 mt-0.5">{{ bugs.filter(b => b.severity === 'Critical' || b.severity === 'Major').length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-red-100 text-red-700 flex items-center justify-center text-lg">🚨</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-cyan-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-cyan-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-cyan-700 uppercase">Ready for Retest</div>
          <div class="text-2xl font-black text-cyan-700 mt-0.5">{{ bugs.filter(b => b.status === 'Ready for Retest').length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center text-lg">🔄</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-emerald-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-emerald-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-emerald-700 uppercase">Tuntas (Closed)</div>
          <div class="text-2xl font-black text-emerald-700 mt-0.5">{{ bugs.filter(b => b.status === 'Closed').length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg">✅</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Cari Bug</label>
          <input
            v-model="filters.search"
            @input="debounceFetch"
            type="text"
            placeholder="Kode, judul, atau deskripsi..."
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Aplikasi</label>
          <select
            v-model="filters.application_id"
            @change="fetchBugs"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Aplikasi</option>
            <option v-for="app in apps" :key="app.id" :value="app.id">{{ app.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Severity</label>
          <select
            v-model="filters.severity"
            @change="fetchBugs"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Severity</option>
            <option value="Critical">Critical</option>
            <option value="Major">Major</option>
            <option value="Minor">Minor</option>
            <option value="Trivial">Trivial</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Status Bug</label>
          <select
            v-model="filters.status"
            @change="fetchBugs"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Status</option>
            <option value="Open">Open</option>
            <option value="Assigned">Assigned</option>
            <option value="In Progress">In Progress</option>
            <option value="Fixed">Fixed</option>
            <option value="Ready for Retest">Ready for Retest</option>
            <option value="Reopened">Reopened</option>
            <option value="Closed">Closed</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>
        <div class="flex items-end">
          <button
            @click="resetFilters"
            class="w-full py-2 px-3 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-600 transition-colors cursor-pointer"
          >
            Reset Filter
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <LoadingSpinner v-if="loading && bugs.length === 0" text="Memuat tiket bug..." />

    <!-- Table -->
    <div v-else class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div v-if="bugs.length === 0" class="p-8">
        <EmptyState title="Tidak Ada Tiket Bug" description="Belum ada tiket bug yang cocok dengan kriteria filter." />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
              <th class="py-3 px-4">Kode & Judul Bug</th>
              <th class="py-3 px-4">Aplikasi & Modul</th>
              <th class="py-3 px-4">Severity & Priority</th>
              <th class="py-3 px-4">Pelapor & Assignee</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4">Tanggal</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <tr v-for="b in bugs" :key="b.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900">{{ b.title }}</div>
                <div class="text-[10px] font-mono text-red-600 font-bold">{{ b.bug_code }}</div>
              </td>
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-800">{{ b.application?.name || '-' }}</div>
                <div class="flex flex-wrap items-center gap-1 mt-1">
                  <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200/70 text-[10px] font-semibold">
                    🗂️ {{ b.module?.name || 'Umum' }}
                  </span>
                  <span v-if="b.menu?.name" class="px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 border border-sky-200/70 text-[10px] font-semibold">
                    📌 {{ b.menu.name }}
                  </span>
                </div>
              </td>
              <td class="py-3.5 px-4">
                <div class="flex items-center space-x-1.5">
                  <Badge :value="b.severity" type="severity" />
                  <Badge :value="b.priority" type="priority" :dot="false" />
                </div>
              </td>
              <td class="py-3.5 px-4">
                <div class="text-slate-800">👤 {{ b.reporter?.name }}</div>
                <div class="text-[10px] text-slate-500">🛠️ {{ b.assignee?.name || 'Belum Ditugaskan' }}</div>
              </td>
              <td class="py-3.5 px-4">
                <Badge :value="b.status" />
              </td>
              <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                {{ formatDate(b.report_date) }}
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="openDetailModal(b)"
                    class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-[11px] font-semibold transition-colors cursor-pointer"
                    title="Lihat Detail & Riwayat"
                  >
                    Detail
                  </button>
                  <button
                    v-if="hasFeatureAccess('edit_bug')"
                    @click="openEditModal(b)"
                    class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[11px] font-semibold transition-colors cursor-pointer"
                    title="Ubah Status / Penugasan"
                  >
                    Edit
                  </button>
                  <button
                    v-if="isAdmin"
                    @click="confirmDelete(b)"
                    class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-[11px] font-semibold transition-colors cursor-pointer"
                    title="Hapus Bug"
                  >
                    Hapus
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="pagination.total > pagination.per_page" class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
          <div>Menampilkan {{ bugs.length }} dari {{ pagination.total }} bug</div>
          <div class="flex space-x-1">
            <button
              :disabled="pagination.current_page <= 1"
              @click="changePage(pagination.current_page - 1)"
              class="px-2.5 py-1 rounded border border-slate-200 disabled:opacity-40"
            >
              Sebelumnya
            </button>
            <button
              :disabled="pagination.current_page >= pagination.last_page"
              @click="changePage(pagination.current_page + 1)"
              class="px-2.5 py-1 rounded border border-slate-200 disabled:opacity-40"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Bug Modal -->
    <Modal
      :show="showCreateModal"
      title="Laporkan Tiket Bug Baru"
      subtitle="Deskripsikan anomali atau kegagalan pengujian yang ditemukan"
      size="xl"
      @close="showCreateModal = false"
    >
      <form @submit.prevent="submitCreateBug" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Judul Bug <span class="text-red-500">*</span></label>
            <input
              v-model="createForm.title"
              type="text"
              required
              placeholder="Contoh: Error 500 saat simpan faktur penjualan tanpa diskon"
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
          </div>

          <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-3.5 p-3.5 rounded-xl bg-slate-50/90 border border-slate-200/80">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">🏢 Aplikasi <span class="text-red-500">*</span></label>
              <select
                v-model="createForm.application_id"
                @change="handleCreateAppChange"
                required
                class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
              >
                <option value="" disabled>Pilih Aplikasi</option>
                <option v-for="app in apps" :key="app.id" :value="app.id">{{ app.name }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">🗂️ Menu Utama (Modul)</label>
              <select
                v-model="createForm.module_id"
                @change="handleCreateModuleChange"
                class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
              >
                <option value="">Pilih Modul (Menu Utama)</option>
                <option v-for="mod in createModuleOptions" :key="mod.id" :value="mod.id">{{ mod.name }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                📌 Sub-Menu / Fitur
                <span v-if="createForm.module_id" class="text-blue-600 font-semibold ml-1">({{ createMenuOptions.length }})</span>
              </label>
              <select
                v-model="createForm.menu_id"
                :disabled="!createForm.module_id"
                class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:bg-slate-100 disabled:text-slate-400"
              >
                <option value="">{{ createForm.module_id ? 'Pilih Sub-Menu (Opsional)' : 'Pilih Modul dahulu...' }}</option>
                <option v-for="mn in createMenuOptions" :key="mn.id" :value="mn.id">📌 {{ mn.name }} ({{ mn.code }})</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Severity (Tingkat Dampak) <span class="text-red-500">*</span></label>
            <select
              v-model="createForm.severity"
              required
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="Critical">Critical (Sistem Mati / Blocker)</option>
              <option value="Major">Major (Fitur Utama Gagal)</option>
              <option value="Minor">Minor (Fungsi Parsial / UI Glitch)</option>
              <option value="Trivial">Trivial (Kosmetik / Typo)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Priority (Urgensi Perbaikan) <span class="text-red-500">*</span></label>
            <select
              v-model="createForm.priority"
              required
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="Urgent">Urgent</option>
              <option value="High">High</option>
              <option value="Medium">Medium</option>
              <option value="Low">Low</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Penanggung Jawab (Developer)</label>
            <select
              v-model="createForm.assignee_id"
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="">Belum Ditugaskan</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Lengkap Bug <span class="text-red-500">*</span></label>
            <textarea
              v-model="createForm.description"
              rows="3"
              required
              placeholder="Jelaskan detail bug, kondisi lingkungan, pesan error yang muncul..."
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            ></textarea>
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Langkah Reproduksi (Steps to Reproduce)</label>
            <textarea
              v-model="createForm.steps_to_reproduce"
              rows="3"
              placeholder="1. Login sebagai X&#10;2. Buka menu Y&#10;3. Masukkan input Z lalu submit"
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Ekspektasi Hasil (Expected Result)</label>
            <textarea
              v-model="createForm.expected_result"
              rows="2"
              placeholder="Apa yang seharusnya terjadi..."
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Hasil Aktual (Actual Result)</label>
            <textarea
              v-model="createForm.actual_result"
              rows="2"
              placeholder="Apa yang salah atau terjadi di sistem..."
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            ></textarea>
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Unggah Evidence Awal (Screenshot/Video/PDF)</label>
            <input
              type="file"
              multiple
              @change="handleCreateFiles"
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 bg-slate-50"
            />
          </div>
        </div>

        <div class="flex justify-end space-x-2.5 pt-4 border-t border-slate-100">
          <button
            type="button"
            @click="showCreateModal = false"
            class="px-4 py-2 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer disabled:opacity-50"
          >
            {{ saving ? 'Menyimpan...' : 'Laporkan Bug' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Edit Bug Modal -->
    <Modal
      :show="showEditModal"
      :title="`Ubah Status & Penugasan: ${selectedBug?.bug_code || ''}`"
      subtitle="Perbarui siklus hidup bug dan tambahkan catatan perubahan"
      size="md"
      @close="showEditModal = false"
    >
      <form @submit.prevent="submitUpdateBug" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Status Bug <span class="text-red-500">*</span></label>
          <select
            v-model="editForm.status"
            required
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="Open">Open</option>
            <option value="Assigned">Assigned</option>
            <option value="In Progress">In Progress</option>
            <option value="Fixed">Fixed</option>
            <option value="Ready for Retest">Ready for Retest</option>
            <option value="Reopened">Reopened</option>
            <option value="Closed">Closed</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Penanggung Jawab (Developer)</label>
          <select
            v-model="editForm.assignee_id"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Belum Ditugaskan</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Perubahan Riwayat</label>
          <textarea
            v-model="editForm.history_note"
            rows="3"
            placeholder="Alasan perubahan status atau progres perbaikan..."
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
          ></textarea>
        </div>

        <div class="flex justify-end space-x-2.5 pt-4 border-t border-slate-100">
          <button
            type="button"
            @click="showEditModal = false"
            class="px-4 py-2 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer disabled:opacity-50"
          >
            {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Detail Drawer / Modal -->
    <Modal
      :show="showDetailModal"
      :title="`Detail Bug: ${detailData?.bug_code || ''}`"
      size="xl"
      @close="showDetailModal = false"
    >
      <div v-if="detailData" class="space-y-5 text-xs">
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
          <div class="text-base font-bold text-slate-900 mb-1.5">{{ detailData.title }}</div>
          <div class="flex flex-wrap gap-2">
            <Badge :value="detailData.status" />
            <Badge :value="detailData.severity" type="severity" />
            <Badge :value="detailData.priority" type="priority" />
            <span class="bg-slate-200 text-slate-800 px-2 py-0.5 rounded font-medium">{{ detailData.application?.name }}</span>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="bg-slate-50/50 p-3 rounded-xl border border-slate-100">
            <span class="font-bold text-slate-700 block mb-1">Pelapor & Developer:</span>
            <div class="text-slate-700">Pelapor: <strong>{{ detailData.reporter?.name }}</strong></div>
            <div class="text-slate-700">Developer: <strong>{{ detailData.assignee?.name || 'Belum Ditugaskan' }}</strong></div>
          </div>
          <div class="bg-slate-50/50 p-3 rounded-xl border border-slate-100">
            <span class="font-bold text-slate-700 block mb-1">Keterkaitan Test Case / Flow:</span>
            <div class="text-slate-700">Test Case: <strong>{{ detailData.test_case?.test_case_code || '-' }}</strong></div>
            <div class="text-slate-700">Flow: <strong>{{ detailData.flow?.title || '-' }}</strong></div>
          </div>
        </div>

        <div class="bg-slate-50/50 p-3 rounded-xl border border-slate-100">
          <span class="font-bold text-slate-700 block mb-1">Deskripsi Bug:</span>
          <div class="whitespace-pre-line text-slate-700 leading-relaxed">{{ detailData.description }}</div>
        </div>

        <div v-if="detailData.steps_to_reproduce" class="bg-slate-50/50 p-3 rounded-xl border border-slate-100">
          <span class="font-bold text-slate-700 block mb-1">Langkah Reproduksi:</span>
          <div class="whitespace-pre-line text-slate-700 leading-relaxed">{{ detailData.steps_to_reproduce }}</div>
        </div>

        <!-- Evidences Section -->
        <div>
          <h4 class="font-bold text-slate-900 text-xs mb-2">Berkas Evidence (Bukti Awal, Perbaikan, & Retesting):</h4>
          <div v-if="!detailData.evidences || detailData.evidences.length === 0" class="text-slate-400 italic">
            Tidak ada bukti berkas yang dilampirkan.
          </div>
          <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <div
              v-for="ev in detailData.evidences"
              :key="ev.id"
              class="p-2.5 rounded-lg border border-slate-200 bg-white flex items-center justify-between"
            >
              <div class="truncate mr-2">
                <div class="font-bold text-[11px] truncate text-slate-800">{{ ev.original_name }}</div>
                <div class="text-[10px] text-slate-400 uppercase font-semibold">Tipe: {{ ev.evidence_type }}</div>
              </div>
              <a
                :href="`/api/files/bug/${ev.id}/download`"
                target="_blank"
                class="px-2 py-1 rounded bg-blue-50 text-blue-600 hover:bg-blue-100 font-bold text-[10px] shrink-0"
              >
                Unduh
              </a>
            </div>
          </div>
        </div>

        <!-- History Section -->
        <div>
          <h4 class="font-bold text-slate-900 text-xs mb-2">Riwayat Audit Perubahan Status Bug:</h4>
          <div class="space-y-2 max-h-48 overflow-y-auto">
            <div
              v-for="h in detailData.histories"
              :key="h.id"
              class="p-2.5 rounded-lg border border-slate-200 bg-white text-[11px] flex items-start justify-between"
            >
              <div>
                <span class="font-bold text-slate-800">{{ h.user?.name }}</span>:
                <span class="text-slate-600">{{ h.notes || 'Status diubah' }}</span>
                <span class="text-slate-400 text-[10px] block mt-0.5">{{ formatDateTime(h.created_at) }}</span>
              </div>
              <Badge :value="h.new_value" />
            </div>
          </div>
        </div>
      </div>
    </Modal>

    <!-- Import Excel Modal -->
    <Modal
      :show="showImportModal"
      title="📤 Upload / Import Excel Bug Tracking"
      subtitle="Impor daftar temuan bug secara massal menggunakan format template Excel SISQA"
      size="lg"
      @close="showImportModal = false"
    >
      <form @submit.prevent="submitImportExcel" class="space-y-4 text-xs">
        <div class="p-4 rounded-xl bg-rose-50/80 border border-rose-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div class="font-extrabold text-rose-900">1️⃣ Belum punya format Excel-nya?</div>
            <p class="text-[11px] text-rose-700 mt-0.5">
              Unduh template resmi agar kolom Aplikasi, Modul, Sub-Menu, Judul Bug, Deskripsi, Severity, dan Status sesuai standar.
            </p>
          </div>
          <button
            type="button"
            @click="downloadExcelTemplate"
            class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shrink-0 cursor-pointer"
          >
            📥 Download Template .xlsx
          </button>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Aplikasi Default (Jika kolom Aplikasi di Excel kosong)</label>
          <select v-model="importForm.application_id" class="w-full py-2 px-3 border border-slate-300 rounded-xl">
            <option value="">Sesuai Nama Aplikasi di Kolom Excel</option>
            <option v-for="app in apps" :key="app.id" :value="app.id">{{ app.name }}</option>
          </select>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">2️⃣ Pilih Berkas Excel (.xlsx, .xls, .csv) <span class="text-red-500">*</span></label>
          <input
            type="file"
            required
            accept=".xlsx,.xls,.csv"
            @change="handleImportFileSelect"
            class="w-full py-2.5 px-3 border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 hover:bg-white cursor-pointer"
          />
          <p v-if="importForm.file" class="text-[11px] text-emerald-700 font-bold mt-1.5">
            ✅ File terpilih: {{ importForm.file.name }}
          </p>
        </div>

        <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
          <button
            type="button"
            @click="showImportModal = false"
            class="px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="importing || !importForm.file"
            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-extrabold rounded-xl cursor-pointer disabled:opacity-50"
          >
            {{ importing ? 'Memproses Import...' : '🚀 Proses Import Excel' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Delete Confirmation -->
    <ConfirmDialog
      :show="showDeleteConfirm"
      title="Hapus Tiket Bug"
      :message="`Apakah Anda yakin ingin menghapus bug '${bugToDelete?.bug_code}'?`"
      confirmText="Ya, Hapus"
      :isDanger="true"
      @confirm="executeDelete"
      @cancel="showDeleteConfirm = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '../api';
import { useAuth } from '../auth';
import Badge from '../components/Badge.vue';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import EmptyState from '../components/EmptyState.vue';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const { isAdmin, hasFeatureAccess } = useAuth();

const loading = ref(false);
const saving = ref(false);
const bugs = ref([]);
const apps = ref([]);
const users = ref([]);
const createModuleOptions = ref([]);
const createMenuOptions = ref([]);

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 15,
});

const filters = reactive({
  search: '',
  application_id: '',
  severity: '',
  status: '',
});

const showCreateModal = ref(false);
const createFiles = ref([]);
const createForm = reactive({
  title: '',
  application_id: '',
  module_id: '',
  menu_id: '',
  severity: 'Major',
  priority: 'High',
  assignee_id: '',
  description: '',
  steps_to_reproduce: '',
  expected_result: '',
  actual_result: '',
});

const showEditModal = ref(false);
const selectedBug = ref(null);
const editForm = reactive({
  status: 'Open',
  assignee_id: '',
  history_note: '',
});

const showDetailModal = ref(false);
const detailData = ref(null);
const showDeleteConfirm = ref(false);
const bugToDelete = ref(null);

const showImportModal = ref(false);
const importing = ref(false);
const importForm = reactive({
  application_id: '',
  file: null,
});

const downloadExcelTemplate = async () => {
  try {
    const res = await api.get('/bugs/excel-template', { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([res.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'Template_Import_BugTracking_SISQA.xlsx');
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (e) {
    alert('Gagal mengunduh template Excel Bug Tracking.');
  }
};

const openImportExcelModal = () => {
  importForm.application_id = filters.application_id || apps.value[0]?.id || '';
  importForm.file = null;
  showImportModal.value = true;
};

const handleImportFileSelect = (e) => {
  importForm.file = e.target.files?.[0] || null;
};

const submitImportExcel = async () => {
  if (!importForm.file) return;
  importing.value = true;
  try {
    const fd = new FormData();
    fd.append('file', importForm.file);
    if (importForm.application_id) {
      fd.append('application_id', importForm.application_id);
    }
    const res = await api.post('/bugs/import-excel', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    alert(res.data?.message || 'Import Excel Bug berhasil.');
    showImportModal.value = false;
    fetchBugs(1);
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal mengimpor file Excel Bug Tracking.');
  } finally {
    importing.value = false;
  }
};

const formatDate = (str) => {
  if (!str) return '-';
  const d = new Date(str);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatDateTime = (str) => {
  if (!str) return '-';
  const d = new Date(str);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const fetchLookups = async () => {
  try {
    const [appRes, userRes] = await Promise.all([
      api.get('/cascade/apps'),
      api.get('/cascade/users'),
    ]);
    apps.value = appRes.data;
    users.value = userRes.data;
  } catch (e) {}
};

const fetchBugs = async (page = 1) => {
  loading.value = true;
  try {
    const res = await api.get('/bugs', {
      params: { ...filters, page },
    });
    bugs.value = res.data.data;
    pagination.current_page = res.data.current_page;
    pagination.last_page = res.data.last_page;
    pagination.total = res.data.total;
  } catch (e) {
    console.error('Fetch bugs error:', e);
  } finally {
    loading.value = false;
  }
};

let debounceTimeout = null;
const debounceFetch = () => {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    fetchBugs(1);
  }, 300);
};

const resetFilters = () => {
  filters.search = '';
  filters.application_id = '';
  filters.severity = '';
  filters.status = '';
  fetchBugs(1);
};

const changePage = (p) => {
  fetchBugs(p);
};

const handleCreateAppChange = async () => {
  createForm.module_id = '';
  createForm.menu_id = '';
  createModuleOptions.value = [];
  createMenuOptions.value = [];
  if (createForm.application_id) {
    const res = await api.get(`/cascade/modules?application_id=${createForm.application_id}`);
    createModuleOptions.value = res.data;
  }
};

const handleCreateModuleChange = async () => {
  createForm.menu_id = '';
  createMenuOptions.value = [];
  if (createForm.module_id) {
    const res = await api.get(`/cascade/menus?module_id=${createForm.module_id}`);
    createMenuOptions.value = res.data;
  }
};

const openCreateModal = () => {
  createForm.title = '';
  createForm.application_id = apps.value[0]?.id || '';
  createForm.module_id = '';
  createForm.menu_id = '';
  createForm.severity = 'Major';
  createForm.priority = 'High';
  createForm.assignee_id = '';
  createForm.description = '';
  createForm.steps_to_reproduce = '';
  createForm.expected_result = '';
  createForm.actual_result = '';
  createFiles.value = [];
  handleCreateAppChange();
  showCreateModal.value = true;
};

const handleCreateFiles = (e) => {
  createFiles.value = Array.from(e.target.files);
};

const submitCreateBug = async () => {
  saving.value = true;
  try {
    const fd = new FormData();
    Object.keys(createForm).forEach((k) => {
      if (createForm[k]) fd.append(k, createForm[k]);
    });
    createFiles.value.forEach((file) => {
      fd.append('evidences[]', file);
    });

    await api.post('/bugs', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    showCreateModal.value = false;
    fetchBugs(pagination.current_page);
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal melaporkan bug.');
  } finally {
    saving.value = false;
  }
};

const openEditModal = (b) => {
  selectedBug.value = b;
  editForm.status = b.status;
  editForm.assignee_id = b.assignee_id || '';
  editForm.history_note = '';
  showEditModal.value = true;
};

const submitUpdateBug = async () => {
  saving.value = true;
  try {
    await api.put(`/bugs/${selectedBug.value.id}`, {
      ...selectedBug.value,
      status: editForm.status,
      assignee_id: editForm.assignee_id || null,
      history_note: editForm.history_note,
    });
    showEditModal.value = false;
    fetchBugs(pagination.current_page);
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal memperbarui bug.');
  } finally {
    saving.value = false;
  }
};

const openDetailModal = async (b) => {
  try {
    const res = await api.get(`/bugs/${b.id}`);
    detailData.value = res.data.bug;
    showDetailModal.value = true;
  } catch (e) {}
};

const confirmDelete = (b) => {
  bugToDelete.value = b;
  showDeleteConfirm.value = true;
};

const executeDelete = async () => {
  if (!bugToDelete.value) return;
  try {
    await api.delete(`/bugs/${bugToDelete.value.id}`);
    showDeleteConfirm.value = false;
    fetchBugs(pagination.current_page);
  } catch (e) {
    alert('Gagal menghapus tiket bug.');
  }
};

onMounted(() => {
  fetchLookups();
  fetchBugs();
});
</script>

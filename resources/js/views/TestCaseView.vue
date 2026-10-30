<template>
  <div class="space-y-6">
    <!-- Modern Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-900 p-6 text-white shadow-lg border border-slate-800">
      <OfficeSkylineBg />
      <div class="absolute -right-12 -top-12 w-56 h-56 rounded-full bg-indigo-500/20 blur-2xl pointer-events-none"></div>
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-[10px] font-extrabold uppercase tracking-wider mb-2">
            <span>📝 Test Repository &amp; Execution</span>
          </div>
          <h2 class="text-lg font-black tracking-tight text-white">Test Management &amp; Eksekusi Pengujian</h2>
          <p class="text-xs text-slate-300 mt-0.5">Kelola repositori skenario pengujian, prasyarat, langkah uji, ekspektasi hasil, dan catat riwayat eksekusi beserta bukti.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
          <button
            v-if="hasFeatureAccess('import_test_excel')"
            type="button"
            @click="downloadExcelTemplate"
            class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs font-bold text-white shadow-md shadow-emerald-600/25 transition-all flex items-center space-x-1.5 cursor-pointer"
            title="Unduh file template Excel (.xlsx) untuk mengisi skenario Test Case secara massal"
          >
            <span>📥 Template Excel</span>
          </button>

          <button
            v-if="hasFeatureAccess('import_test_excel')"
            type="button"
            @click="openImportExcelModal"
            class="px-3.5 py-2 rounded-xl bg-teal-600 hover:bg-teal-500 text-xs font-bold text-white shadow-md shadow-teal-600/25 transition-all flex items-center space-x-1.5 cursor-pointer"
            title="Unggah file Excel (.xlsx/.xls/.csv) berisi daftar Test Case"
          >
            <span>📤 Upload Excel</span>
          </button>

          <button
            v-if="hasFeatureAccess('create_test')"
            @click="openCreateModal"
            class="px-4 py-2 rounded-xl bg-blue-500 hover:bg-blue-400 text-xs font-bold text-white shadow-md shadow-blue-500/25 transition-all cursor-pointer"
          >
            + Tambah Test Case Baru
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Test Case Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[11px] font-bold text-slate-400 uppercase">Total Skenario</div>
          <div class="text-2xl font-black text-slate-900 mt-0.5">{{ pagination.total || testCases.length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg">📝</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-emerald-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-emerald-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-emerald-700 uppercase">Status PASS</div>
          <div class="text-2xl font-black text-emerald-700 mt-0.5">{{ testCases.filter(t => t.latest_execution?.status === 'PASS').length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg">✅</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-red-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-red-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-red-700 uppercase">Status FAILED</div>
          <div class="text-2xl font-black text-red-700 mt-0.5">{{ testCases.filter(t => t.latest_execution?.status === 'FAILED').length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-red-100 text-red-700 flex items-center justify-center text-lg">❌</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-amber-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-amber-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-amber-700 uppercase">Belum / Pending</div>
          <div class="text-2xl font-black text-amber-700 mt-0.5">{{ testCases.filter(t => !t.latest_execution || t.latest_execution?.status === 'PENDING' || t.latest_execution?.status === 'NOT TESTED').length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg">⏳</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Cari Skenario</label>
          <input
            v-model="filters.search"
            @input="debounceFetch"
            type="text"
            placeholder="Cari kode, judul, atau kata kunci..."
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Aplikasi</label>
          <select
            v-model="filters.application_id"
            @change="fetchTestCases"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Aplikasi</option>
            <option v-for="app in apps" :key="app.id" :value="app.id">{{ app.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Status Hasil</label>
          <select
            v-model="filters.status"
            @change="fetchTestCases"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Status</option>
            <option value="PASS">PASS</option>
            <option value="FAILED">FAILED</option>
            <option value="PENDING">PENDING</option>
            <option value="NOT TESTED">NOT TESTED</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Prioritas</label>
          <select
            v-model="filters.priority"
            @change="fetchTestCases"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Prioritas</option>
            <option value="Urgent">Urgent</option>
            <option value="High">High</option>
            <option value="Medium">Medium</option>
            <option value="Low">Low</option>
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
    <LoadingSpinner v-if="loading && testCases.length === 0" text="Memuat daftar test case..." />

    <!-- Table -->
    <div v-else class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div v-if="testCases.length === 0" class="p-8">
        <EmptyState title="Tidak Ada Test Case" description="Belum ada test case yang memenuhi kriteria filter." />
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
              <th class="py-3 px-4">Kode & Skenario</th>
              <th class="py-3 px-4">Aplikasi & Modul</th>
              <th class="py-3 px-4">Prioritas</th>
              <th class="py-3 px-4">Tester Bertugas</th>
              <th class="py-3 px-4">Hasil Terkini</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <tr v-for="tc in testCases" :key="tc.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900">{{ tc.title }}</div>
                <div class="text-[10px] font-mono text-blue-600">{{ tc.test_case_code }}</div>
              </td>
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-800">{{ tc.application?.name || '-' }}</div>
                <div class="flex flex-wrap items-center gap-1 mt-1">
                  <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200/70 text-[10px] font-semibold">
                    🗂️ {{ tc.module?.name || 'Umum' }}
                  </span>
                  <span v-if="tc.menu?.name" class="px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 border border-sky-200/70 text-[10px] font-semibold">
                    📌 {{ tc.menu.name }}
                  </span>
                </div>
              </td>
              <td class="py-3.5 px-4">
                <Badge :value="tc.priority" type="priority" :dot="false" />
              </td>
              <td class="py-3.5 px-4 text-slate-700">
                {{ tc.assigned_tester?.name || 'Belum Ditugaskan' }}
              </td>
              <td class="py-3.5 px-4">
                <Badge :value="tc.latest_execution?.status || 'NOT TESTED'" />
                <div v-if="tc.latest_execution" class="text-[10px] text-slate-400 mt-0.5">
                  oleh {{ tc.latest_execution.tester?.name }}
                </div>
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- Eksekusi Button / Locked if Schedule Expired -->
                  <span
                    v-if="tc.is_schedule_expired"
                    class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 border border-rose-200 font-bold text-[11px] cursor-not-allowed select-none"
                    :title="`Jadwal QA (${tc.expired_schedule_code || ''}) telah melewati batas waktu (${tc.expired_schedule_end || ''}). Tidak dapat melanjutkan QA.`"
                  >
                    🔒 Jadwal Lewat Waktu
                  </span>
                  <button
                    v-else-if="hasFeatureAccess('execute_test')"
                    @click="openExecuteModal(tc)"
                    class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 font-bold text-[11px] transition-colors cursor-pointer"
                    title="Eksekusi Testing"
                  >
                    ▶ Eksekusi
                  </button>
                  <button
                    @click="openDetailModal(tc)"
                    class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-[11px] font-semibold transition-colors cursor-pointer"
                    title="Lihat Detail & Riwayat"
                  >
                    Detail
                  </button>
                  <button
                    v-if="isAdmin"
                    @click="openEditModal(tc)"
                    class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[11px] font-semibold transition-colors cursor-pointer"
                    title="Edit Skenario"
                  >
                    Edit
                  </button>
                  <button
                    v-if="isAdmin"
                    @click="confirmDelete(tc)"
                    class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-[11px] font-semibold transition-colors cursor-pointer"
                    title="Hapus Skenario"
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
          <div>Menampilkan {{ testCases.length }} dari {{ pagination.total }} test case</div>
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

    <!-- Execution Modal -->
    <Modal
      :show="showExecuteModal"
      :title="`Eksekusi Pengujian: ${selectedTestCase?.test_case_code || ''}`"
      :subtitle="selectedTestCase?.title"
      size="lg"
      @close="showExecuteModal = false"
    >
      <form @submit.prevent="submitExecution" class="space-y-4">
        <!-- Expected Result Info -->
        <div class="p-3 bg-blue-50/60 rounded-xl border border-blue-100 text-xs">
          <div class="font-bold text-blue-900 mb-1">Hasil yang Diharapkan (Expected Result):</div>
          <div class="text-blue-800 leading-relaxed">{{ selectedTestCase?.expected_result }}</div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Status Hasil Pengujian <span class="text-red-500">*</span></label>
          <div class="grid grid-cols-4 gap-2">
            <label
              v-for="st in ['PASS', 'FAILED', 'PENDING', 'NOT TESTED']"
              :key="st"
              :class="[
                'border rounded-xl p-2.5 text-center font-bold text-xs cursor-pointer transition-all flex flex-col items-center justify-center space-y-1',
                execForm.status === st ? 'ring-2 ring-blue-500 bg-blue-50 border-blue-300 text-blue-900' : 'bg-white hover:bg-slate-50 border-slate-200 text-slate-700'
              ]"
            >
              <input type="radio" v-model="execForm.status" :value="st" class="sr-only" />
              <span>{{ st }}</span>
            </label>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Hasil Aktual Pengujian (Actual Result)</label>
          <textarea
            v-model="execForm.actual_result"
            rows="3"
            placeholder="Jelaskan apa yang sebenarnya terjadi pada sistem saat pengujian dijalankan..."
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
          ></textarea>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
          <textarea
            v-model="execForm.notes"
            rows="2"
            placeholder="Catatan lingkungan pengujian, versi browser, atau kendala..."
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
          ></textarea>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Unggah Evidence / Bukti Testing (PNG, JPG, PDF, MP4)</label>
          <input
            type="file"
            multiple
            @change="handleExecFileChange"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 bg-slate-50"
          />
        </div>

        <div class="flex justify-end space-x-2.5 pt-4 border-t border-slate-100">
          <button
            type="button"
            @click="showExecuteModal = false"
            class="px-4 py-2 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="executing"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer disabled:opacity-50"
          >
            {{ executing ? 'Menyimpan...' : 'Simpan Hasil Testing' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Create / Edit Modal -->
    <Modal
      :show="showModal"
      :title="isEdit ? 'Ubah Test Case' : 'Tambah Test Case Baru'"
      subtitle="Definisikan skenario dan langkah pengujian"
      size="xl"
      @close="showModal = false"
    >
      <form @submit.prevent="saveTestCase" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Judul Test Case <span class="text-red-500">*</span></label>
            <input
              v-model="form.title"
              type="text"
              required
              placeholder="Contoh: Validasi kalkulasi PPN 11% pada faktur penjualan"
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
          </div>

          <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-3.5 p-3.5 rounded-xl bg-slate-50/90 border border-slate-200/80">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">🏢 Aplikasi <span class="text-red-500">*</span></label>
              <select
                v-model="form.application_id"
                @change="handleFormAppChange"
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
                v-model="form.module_id"
                @change="handleFormModuleChange"
                class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
              >
                <option value="">Pilih Modul (Menu Utama)</option>
                <option v-for="mod in formModuleOptions" :key="mod.id" :value="mod.id">{{ mod.name }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                📌 Sub-Menu / Fitur
                <span v-if="form.module_id" class="text-blue-600 font-semibold ml-1">({{ formMenuOptions.length }})</span>
              </label>
              <select
                v-model="form.menu_id"
                :disabled="!form.module_id"
                class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:bg-slate-100 disabled:text-slate-400"
              >
                <option value="">{{ form.module_id ? 'Pilih Sub-Menu (Opsional)' : 'Pilih Modul dahulu...' }}</option>
                <option v-for="mn in formMenuOptions" :key="mn.id" :value="mn.id">📌 {{ mn.name }} ({{ mn.code }})</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Prioritas <span class="text-red-500">*</span></label>
            <select
              v-model="form.priority"
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
            <label class="block text-xs font-bold text-slate-700 mb-1">Tester Bertugas</label>
            <select
              v-model="form.assigned_tester_id"
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="">Belum Ditugaskan</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Hasil yang Diharapkan (Expected Result) <span class="text-red-500">*</span></label>
            <textarea
              v-model="form.expected_result"
              rows="2"
              required
              placeholder="Deskripsikan respon atau output sistem yang diharapkan..."
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Prasyarat (Preconditions)</label>
            <textarea
              v-model="form.preconditions"
              rows="3"
              placeholder="Kondisi awal sebelum pengujian dilakukan..."
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Langkah Pengujian (Test Steps)</label>
            <textarea
              v-model="form.test_steps"
              rows="3"
              placeholder="1. Buka menu X&#10;2. Input data Y&#10;3. Klik tombol Z"
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            ></textarea>
          </div>
        </div>

        <div class="flex justify-end space-x-2.5 pt-4 border-t border-slate-100">
          <button
            type="button"
            @click="showModal = false"
            class="px-4 py-2 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer disabled:opacity-50"
          >
            {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Skenario' : 'Simpan Test Case') }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Detail & History Modal -->
    <Modal
      :show="showDetailModal"
      :title="`Detail Skenario: ${detailData?.test_case_code || ''}`"
      size="xl"
      @close="showDetailModal = false"
    >
      <div v-if="detailData" class="space-y-5 text-xs">
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
          <div class="text-base font-bold text-slate-900 mb-1">{{ detailData.title }}</div>
          <div class="flex flex-wrap gap-2 text-slate-600">
            <span>🏢 {{ detailData.application?.name }}</span>
            <span v-if="detailData.module">/ 🗂️ {{ detailData.module?.name }}</span>
            <Badge :value="detailData.priority" type="priority" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="bg-slate-50/50 p-3 rounded-xl border border-slate-100">
            <span class="font-bold text-slate-700 block mb-1">Prasyarat:</span>
            <div class="whitespace-pre-line text-slate-600 leading-relaxed">{{ detailData.preconditions || '-' }}</div>
          </div>
          <div class="bg-slate-50/50 p-3 rounded-xl border border-slate-100">
            <span class="font-bold text-slate-700 block mb-1">Hasil yang Diharapkan:</span>
            <div class="whitespace-pre-line text-slate-600 leading-relaxed">{{ detailData.expected_result }}</div>
          </div>
        </div>

        <div v-if="detailData.test_steps" class="bg-slate-50/50 p-3 rounded-xl border border-slate-100">
          <span class="font-bold text-slate-700 block mb-1">Langkah Pengujian:</span>
          <div class="whitespace-pre-line text-slate-600 leading-relaxed">{{ detailData.test_steps }}</div>
        </div>

        <!-- Historical Executions -->
        <div>
          <h4 class="font-bold text-slate-900 text-xs mb-2">Riwayat Seluruh Eksekusi Pengujian:</h4>
          <div v-if="!detailData.executions || detailData.executions.length === 0" class="text-slate-400 italic">
            Belum ada catatan riwayat eksekusi.
          </div>
          <div v-else class="space-y-2 max-h-56 overflow-y-auto">
            <div
              v-for="ex in detailData.executions"
              :key="ex.id"
              class="p-3 rounded-xl border border-slate-200 bg-white flex items-start justify-between space-x-3"
            >
              <div>
                <div class="flex items-center space-x-2 mb-1">
                  <Badge :value="ex.status" />
                  <span class="font-bold text-slate-800">{{ ex.tester?.name }}</span>
                  <span class="text-slate-400 text-[10px]">{{ formatDateTime(ex.executed_at) }}</span>
                </div>
                <div v-if="ex.actual_result" class="text-slate-600 text-[11px]">
                  <strong>Hasil:</strong> {{ ex.actual_result }}
                </div>
                <div v-if="ex.notes" class="text-slate-500 text-[10px] mt-0.5">
                  <strong>Catatan:</strong> {{ ex.notes }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Modal>

    <!-- Import Excel Modal -->
    <Modal
      :show="showImportModal"
      title="📤 Upload / Import Excel Test Case"
      subtitle="Impor skenario pengujian secara massal menggunakan format template Excel SISQA"
      size="lg"
      @close="showImportModal = false"
    >
      <form @submit.prevent="submitImportExcel" class="space-y-4 text-xs">
        <div class="p-4 rounded-xl bg-emerald-50/80 border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div class="font-extrabold text-emerald-900">1️⃣ Belum punya format Excel-nya?</div>
            <p class="text-[11px] text-emerald-700 mt-0.5">
              Unduh template resmi agar kolom Aplikasi, Modul, Sub-Menu, Judul, Langkah, dan Expected Result sesuai standar.
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
            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl cursor-pointer disabled:opacity-50"
          >
            {{ importing ? 'Memproses Import...' : '🚀 Proses Import Excel' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Delete Confirmation -->
    <ConfirmDialog
      :show="showDeleteConfirm"
      title="Hapus Test Case"
      :message="`Apakah Anda yakin ingin menghapus test case '${testCaseToDelete?.title}'?`"
      confirmText="Ya, Hapus"
      :isDanger="true"
      @confirm="executeDelete"
      @cancel="showDeleteConfirm = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api';
import { useAuth } from '../auth';
import Badge from '../components/Badge.vue';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import EmptyState from '../components/EmptyState.vue';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const route = useRoute();
const { isAdmin, hasFeatureAccess } = useAuth();

const loading = ref(false);
const saving = ref(false);
const executing = ref(false);
const testCases = ref([]);
const apps = ref([]);
const users = ref([]);
const formModuleOptions = ref([]);
const formMenuOptions = ref([]);

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 15,
});

const filters = reactive({
  search: '',
  application_id: '',
  status: '',
  priority: '',
});

const showModal = ref(false);
const isEdit = ref(false);
const currentId = ref(null);

const form = reactive({
  title: '',
  application_id: '',
  module_id: '',
  menu_id: '',
  priority: 'Medium',
  assigned_tester_id: '',
  expected_result: '',
  preconditions: '',
  test_steps: '',
});

const showExecuteModal = ref(false);
const selectedTestCase = ref(null);
const execForm = reactive({
  status: 'PASS',
  actual_result: '',
  notes: '',
  files: [],
});

const showDetailModal = ref(false);
const detailData = ref(null);
const showDeleteConfirm = ref(false);
const testCaseToDelete = ref(null);

const showImportModal = ref(false);
const importing = ref(false);
const importForm = reactive({
  application_id: '',
  file: null,
});

const downloadExcelTemplate = async () => {
  try {
    const res = await api.get('/test-cases/excel-template', { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([res.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'Template_Import_TestCase_SISQA.xlsx');
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (e) {
    alert('Gagal mengunduh template Excel Test Case.');
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
    const res = await api.post('/test-cases/import-excel', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    alert(res.data?.message || 'Import Excel berhasil.');
    showImportModal.value = false;
    fetchTestCases(1);
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal mengimpor file Excel Test Case.');
  } finally {
    importing.value = false;
  }
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

const fetchTestCases = async (page = 1) => {
  loading.value = true;
  try {
    const res = await api.get('/test-cases', {
      params: { ...filters, page },
    });
    testCases.value = res.data.data;
    pagination.current_page = res.data.current_page;
    pagination.last_page = res.data.last_page;
    pagination.total = res.data.total;
  } catch (e) {
    console.error('Fetch test cases error:', e);
  } finally {
    loading.value = false;
  }
};

let debounceTimeout = null;
const debounceFetch = () => {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    fetchTestCases(1);
  }, 300);
};

const resetFilters = () => {
  filters.search = '';
  filters.application_id = '';
  filters.status = '';
  filters.priority = '';
  fetchTestCases(1);
};

const changePage = (p) => {
  fetchTestCases(p);
};

const handleFormAppChange = async () => {
  form.module_id = '';
  form.menu_id = '';
  formModuleOptions.value = [];
  formMenuOptions.value = [];
  if (form.application_id) {
    const res = await api.get(`/cascade/modules?application_id=${form.application_id}`);
    formModuleOptions.value = res.data;
  }
};

const handleFormModuleChange = async () => {
  form.menu_id = '';
  formMenuOptions.value = [];
  if (form.module_id) {
    const res = await api.get(`/cascade/menus?module_id=${form.module_id}`);
    formMenuOptions.value = res.data;
  }
};

const openCreateModal = async () => {
  isEdit.value = false;
  currentId.value = null;
  form.title = '';
  form.application_id = apps.value[0]?.id || '';
  await handleFormAppChange();
  form.module_id = '';
  form.menu_id = '';
  form.priority = 'Medium';
  form.assigned_tester_id = '';
  form.expected_result = '';
  form.preconditions = '';
  form.test_steps = '';
  showModal.value = true;
};

const openEditModal = async (tc) => {
  isEdit.value = true;
  currentId.value = tc.id;
  form.title = tc.title;
  form.application_id = tc.application_id;
  await handleFormAppChange();
  form.module_id = tc.module_id || '';
  if (form.module_id) {
    await handleFormModuleChange();
  }
  form.menu_id = tc.menu_id || '';
  form.priority = tc.priority;
  form.assigned_tester_id = tc.assigned_tester_id || '';
  form.expected_result = tc.expected_result;
  form.preconditions = tc.preconditions || '';
  form.test_steps = tc.test_steps || '';
  showModal.value = true;
};

const saveTestCase = async () => {
  saving.value = true;
  try {
    if (isEdit.value) {
      await api.put(`/test-cases/${currentId.value}`, form);
    } else {
      await api.post('/test-cases', form);
    }
    showModal.value = false;
    fetchTestCases(pagination.current_page);
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan test case.');
  } finally {
    saving.value = false;
  }
};

const openExecuteModal = (tc) => {
  selectedTestCase.value = tc;
  execForm.status = 'PASS';
  execForm.actual_result = '';
  execForm.notes = '';
  execForm.files = [];
  showExecuteModal.value = true;
};

const handleExecFileChange = (e) => {
  execForm.files = Array.from(e.target.files);
};

const submitExecution = async () => {
  executing.value = true;
  try {
    const fd = new FormData();
    fd.append('status', execForm.status);
    if (execForm.actual_result) fd.append('actual_result', execForm.actual_result);
    if (execForm.notes) fd.append('notes', execForm.notes);
    execForm.files.forEach((file) => {
      fd.append('evidences[]', file);
    });

    await api.post(`/test-cases/${selectedTestCase.value.id}/execute`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    showExecuteModal.value = false;
    fetchTestCases(pagination.current_page);
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan eksekusi pengujian.');
  } finally {
    executing.value = false;
  }
};

const openDetailModal = async (tc) => {
  try {
    const res = await api.get(`/test-cases/${tc.id}`);
    detailData.value = res.data.test_case;
    showDetailModal.value = true;
  } catch (e) {}
};

const confirmDelete = (tc) => {
  testCaseToDelete.value = tc;
  showDeleteConfirm.value = true;
};

const executeDelete = async () => {
  if (!testCaseToDelete.value) return;
  try {
    await api.delete(`/test-cases/${testCaseToDelete.value.id}`);
    showDeleteConfirm.value = false;
    fetchTestCases(pagination.current_page);
  } catch (e) {
    alert('Gagal menghapus test case.');
  }
};

onMounted(() => {
  if (route.query.application_id) {
    filters.application_id = Number(route.query.application_id) || route.query.application_id;
  }
  fetchLookups();
  fetchTestCases();
});
</script>

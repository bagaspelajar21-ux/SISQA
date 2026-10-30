<template>
  <div class="space-y-6">
    <!-- Modern Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-900 p-6 text-white shadow-lg border border-slate-800">
      <OfficeSkylineBg />
      <div class="absolute -right-12 -top-12 w-56 h-56 rounded-full bg-blue-500/20 blur-2xl pointer-events-none"></div>
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-[10px] font-extrabold uppercase tracking-wider mb-2">
            <span>📅 QA Planning &amp; Timeline</span>
          </div>
          <h2 class="text-lg font-black tracking-tight text-white">Manajemen Jadwal &amp; Kalender Pengujian QA</h2>
          <p class="text-xs text-slate-300 mt-0.5">Kelola agenda pengujian, alokasi tester, deteksi jadwal bentrok otomatis, dan pantau progres eksekusi aktual.</p>
        </div>

        <div class="flex items-center space-x-2.5 shrink-0">
          <!-- View Toggle -->
          <div class="bg-slate-950/60 p-1 rounded-xl border border-white/10 flex items-center space-x-1 text-xs font-bold">
            <button
              @click="activeView = 'list'"
              :class="['px-3 py-1.5 rounded-lg transition-all cursor-pointer', activeView === 'list' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:text-white']"
            >
              📋 Daftar
            </button>
            <button
              @click="activeView = 'calendar'"
              :class="['px-3 py-1.5 rounded-lg transition-all cursor-pointer', activeView === 'calendar' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-300 hover:text-white']"
            >
              📅 Kalender
            </button>
          </div>

          <button
            v-if="hasFeatureAccess('create_schedule')"
            @click="openCreateModal"
            class="px-4 py-2 rounded-xl bg-blue-500 hover:bg-blue-400 text-xs font-bold text-white shadow-md shadow-blue-500/25 transition-all flex items-center space-x-1.5 cursor-pointer"
          >
            <span>+ Tambah Jadwal</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Schedule Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div>
          <div class="text-[11px] font-bold text-slate-400 uppercase">Total Jadwal</div>
          <div class="text-2xl font-black text-slate-900 mt-0.5">{{ schedules.length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">📅</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-blue-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-blue-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-blue-700 uppercase">Sedang Berjalan</div>
          <div class="text-2xl font-black text-blue-700 mt-0.5">{{ schedules.filter(s => s.status === 'In Progress' || s.status === 'Scheduled').length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-lg">⚡</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-emerald-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-emerald-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-emerald-700 uppercase">Selesai (Completed)</div>
          <div class="text-2xl font-black text-emerald-700 mt-0.5">{{ schedules.filter(s => s.status === 'Completed').length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg">✅</div>
      </div>
      <div class="bg-white rounded-2xl p-4 border border-red-200/80 shadow-xs flex items-center justify-between bg-gradient-to-b from-red-50/30 to-white">
        <div>
          <div class="text-[11px] font-bold text-red-700 uppercase">Terlambat (Overdue)</div>
          <div class="text-2xl font-black text-red-700 mt-0.5">{{ schedules.filter(s => s.status === 'Overdue').length }}</div>
        </div>
        <div class="w-10 h-10 rounded-xl bg-red-100 text-red-700 flex items-center justify-center text-lg">⏰</div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
      <!-- Filters -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Cari Jadwal</label>
          <input
            v-model="filters.search"
            @input="debounceFetch"
            type="text"
            placeholder="Cari kode atau judul..."
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Aplikasi</label>
          <select
            v-model="filters.application_id"
            @change="fetchSchedules"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Aplikasi</option>
            <option v-for="app in apps" :key="app.id" :value="app.id">{{ app.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Status</label>
          <select
            v-model="filters.status"
            @change="fetchSchedules"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Status</option>
            <option value="Scheduled">Scheduled</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
            <option value="Overdue">Overdue</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Tester</label>
          <select
            v-model="filters.tester_id"
            @change="fetchSchedules"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          >
            <option value="">Semua Tester</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
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
    <LoadingSpinner v-if="loading && schedules.length === 0" text="Memuat jadwal pengujian..." />

    <!-- List View -->
    <div v-else-if="activeView === 'list'" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div v-if="schedules.length === 0" class="p-8">
        <EmptyState
          title="Tidak Ada Jadwal QA"
          description="Belum ada jadwal yang cocok dengan filter yang dipilih."
        >
          <template #action v-if="isAdmin">
            <button
              @click="openCreateModal"
              class="px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition-colors cursor-pointer"
            >
              Buat Jadwal Pertama
            </button>
          </template>
        </EmptyState>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/75 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
              <th class="py-3 px-4">Jadwal & Kode</th>
              <th class="py-3 px-4">Aplikasi & Modul</th>
              <th class="py-3 px-4">Jenis & Prioritas</th>
              <th class="py-3 px-4">Tester</th>
              <th class="py-3 px-4">Waktu Pelaksanaan</th>
              <th class="py-3 px-4">Progres Eksekusi</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <tr v-for="sch in schedules" :key="sch.id" class="hover:bg-slate-50/80 transition-colors">
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-900">{{ sch.title }}</div>
                <div class="text-[10px] font-mono text-blue-600">{{ sch.schedule_code }}</div>
              </td>
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-800">{{ sch.application?.name || '-' }}</div>
                <div class="flex flex-wrap items-center gap-1 mt-1">
                  <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200/70 text-[10px] font-semibold">
                    🗂️ {{ sch.module?.name || 'Seluruh Modul' }}
                  </span>
                  <span v-if="sch.menu?.name" class="px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 border border-sky-200/70 text-[10px] font-semibold">
                    📌 {{ sch.menu.name }}
                  </span>
                </div>
              </td>
              <td class="py-3.5 px-4">
                <div class="font-semibold text-slate-700">{{ sch.testing_type }}</div>
                <div class="mt-0.5">
                  <Badge :value="sch.priority" type="priority" :dot="false" />
                </div>
              </td>
              <td class="py-3.5 px-4">
                <div v-if="sch.tester" class="flex items-center space-x-1.5">
                  <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-[10px]">
                    {{ sch.tester.name[0] }}
                  </span>
                  <span class="font-medium text-slate-800">{{ sch.tester.name }}</span>
                </div>
                <span v-else class="text-slate-400 italic">Belum Ditugaskan</span>
              </td>
              <td class="py-3.5 px-4 text-[11px]">
                <div class="font-bold text-slate-800">{{ formatDateTime(sch.start_time) }}</div>
                <div class="text-slate-500">s/d {{ formatDateTime(sch.end_time) }}</div>
                <div v-if="isScheduleExpired(sch)" class="mt-1 inline-flex items-center space-x-1 px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold">
                  <span>⏰ Lewat Hari / Jam</span>
                </div>
              </td>
              <td class="py-3.5 px-4">
                <div class="w-36">
                  <div class="flex justify-between text-[10px] font-bold mb-1">
                    <span class="text-slate-600">{{ sch.progress_info?.completed }} / {{ sch.progress_info?.target }} Selesai</span>
                    <span class="text-blue-600">{{ sch.progress_info?.percentage }}%</span>
                  </div>
                  <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div
                      class="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
                      :style="{ width: `${sch.progress_info?.percentage || 0}%` }"
                    ></div>
                  </div>
                </div>
              </td>
              <td class="py-3.5 px-4">
                <Badge :value="isScheduleExpired(sch) && sch.status !== 'Completed' && sch.status !== 'Cancelled' ? 'Overdue' : sch.status" />
              </td>
              <td class="py-3.5 px-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    v-if="!isScheduleExpired(sch) && sch.status !== 'Completed' && sch.status !== 'Cancelled' && hasFeatureAccess('execute_test')"
                    @click="continueQaFromSchedule(sch)"
                    class="px-2.5 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-[11px] font-bold transition-colors cursor-pointer"
                    title="Lanjutkan eksekusi QA sesuai jadwal ini"
                  >
                    ▶ Lanjutkan QA
                  </button>
                  <span
                    v-else-if="isScheduleExpired(sch) && sch.status !== 'Completed' && sch.status !== 'Cancelled'"
                    class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-400 border border-slate-200 text-[11px] font-bold cursor-not-allowed select-none"
                    title="Jadwal pengujian sudah melewati batas hari dan jam selesai. Tidak dapat melanjutkan QA."
                  >
                    🔒 Terkunci (Lewat Waktu)
                  </span>
                  <button
                    @click="openDetailModal(sch)"
                    class="px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-[11px] font-semibold transition-colors cursor-pointer"
                    title="Lihat Detail Jadwal"
                  >
                    Detail
                  </button>
                  <button
                    v-if="isAdmin"
                    @click="openEditModal(sch)"
                    class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[11px] font-semibold transition-colors cursor-pointer"
                    title="Edit Jadwal"
                  >
                    Edit
                  </button>
                  <button
                    v-if="isAdmin"
                    @click="confirmDelete(sch)"
                    class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-[11px] font-semibold transition-colors cursor-pointer"
                    title="Hapus Jadwal"
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
          <div>Menampilkan {{ schedules.length }} dari {{ pagination.total }} jadwal</div>
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

    <!-- Calendar View -->
    <div v-else-if="activeView === 'calendar'" class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <h3 class="text-sm font-bold text-slate-900">Agenda Jadwal Pengujian</h3>
        <span class="text-xs text-slate-500">Menampilkan seluruh agenda pengujian aktif</span>
      </div>

      <!-- Calendar Event Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="evt in calendarEvents"
          :key="evt.id"
          class="p-4 rounded-xl border border-slate-200 hover:border-blue-400 transition-all bg-slate-50/50 flex flex-col justify-between"
        >
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[10px] font-mono font-bold text-blue-600">{{ evt.extendedProps?.schedule_code }}</span>
              <Badge :value="evt.status" />
            </div>
            <h4 class="text-xs font-bold text-slate-900 mb-1 line-clamp-1">{{ evt.title }}</h4>
            <div class="text-[11px] text-slate-600 mb-3">
              <div>🏢 {{ evt.application }}</div>
              <div>👤 Tester: {{ evt.tester }}</div>
              <div>⏱️ {{ formatDateTime(evt.start) }} - {{ formatDateTime(evt.end) }}</div>
            </div>
          </div>
          <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-[11px]">
            <span class="font-semibold text-slate-600">Progres: {{ evt.extendedProps?.progress?.completed || 0 }} / {{ evt.extendedProps?.progress?.target || 0 }}</span>
            <span class="font-bold text-blue-600">{{ evt.extendedProps?.progress?.percentage || 0 }}%</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <Modal
      :show="showModal"
      :title="isEdit ? 'Ubah Jadwal QA' : 'Tambah Jadwal QA Baru'"
      subtitle="Isi rincian agenda pengujian dan alokasikan tester"
      size="lg"
      @close="closeModal"
    >
      <form @submit.prevent="saveSchedule" class="space-y-4">
        <!-- Conflict Warning Alert -->
        <div v-if="conflictWarning" class="p-3 bg-amber-50 border border-amber-300 rounded-xl text-amber-800 text-xs">
          <div class="font-bold flex items-center space-x-1.5 mb-1">
            <span>⚠️</span>
            <span>Peringatan Jadwal Bentrok Terdeteksi!</span>
          </div>
          <p>Tester yang dipilih telah memiliki agenda aktif lain pada rentang waktu ini:</p>
          <ul class="list-disc list-inside mt-1 font-mono text-[11px]">
            <li v-for="c in conflictList" :key="c.id">[{{ c.schedule_code }}] {{ c.title }}</li>
          </ul>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Judul Jadwal <span class="text-red-500">*</span></label>
            <input
              v-model="form.title"
              type="text"
              required
              placeholder="Contoh: Pengujian Siklus Rilis Patch 3.4.2"
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
                <option value="">Seluruh Modul</option>
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
                <option value="">{{ form.module_id ? 'Seluruh Sub-Menu' : 'Pilih Modul dahulu...' }}</option>
                <option v-for="mn in formMenuOptions" :key="mn.id" :value="mn.id">📌 {{ mn.name }} ({{ mn.code }})</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Pengujian <span class="text-red-500">*</span></label>
            <select
              v-model="form.testing_type"
              required
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="Daily">Daily</option>
              <option value="Weekly">Weekly</option>
              <option value="Regression">Regression</option>
              <option value="Retesting">Retesting</option>
              <option value="Release">Release</option>
              <option value="Smoke Testing">Smoke Testing</option>
            </select>
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
            <label class="block text-xs font-bold text-slate-700 mb-1">Tester Penanggung Jawab</label>
            <select
              v-model="form.tester_id"
              @change="checkTesterConflict"
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="">Belum Ditugaskan</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Status Jadwal <span class="text-red-500">*</span></label>
            <select
              v-model="form.status"
              required
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
              <option value="Scheduled">Scheduled</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
              <option value="Overdue">Overdue</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Waktu Mulai <span class="text-red-500">*</span></label>
            <input
              v-model="form.start_time"
              type="datetime-local"
              @change="checkTesterConflict"
              required
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Waktu Selesai <span class="text-red-500">*</span></label>
            <input
              v-model="form.end_time"
              type="datetime-local"
              @change="checkTesterConflict"
              required
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
            <textarea
              v-model="form.notes"
              rows="3"
              placeholder="Instruksi khusus pengujian..."
              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
            ></textarea>
          </div>
        </div>

        <div class="flex justify-end space-x-2.5 pt-4 border-t border-slate-100">
          <button
            type="button"
            @click="closeModal"
            class="px-4 py-2 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs transition-colors disabled:opacity-50 cursor-pointer"
          >
            {{ saving ? 'Menyimpan...' : (isEdit ? 'Perbarui Jadwal' : 'Simpan Jadwal') }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Detail Drawer / Modal -->
    <Modal
      :show="showDetail"
      :title="`Detail Jadwal: ${detailData?.schedule_code || ''}`"
      size="lg"
      @close="showDetail = false"
    >
      <div v-if="detailData" class="space-y-5 text-xs">
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2">
          <div class="text-base font-bold text-slate-900">{{ detailData.title }}</div>
          <div class="flex flex-wrap gap-2">
            <Badge :value="detailData.status" />
            <Badge :value="detailData.priority" type="priority" />
            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded font-bold">{{ detailData.testing_type }}</span>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-slate-500 block">Aplikasi & Modul:</span>
            <span class="font-bold text-slate-800">{{ detailData.application?.name }} / {{ detailData.module?.name || 'Semua Modul' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block">Tester Bertugas:</span>
            <span class="font-bold text-slate-800">{{ detailData.tester?.name || 'Belum Ditugaskan' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block">Mulai:</span>
            <span class="font-bold text-slate-800">{{ formatDateTime(detailData.start_time) }}</span>
          </div>
          <div>
            <span class="text-slate-500 block">Selesai:</span>
            <span class="font-bold text-slate-800">{{ formatDateTime(detailData.end_time) }}</span>
          </div>
        </div>

        <!-- Progress Box -->
        <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
          <div class="font-bold text-blue-900 mb-2">Progres Eksekusi Aktual:</div>
          <div class="grid grid-cols-3 gap-2 text-center mb-3">
            <div class="bg-white p-2 rounded-lg border border-blue-200">
              <div class="text-[10px] text-slate-500">Target</div>
              <div class="font-bold text-slate-800">{{ detailData.progress_info?.target || 0 }}</div>
            </div>
            <div class="bg-white p-2 rounded-lg border border-blue-200">
              <div class="text-[10px] text-slate-500">Selesai</div>
              <div class="font-bold text-emerald-600">{{ detailData.progress_info?.completed || 0 }}</div>
            </div>
            <div class="bg-white p-2 rounded-lg border border-blue-200">
              <div class="text-[10px] text-slate-500">Tersisa</div>
              <div class="font-bold text-amber-600">{{ detailData.progress_info?.remaining || 0 }}</div>
            </div>
          </div>
          <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
            <div
              class="bg-blue-600 h-2 rounded-full"
              :style="{ width: `${detailData.progress_info?.percentage || 0}%` }"
            ></div>
          </div>
        </div>

        <div v-if="detailData.notes">
          <span class="text-slate-500 block mb-1">Catatan:</span>
          <div class="bg-slate-50 p-3 rounded-lg text-slate-700 leading-relaxed">{{ detailData.notes }}</div>
        </div>

        <!-- Expired / Continue QA Banner -->
        <div
          v-if="isScheduleExpired(detailData) && detailData.status !== 'Completed' && detailData.status !== 'Cancelled'"
          class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start space-x-3"
        >
          <span class="text-xl">🔒</span>
          <div>
            <div class="font-extrabold text-rose-900">Batas Hari &amp; Jam Pengujian Telah Terlewat</div>
            <p class="mt-0.5 text-[11px] leading-relaxed">
              Jadwal ini memiliki batas akhir pada <strong>{{ formatDateTime(detailData.end_time) }}</strong>. Karena sudah melewati hari/jam yang ditentukan, eksekusi QA pada jadwal ini otomatis dikunci dan tidak dapat dilanjutkan kembali kecuali Admin memperbarui jadwal.
            </p>
          </div>
        </div>
        <div
          v-else-if="detailData.status !== 'Completed' && detailData.status !== 'Cancelled' && hasFeatureAccess('execute_test')"
          class="pt-2 flex justify-end"
        >
          <button
            @click="continueQaFromSchedule(detailData)"
            class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-md shadow-emerald-600/20 transition-all cursor-pointer"
          >
            ▶ Lanjutkan Pengujian QA Sekarang
          </button>
        </div>
      </div>
    </Modal>

    <!-- Delete Confirmation -->
    <ConfirmDialog
      :show="showDeleteConfirm"
      title="Hapus Jadwal QA"
      :message="`Apakah Anda yakin ingin menghapus jadwal '${scheduleToDelete?.title}'? Data pengujian terkait tidak akan hilang.`"
      confirmText="Ya, Hapus"
      :isDanger="true"
      @confirm="executeDelete"
      @cancel="showDeleteConfirm = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';
import { useAuth } from '../auth';
import Badge from '../components/Badge.vue';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import EmptyState from '../components/EmptyState.vue';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const router = useRouter();
const { isAdmin, hasFeatureAccess } = useAuth();

const isScheduleExpired = (sch) => {
  if (!sch) return false;
  if (sch.is_expired) return true;
  if (!sch.end_time) return false;
  return new Date(sch.end_time).getTime() < Date.now();
};

const continueQaFromSchedule = (sch) => {
  if (isScheduleExpired(sch)) {
    alert('Jadwal QA ini sudah melewati batas hari dan jam yang ditentukan sehingga tidak dapat dilanjutkan.');
    return;
  }
  showDetail.value = false;
  router.push({
    path: '/test-cases',
    query: {
      application_id: sch.application_id || '',
    },
  });
};

const activeView = ref('list');
const loading = ref(false);
const saving = ref(false);
const schedules = ref([]);
const calendarEvents = ref([]);
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
  tester_id: '',
});

const showModal = ref(false);
const isEdit = ref(false);
const currentId = ref(null);
const conflictWarning = ref(false);
const conflictList = ref([]);

const form = reactive({
  title: '',
  application_id: '',
  module_id: '',
  menu_id: '',
  testing_type: 'Daily',
  priority: 'Medium',
  tester_id: '',
  status: 'Scheduled',
  start_time: '',
  end_time: '',
  notes: '',
});

const showDetail = ref(false);
const detailData = ref(null);
const showDeleteConfirm = ref(false);
const scheduleToDelete = ref(null);

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

const fetchSchedules = async (page = 1) => {
  loading.value = true;
  try {
    const res = await api.get('/schedules', {
      params: { ...filters, page },
    });
    schedules.value = res.data.data;
    pagination.current_page = res.data.current_page;
    pagination.last_page = res.data.last_page;
    pagination.total = res.data.total;
  } catch (e) {
    console.error('Fetch schedules error:', e);
  } finally {
    loading.value = false;
  }
};

const fetchCalendar = async () => {
  try {
    const res = await api.get('/schedules/calendar');
    calendarEvents.value = res.data;
  } catch (e) {}
};

let debounceTimeout = null;
const debounceFetch = () => {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    fetchSchedules(1);
  }, 300);
};

const resetFilters = () => {
  filters.search = '';
  filters.application_id = '';
  filters.status = '';
  filters.tester_id = '';
  fetchSchedules(1);
};

const changePage = (p) => {
  fetchSchedules(p);
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

const checkTesterConflict = async () => {
  if (!form.tester_id || !form.start_time || !form.end_time) {
    conflictWarning.value = false;
    conflictList.value = [];
    return;
  }
  try {
    const res = await api.get('/schedules/check-conflict', {
      params: {
        tester_id: form.tester_id,
        start_time: form.start_time,
        end_time: form.end_time,
        exclude_id: currentId.value,
      },
    });
    conflictWarning.value = res.data.has_conflict;
    conflictList.value = res.data.conflicts || [];
  } catch (e) {}
};

const openCreateModal = async () => {
  isEdit.value = false;
  currentId.value = null;
  conflictWarning.value = false;
  form.title = '';
  form.application_id = apps.value[0]?.id || '';
  await handleFormAppChange();
  form.module_id = '';
  form.menu_id = '';
  form.testing_type = 'Daily';
  form.priority = 'Medium';
  form.tester_id = '';
  form.status = 'Scheduled';
  form.start_time = '';
  form.end_time = '';
  form.notes = '';
  showModal.value = true;
};

const openEditModal = async (sch) => {
  isEdit.value = true;
  currentId.value = sch.id;
  conflictWarning.value = false;
  form.title = sch.title;
  form.application_id = sch.application_id;
  await handleFormAppChange();
  form.module_id = sch.module_id || '';
  if (form.module_id) {
    await handleFormModuleChange();
  }
  form.menu_id = sch.menu_id || '';
  form.testing_type = sch.testing_type;
  form.priority = sch.priority;
  form.tester_id = sch.tester_id || '';
  form.status = sch.status;
  form.start_time = sch.start_time?.slice(0, 16);
  form.end_time = sch.end_time?.slice(0, 16);
  form.notes = sch.notes || '';
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const saveSchedule = async () => {
  saving.value = true;
  try {
    if (isEdit.value) {
      await api.put(`/schedules/${currentId.value}`, form);
    } else {
      await api.post('/schedules', form);
    }
    showModal.value = false;
    fetchSchedules(pagination.current_page);
    fetchCalendar();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan jadwal.');
  } finally {
    saving.value = false;
  }
};

const openDetailModal = (sch) => {
  detailData.value = sch;
  showDetail.value = true;
};

const confirmDelete = (sch) => {
  scheduleToDelete.value = sch;
  showDeleteConfirm.value = true;
};

const executeDelete = async () => {
  if (!scheduleToDelete.value) return;
  try {
    await api.delete(`/schedules/${scheduleToDelete.value.id}`);
    showDeleteConfirm.value = false;
    fetchSchedules(pagination.current_page);
    fetchCalendar();
  } catch (e) {
    alert('Gagal menghapus jadwal.');
  }
};

onMounted(() => {
  fetchLookups();
  fetchSchedules();
  fetchCalendar();
});
</script>

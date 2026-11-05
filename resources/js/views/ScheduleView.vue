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
            v-if="hasFeatureAccess('add_schedule')"
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
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-3">
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
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal Mulai</label>
          <input
            v-model="filters.start_date"
            :max="filters.end_date || undefined"
            type="date"
            @change="fetchSchedules(1); fetchCalendar()"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal Selesai</label>
          <input
            v-model="filters.end_date"
            :min="filters.start_date || undefined"
            type="date"
            @change="fetchSchedules(1); fetchCalendar()"
            class="w-full text-xs py-2 px-3 rounded-lg border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
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
          <template #action v-if="hasFeatureAccess('add_schedule')">
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
                  <span
                    v-if="sch.menu_scope === 'all'"
                    class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200/70 text-[10px] font-semibold"
                  >
                    🌐 Semua Submenu ({{ sch.menus_count || 0 }})
                  </span>
                  <span
                    v-else-if="(sch.menus_count || 0) > 0"
                    class="px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 border border-sky-200/70 text-[10px] font-semibold"
                  >
                    📌 {{ sch.menus_count }} Submenu
                  </span>
                  <span v-else-if="sch.menu?.name" class="px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 border border-sky-200/70 text-[10px] font-semibold">
                    📌 {{ sch.menu.name }}
                  </span>
                  <span class="px-2 py-0.5 rounded-md bg-slate-50 text-slate-600 border border-slate-200/70 text-[10px] font-semibold">
                    🧪 {{ sch.test_cases_count || 0 }} TC
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
                <div v-if="scheduleTesterNames(sch).length > 0" class="space-y-1">
                  <div v-for="name in scheduleTesterNames(sch).slice(0, 2)" :key="name" class="flex items-center space-x-1.5">
                    <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 font-bold flex items-center justify-center text-[10px] shrink-0">
                      {{ name[0] }}
                    </span>
                    <span class="font-medium text-slate-800 truncate">{{ name }}</span>
                  </div>
                  <div v-if="scheduleTesterNames(sch).length > 2" class="text-[10px] text-slate-400 font-semibold pl-6">
                    +{{ scheduleTesterNames(sch).length - 2 }} tester lainnya
                  </div>
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
                    v-if="hasFeatureAccess('update_schedule')"
                    @click="openEditModal(sch)"
                    class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-[11px] font-semibold transition-colors cursor-pointer"
                    title="Edit Jadwal"
                  >
                    Edit
                  </button>
                  <button
                    v-if="hasFeatureAccess('delete_schedule')"
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
              <p v-if="apps.length === 0" class="mt-1 text-[10px] text-amber-700">
                Belum ada aplikasi aktif. Periksa Master Data Aplikasi.
              </p>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">🗂️ Modul <span class="text-red-500">*</span></label>
              <select
                v-model="form.module_id"
                @change="handleFormModuleChange"
                required
                class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
              >
                <option value="" disabled>Pilih Modul</option>
                <option v-for="mod in formModuleOptions" :key="mod.id" :value="mod.id">{{ mod.name }}</option>
              </select>
              <p v-if="form.application_id && !formModulesLoading && formModuleOptions.length === 0" class="mt-1 text-[10px] text-amber-700">
                Belum ada modul aktif untuk aplikasi ini. Periksa Master Data Modul.
              </p>
            </div>
          </div>

          <!-- Cakupan Submenu Terpusat -->
          <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50/70 p-3.5 space-y-3">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-slate-700">📌 Cakupan Submenu <span class="text-red-500">*</span></label>
              <span class="text-[10px] font-bold text-blue-600">
                {{ form.menu_scope === 'all' ? `${formMenuOptions.length} submenu` : `${form.menu_ids.length} dipilih` }}
              </span>
            </div>
            <div class="flex items-center space-x-4 text-xs font-semibold text-slate-700">
              <label class="flex items-center space-x-1.5 cursor-pointer">
                <input v-model="form.menu_scope" type="radio" value="all" class="text-blue-600" />
                <span>Semua Submenu</span>
              </label>
              <label class="flex items-center space-x-1.5 cursor-pointer">
                <input v-model="form.menu_scope" type="radio" value="selected" class="text-blue-600" />
                <span>Submenu Tertentu</span>
              </label>
            </div>

            <div v-if="form.menu_scope === 'selected'" class="rounded-lg border border-slate-200 bg-white">
              <div class="flex items-center justify-between px-3 py-2 border-b border-slate-100">
                <span class="text-[10px] font-bold text-slate-500 uppercase">Pilih Submenu ({{ form.menu_ids.length }})</span>
                <div class="flex space-x-2">
                  <button type="button" @click="selectAllMenus" class="text-[10px] font-bold text-blue-600 hover:underline cursor-pointer">Pilih Semua</button>
                  <button type="button" @click="form.menu_ids = []" class="text-[10px] font-bold text-slate-500 hover:underline cursor-pointer">Bersihkan</button>
                </div>
              </div>
              <div class="max-h-36 overflow-y-auto p-2 grid grid-cols-1 sm:grid-cols-2 gap-1">
                <label
                  v-for="mn in formMenuOptions"
                  :key="mn.id"
                  class="flex items-center space-x-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer text-[11px] text-slate-700"
                >
                  <input v-model="form.menu_ids" type="checkbox" :value="mn.id" class="rounded text-blue-600" />
                  <span class="truncate">{{ mn.name }}</span>
                </label>
                <p v-if="form.module_id && !formMenusLoading && formMenuOptions.length === 0" class="col-span-full px-2 py-2 text-[10px] text-amber-700">
                  Belum ada sub-menu aktif. Tambahkan atau aktifkan sub-menu melalui Master Data.
                </p>
                <p v-if="!form.module_id" class="col-span-full px-2 py-2 text-[10px] text-slate-400">
                  Pilih modul dahulu untuk melihat daftar submenu.
                </p>
              </div>
            </div>
            <p v-else class="text-[10px] text-slate-500">
              Semua submenu aktif pada modul ini akan dicakup jadwal. Perubahan master submenu setelah jadwal dibuat tidak mengubah cakupan yang tersimpan.
            </p>
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

          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-slate-700 mb-1">Tester yang Ditugaskan <span class="text-red-500">*</span></label>
            <div class="rounded-lg border border-slate-300 bg-white">
              <div class="flex items-center justify-between px-3 py-1.5 border-b border-slate-100">
                <span class="text-[10px] font-bold text-slate-500 uppercase">{{ form.tester_ids.length }} tester dipilih</span>
                <div class="flex space-x-2">
                  <button type="button" @click="form.tester_ids = users.map(u => u.id)" class="text-[10px] font-bold text-blue-600 hover:underline cursor-pointer">Pilih Semua</button>
                  <button type="button" @click="form.tester_ids = []" class="text-[10px] font-bold text-slate-500 hover:underline cursor-pointer">Bersihkan</button>
                </div>
              </div>
              <div class="max-h-32 overflow-y-auto p-2 grid grid-cols-1 sm:grid-cols-2 gap-1">
                <label
                  v-for="u in users"
                  :key="u.id"
                  class="flex items-center space-x-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer text-[11px] text-slate-700"
                >
                  <input v-model="form.tester_ids" type="checkbox" :value="u.id" @change="checkTesterConflict" class="rounded text-blue-600" />
                  <span class="truncate">{{ u.name }}</span>
                </label>
              </div>
            </div>
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
      <LoadingSpinner v-if="detailLoading" text="Memuat detail jadwal..." />
      <div v-else-if="detailData" class="space-y-5 text-xs">
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2">
          <div class="text-base font-bold text-slate-900">{{ detailData.title }}</div>
          <div class="flex flex-wrap gap-2">
            <Badge :value="detailData.status" />
            <Badge :value="detailData.priority" type="priority" />
            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded font-bold">{{ detailData.testing_type }}</span>
            <span class="bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded font-bold">
              {{ detailData.menu_scope === 'all' ? 'Semua Submenu' : `${detailData.menus_coverage?.length || 0} Submenu Terpilih` }}
            </span>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-slate-500 block">Aplikasi & Modul:</span>
            <span class="font-bold text-slate-800">{{ detailData.application?.name }} / {{ detailData.module?.name || 'Semua Modul' }}</span>
          </div>
          <div>
            <span class="text-slate-500 block">Tester Bertugas:</span>
            <span class="font-bold text-slate-800">
              {{ scheduleTesterNames(detailData).length > 0 ? scheduleTesterNames(detailData).join(', ') : 'Belum Ditugaskan' }}
            </span>
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

        <!-- Cakupan Submenu & Progres per Submenu -->
        <div v-if="detailData.menus_coverage?.length" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
          <div class="px-3 py-2 bg-slate-50 border-b border-slate-100 font-bold text-slate-700">
            Cakupan Submenu ({{ detailData.menus_coverage.length }})
          </div>
          <div class="overflow-x-auto max-h-64 overflow-y-auto">
            <table class="w-full text-left">
              <thead class="sticky top-0 bg-slate-50 text-[10px] font-bold text-slate-500 uppercase">
                <tr>
                  <th class="py-2 px-3">Submenu</th>
                  <th class="py-2 px-3 text-center">Total TC</th>
                  <th class="py-2 px-3 text-center text-emerald-600">PASS</th>
                  <th class="py-2 px-3 text-center text-red-600">FAIL</th>
                  <th class="py-2 px-3 text-center text-amber-600">PEND</th>
                  <th class="py-2 px-3 text-center text-slate-500">NT</th>
                  <th class="py-2 px-3 text-center">Belum</th>
                  <th class="py-2 px-3 text-right">Progres</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="m in detailData.menus_coverage" :key="m.menu_id">
                  <td class="py-2 px-3 font-semibold text-slate-800">{{ m.name }}</td>
                  <td class="py-2 px-3 text-center font-bold">{{ m.total }}</td>
                  <td class="py-2 px-3 text-center text-emerald-700 font-bold">{{ m.pass || 0 }}</td>
                  <td class="py-2 px-3 text-center text-red-700 font-bold">{{ m.failed || 0 }}</td>
                  <td class="py-2 px-3 text-center text-amber-700 font-bold">{{ m.pending || 0 }}</td>
                  <td class="py-2 px-3 text-center text-slate-600 font-bold">{{ m.not_tested || 0 }}</td>
                  <td class="py-2 px-3 text-center text-slate-400 font-bold">{{ m.untested || 0 }}</td>
                  <td class="py-2 px-3 text-right">
                    <span class="font-bold text-blue-600">{{ m.percentage }}%</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Cakupan Test Case (bisa dikurangi admin) -->
        <div v-if="detailTestCases.length > 0" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
          <div class="px-3 py-2 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
            <span class="font-bold text-slate-700">Test Case dalam Jadwal ({{ detailTestCaseIds.length }} / {{ detailTestCases.length }})</span>
            <button
              v-if="hasFeatureAccess('update_schedule')"
              @click="saveScheduleTestCases"
              :disabled="savingTestCases"
              class="px-3 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold disabled:opacity-50 cursor-pointer"
            >
              {{ savingTestCases ? 'Menyimpan...' : 'Simpan Cakupan TC' }}
            </button>
          </div>
          <div class="max-h-52 overflow-y-auto divide-y divide-slate-100">
            <label
              v-for="tc in detailTestCases"
              :key="tc.id"
              class="flex items-center space-x-2.5 px-3 py-2 hover:bg-slate-50 cursor-pointer"
              :class="{ 'opacity-50': !detailTestCaseIds.includes(tc.id) }"
            >
              <input
                type="checkbox"
                :checked="detailTestCaseIds.includes(tc.id)"
                :disabled="!hasFeatureAccess('update_schedule')"
                @change="toggleDetailTestCase(tc.id)"
                class="rounded text-blue-600"
              />
              <span class="font-mono font-bold text-blue-600 text-[11px]">{{ tc.test_case_code }}</span>
              <span class="text-slate-700 truncate">{{ tc.title }}</span>
              <span class="ml-auto shrink-0 text-[10px] text-slate-400">{{ tc.schedule_execution?.status || 'Belum' }}</span>
            </label>
          </div>
        </div>

        <!-- Riwayat Aktivitas -->
        <div v-if="detailActivities.length > 0" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
          <div class="px-3 py-2 bg-slate-50 border-b border-slate-100 font-bold text-slate-700">
            Riwayat Aktivitas ({{ detailActivities.length }})
          </div>
          <div class="max-h-56 overflow-y-auto divide-y divide-slate-100">
            <div v-for="(act, idx) in detailActivities" :key="idx" class="px-3 py-2 flex items-start space-x-2.5">
              <span class="mt-0.5 shrink-0">{{ act.type === 'execution' ? '🧪' : '📋' }}</span>
              <div class="min-w-0 flex-1">
                <div class="text-slate-800 leading-snug">{{ act.description }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">
                  {{ act.user || '-' }} · {{ formatDateTime(act.at) }}
                  <span v-if="act.submenu"> · 📌 {{ act.submenu }}</span>
                </div>
              </div>
              <Badge :value="act.result" />
            </div>
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
          v-else-if="detailData.status !== 'Completed' && detailData.status !== 'Cancelled' && detailData.can_continue_qa && hasFeatureAccess('execute_test')"
          class="pt-2 flex justify-end"
        >
          <button
            @click="continueQaFromSchedule(detailData)"
            class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-md shadow-emerald-600/20 transition-all cursor-pointer"
          >
            ▶ Mulai Eksekusi QA
          </button>
        </div>
      </div>
    </Modal>

    <!-- Panel Eksekusi QA Terpusat -->
    <Modal
      :show="showExecPanel"
      :title="`Eksekusi QA: ${detailData?.title || ''}`"
      :subtitle="detailData?.schedule_code"
      size="xl"
      @close="showExecPanel = false"
    >
      <QaExecutionPanel
        v-if="showExecPanel && execScheduleId"
        :schedule-id="execScheduleId"
        @executed="onExecutionDone"
      />
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
import api from '../api';
import { useAuth } from '../auth';
import { showAlert } from '../dialog';
import Badge from '../components/Badge.vue';
import Modal from '../components/Modal.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import EmptyState from '../components/EmptyState.vue';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';
import QaExecutionPanel from '../components/QaExecutionPanel.vue';

const { hasFeatureAccess } = useAuth();

const isScheduleExpired = (sch) => {
  if (!sch) return false;
  if (sch.is_expired) return true;
  if (!sch.end_time) return false;
  return new Date(sch.end_time).getTime() < Date.now();
};

const continueQaFromSchedule = (sch) => {
  if (isScheduleExpired(sch)) {
    void showAlert('Jadwal QA ini sudah melewati batas hari dan jam yang ditentukan sehingga tidak dapat dilanjutkan.', { title: 'Jadwal QA Terkunci' });
    return;
  }
  if (sch.can_continue_qa === false) {
    void showAlert('Jadwal ini belum dapat dikerjakan (belum dimulai, terkunci, atau Anda belum ditugaskan).', { title: 'Eksekusi Belum Dibuka' });
    return;
  }
  showDetail.value = false;
  openExecutionPanel(sch);
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
const formModulesLoading = ref(false);
const formMenusLoading = ref(false);

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
  start_date: '',
  end_date: '',
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
  menu_scope: 'all',
  menu_ids: [],
  testing_type: 'Daily',
  priority: 'Medium',
  tester_id: '',
  tester_ids: [],
  status: 'Scheduled',
  start_time: '',
  end_time: '',
  notes: '',
});

const showDetail = ref(false);
const detailData = ref(null);
const detailLoading = ref(false);
const detailTestCases = ref([]);
const detailTestCaseIds = ref([]);
const savingTestCases = ref(false);
const detailActivities = ref([]);
const showExecPanel = ref(false);
const execScheduleId = ref(null);
const showDeleteConfirm = ref(false);
const scheduleToDelete = ref(null);

const formatDate = (str) => {
  if (!str) return '-';
  const d = new Date(str);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const scheduleTesterNames = (sch) => {
  const names = (sch.testers || []).map((t) => t.name);
  if (sch.tester && !names.includes(sch.tester.name)) {
    names.push(sch.tester.name);
  }
  return names;
};

const formatDateTime = (str) => {
  if (!str) return '-';
  const d = new Date(str);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const toLocalDateTimeInput = (value) => {
  if (!value) return '';
  const date = new Date(value);
  return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
};

const toUtcIsoString = (value) => {
  return value ? new Date(value).toISOString() : '';
};

const fetchLookups = async () => {
  try {
    const [appRes, userRes] = await Promise.all([
      api.get('/cascade/apps'),
      api.get('/cascade/users'),
    ]);
    apps.value = appRes.data;
    users.value = userRes.data;
  } catch (e) {
    void showAlert(e.response?.data?.message || 'Gagal memuat aplikasi dan tester.', { title: 'Data Jadwal Gagal Dimuat' });
  }
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
    void showAlert(e.response?.data?.message || 'Gagal memuat daftar jadwal.', { title: 'Jadwal Gagal Dimuat' });
  } finally {
    loading.value = false;
  }
};

const fetchCalendar = async () => {
  try {
    const res = await api.get('/schedules/calendar', {
      params: {
        start_date: filters.start_date,
        end_date: filters.end_date,
      },
    });
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
  filters.start_date = '';
  filters.end_date = '';
  fetchSchedules(1);
  fetchCalendar();
};

const changePage = (p) => {
  fetchSchedules(p);
};

const handleFormAppChange = async () => {
  form.module_id = '';
  form.menu_id = '';
  form.menu_scope = 'all';
  form.menu_ids = [];
  formModuleOptions.value = [];
  formMenuOptions.value = [];
  if (!form.application_id) {
    return;
  }

  formModulesLoading.value = true;
  try {
    const res = await api.get('/cascade/modules', {
      params: { application_id: form.application_id },
    });
    formModuleOptions.value = res.data;
  } catch (e) {
    void showAlert(e.response?.data?.message || 'Gagal memuat modul aplikasi.', { title: 'Modul Gagal Dimuat' });
  } finally {
    formModulesLoading.value = false;
  }
};

const handleFormModuleChange = async () => {
  form.menu_id = '';
  form.menu_ids = [];
  formMenuOptions.value = [];
  if (!form.module_id) {
    return;
  }

  formMenusLoading.value = true;
  try {
    const res = await api.get('/cascade/menus', {
      params: {
        application_id: form.application_id,
        module_id: form.module_id,
      },
    });
    formMenuOptions.value = res.data;
  } catch (e) {
    void showAlert(e.response?.data?.message || 'Gagal memuat sub-menu aplikasi.', { title: 'Fitur Gagal Dimuat' });
  } finally {
    formMenusLoading.value = false;
  }
};

const selectAllMenus = () => {
  form.menu_ids = formMenuOptions.value.map((mn) => mn.id);
};

const checkTesterConflict = async () => {
  if (form.tester_ids.length === 0 || !form.start_time || !form.end_time) {
    conflictWarning.value = false;
    conflictList.value = [];
    return;
  }
  try {
    const res = await api.get('/schedules/check-conflict', {
      params: {
        tester_ids: form.tester_ids,
        start_time: toUtcIsoString(form.start_time),
        end_time: toUtcIsoString(form.end_time),
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
  form.menu_scope = 'all';
  form.menu_ids = [];
  form.testing_type = 'Daily';
  form.priority = 'Medium';
  form.tester_id = '';
  form.tester_ids = [];
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
  form.menu_scope = sch.menu_scope === 'all' ? 'all' : 'selected';
  const pivotMenuIds = (sch.menus || []).map((m) => m.id);
  form.menu_ids = pivotMenuIds.length > 0
    ? pivotMenuIds
    : (sch.menu_id ? [Number(sch.menu_id)] : []);
  form.menu_id = form.menu_ids[0] || sch.menu_id || '';
  form.testing_type = sch.testing_type;
  form.priority = sch.priority;
  const pivotTesterIds = (sch.testers || []).map((t) => t.id);
  form.tester_ids = pivotTesterIds.length > 0
    ? pivotTesterIds
    : (sch.tester_id ? [Number(sch.tester_id)] : []);
  form.tester_id = form.tester_ids[0] || sch.tester_id || '';
  form.status = sch.status;
  form.start_time = toLocalDateTimeInput(sch.start_time);
  form.end_time = toLocalDateTimeInput(sch.end_time);
  form.notes = sch.notes || '';
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const saveSchedule = async () => {
  saving.value = true;
  try {
    const scheduleData = {
      ...form,
      menu_id: form.menu_scope === 'selected' ? (form.menu_ids[0] || '') : '',
      tester_id: form.tester_ids[0] || '',
      menu_scope: form.menu_scope,
      menu_ids: form.menu_scope === 'selected' ? form.menu_ids : undefined,
      tester_ids: form.tester_ids,
      start_time: toUtcIsoString(form.start_time),
      end_time: toUtcIsoString(form.end_time),
    };

    if (isEdit.value) {
      await api.put(`/schedules/${currentId.value}`, scheduleData);
    } else {
      await api.post('/schedules', scheduleData);
    }
    showModal.value = false;
    fetchSchedules(pagination.current_page);
    fetchCalendar();
  } catch (e) {
    void showAlert(e.response?.data?.message || 'Gagal menyimpan jadwal.', { title: 'Jadwal Gagal Disimpan' });
  } finally {
    saving.value = false;
  }
};

const fetchDetailExtras = async (scheduleId) => {
  detailTestCases.value = [];
  detailTestCaseIds.value = [];
  detailActivities.value = [];
  try {
    const [tcRes, actRes] = await Promise.all([
      api.get(`/schedules/${scheduleId}/test-cases`),
      api.get(`/schedules/${scheduleId}/activities`),
    ]);
    detailTestCases.value = tcRes.data.test_cases || [];
    detailTestCaseIds.value = detailTestCases.value.map((tc) => tc.id);
    detailActivities.value = actRes.data.activities || [];
  } catch (e) {}
};

const openDetailModal = async (sch) => {
  detailData.value = sch;
  detailLoading.value = true;
  showDetail.value = true;
  fetchDetailExtras(sch.id);
  try {
    const res = await api.get(`/schedules/${sch.id}`);
    detailData.value = res.data.schedule;
  } catch (e) {
    // fallback ke data list bila detail gagal dimuat
  } finally {
    detailLoading.value = false;
  }
};

const toggleDetailTestCase = (tcId) => {
  const idx = detailTestCaseIds.value.indexOf(tcId);
  if (idx === -1) {
    detailTestCaseIds.value.push(tcId);
  } else {
    detailTestCaseIds.value.splice(idx, 1);
  }
};

const saveScheduleTestCases = async () => {
  if (!detailData.value?.id) return;
  savingTestCases.value = true;
  try {
    await api.put(`/schedules/${detailData.value.id}/test-cases`, {
      test_case_ids: detailTestCaseIds.value,
    });
    void showAlert('Cakupan test case jadwal berhasil diperbarui.', { title: 'Cakupan Tersimpan' });
    fetchSchedules(pagination.current_page);
  } catch (e) {
    void showAlert(e.response?.data?.message || 'Gagal menyimpan cakupan test case.', { title: 'Penyimpanan Gagal' });
  } finally {
    savingTestCases.value = false;
  }
};

const openExecutionPanel = (sch) => {
  execScheduleId.value = sch.id;
  showExecPanel.value = true;
};

const onExecutionDone = () => {
  showExecPanel.value = false;
  fetchSchedules(pagination.current_page);
  fetchCalendar();
  if (detailData.value?.id === execScheduleId.value) {
    openDetailModal(detailData.value);
  }
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
    void showAlert('Gagal menghapus jadwal.', { title: 'Jadwal Gagal Dihapus' });
  }
};

onMounted(() => {
  fetchLookups();
  fetchSchedules();
  fetchCalendar();
});
</script>

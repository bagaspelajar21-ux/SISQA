<template>
  <div class="space-y-6">
    <!-- Modern Hero Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-purple-950 p-6 text-white shadow-lg border border-slate-800">
      <OfficeSkylineBg />
      <div class="absolute -right-12 -top-12 w-56 h-56 rounded-full bg-purple-500/20 blur-2xl pointer-events-none"></div>
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-purple-500/20 border border-purple-400/30 text-purple-300 text-[11px] font-extrabold uppercase tracking-wider mb-2">
            <span>🔀 Business Process &amp; Document Preview Hub</span>
          </div>
          <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">Flow &amp; Alur Proses Bisnis Aplikasi</h2>
          <p class="text-xs text-slate-300 mt-1 max-w-2xl">
            Unggah langsung file dokumentasi alur aplikasi (<strong>PPT/PPTX, PDF, Gambar JPG/PNG, Word, Excel</strong>) tanpa perlu input satu per satu, lengkap dengan fitur <strong>Preview Langsung di Dalam Sistem</strong>.
          </p>
        </div>
        <div v-if="isAdmin" class="shrink-0 flex items-center gap-2">
          <button
            @click="openCreateModal"
            class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-xs font-extrabold text-white shadow-lg shadow-blue-500/30 transition-all flex items-center space-x-2 cursor-pointer"
          >
            <span>📤 Upload / Tambah Flow Baru</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Cari Flow / Nama Berkas</label>
          <input
            v-model="filters.search"
            @input="fetchFlows"
            type="text"
            placeholder="Cari kode, judul flow, atau deskripsi..."
            class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Aplikasi</label>
          <select
            v-model="filters.application_id"
            @change="fetchFlows"
            class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Semua Aplikasi</option>
            <option v-for="app in apps" :key="app.id" :value="app.id">{{ app.name }}</option>
          </select>
        </div>
        <div class="flex items-end">
          <button
            @click="resetFilters"
            class="w-full py-2 px-3 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-600 transition-colors cursor-pointer"
          >
            Reset Filter
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <LoadingSpinner v-if="loading && flows.length === 0" text="Memuat daftar flow & berkas aplikasi..." />

    <!-- Flows List -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <div v-if="flows.length === 0" class="lg:col-span-2">
        <EmptyState title="Tidak Ada Flow Aplikasi" description="Belum ada flow aplikasi yang diunggah. Klik tombol Upload / Tambah Flow Baru untuk mengunggah file PPT, JPG, PDF, dll." />
      </div>

      <div
        v-for="flow in flows"
        :key="flow.id"
        class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:border-blue-300 transition-all flex flex-col justify-between"
      >
        <div>
          <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-mono font-extrabold text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-md border border-blue-100">
              {{ flow.flow_code }} • v{{ flow.version }}
            </span>
            <Badge :value="flow.status" />
          </div>

          <h3 class="text-sm font-extrabold text-slate-900 mb-1">{{ flow.title }}</h3>
          <div class="text-[11px] text-slate-500 mb-3">
            🏢 {{ flow.application?.name }}
            <span v-if="flow.module"> / 🗂️ {{ flow.module?.name }}</span>
            <span v-if="flow.menu"> / 📌 {{ flow.menu?.name }}</span>
          </div>

          <p v-if="flow.description || flow.process_goal" class="text-xs text-slate-600 line-clamp-2 mb-3 leading-relaxed">
            {{ flow.description || flow.process_goal }}
          </p>

          <!-- Attached Files Quick Preview Chips -->
          <div class="mb-4">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                📎 Berkas Flow Terlampir ({{ flow.files?.length || flow.files_count || 0 }} File)
              </span>
              <label
                v-if="isAdmin"
                class="text-[11px] font-bold text-blue-600 hover:text-blue-700 cursor-pointer flex items-center gap-1"
                title="Tambah file langsung ke flow ini"
              >
                <span>+ Upload Cepat File</span>
                <input type="file" multiple class="hidden" @change="(e) => quickUploadToCard(e, flow)" />
              </label>
            </div>

            <div v-if="flow.files && flow.files.length > 0" class="flex flex-wrap gap-1.5">
              <button
                v-for="f in flow.files"
                :key="f.id"
                type="button"
                @click="openPreviewModal(flow, f)"
                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 text-xs font-semibold text-slate-700 hover:text-blue-700 transition-all cursor-pointer max-w-full"
                title="Klik untuk Preview Langsung di dalam sistem"
              >
                <span>{{ getFileIcon(f) }}</span>
                <span class="truncate max-w-44">{{ f.original_name }}</span>
                <span class="px-1.5 py-0.2 rounded bg-blue-100 text-blue-700 text-[9px] font-extrabold uppercase shrink-0">
                  Preview
                </span>
              </button>
            </div>
            <div v-else class="p-2.5 rounded-xl bg-slate-50 border border-dashed border-slate-200 text-[11px] text-slate-400 text-center">
              Belum ada file yang diunggah ({{ flow.steps_count || 0 }} langkah diagram manual).
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <span class="text-[10px] text-slate-400 truncate">Oleh {{ flow.creator?.name || 'Admin' }}</span>
          <div class="flex items-center gap-1.5 shrink-0">
            <button
              @click="openPreviewModal(flow)"
              class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold shadow-xs transition-all cursor-pointer flex items-center gap-1"
            >
              <span>👁️ Preview Flow &amp; File</span>
            </button>
            <button
              v-if="isAdmin"
              @click="openEditModal(flow)"
              class="px-2.5 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-semibold cursor-pointer transition-colors"
            >
              Edit
            </button>
            <button
              v-if="isAdmin"
              @click="confirmDelete(flow)"
              class="px-2.5 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-xs font-semibold cursor-pointer transition-colors"
            >
              Hapus
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create / Edit Flow Modal (File-Upload First!) -->
    <Modal
      :show="showFormModal"
      :title="isEdit ? 'Edit & Upload Berkas Flow Aplikasi' : 'Upload / Tambah Flow Aplikasi Baru'"
      subtitle="Cukup pilih Aplikasi dan unggah berkas Flow (PPT, PDF, JPG/PNG, Word, Excel) tanpa perlu ketik manual satu per satu"
      size="xl"
      @close="showFormModal = false"
    >
      <form @submit.prevent="saveFlow" class="space-y-4 text-xs">
        <!-- Primary Upload Box -->
        <div class="p-4 rounded-2xl bg-gradient-to-r from-blue-50/90 via-indigo-50/50 to-purple-50/50 border-2 border-dashed border-blue-300">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <div class="text-xs font-extrabold text-blue-900 flex items-center gap-1.5">
                <span>📂 Upload Berkas Flow Aplikasi (PPT, PPTX, PDF, JPG, PNG, DOCX, XLSX)</span>
              </div>
              <p class="text-[11px] text-slate-600 mt-0.5">
                Anda dapat mengunggah satu atau beberapa file sekaligus. Semua file dapat di-preview langsung di dalam sistem.
              </p>
            </div>
            <label class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs shadow-sm cursor-pointer shrink-0 text-center transition-colors">
              + Pilih File (PPT / JPG / PDF / Dll)
              <input
                type="file"
                multiple
                accept=".ppt,.pptx,.pdf,.jpg,.jpeg,.png,.gif,.webp,.svg,.doc,.docx,.xls,.xlsx,.csv,.mp4,.txt,.json,.drawio"
                class="hidden"
                @change="handleFormFilesSelect"
              />
            </label>
          </div>

          <!-- Selected Pending Files to Upload -->
          <div v-if="pendingFiles.length > 0" class="mt-3 pt-3 border-t border-blue-200/60 space-y-1.5">
            <div class="text-[11px] font-bold text-blue-800">File Baru Siap Diunggah ({{ pendingFiles.length }}):</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div
                v-for="(pf, pIdx) in pendingFiles"
                :key="pIdx"
                class="flex items-center justify-between p-2 rounded-xl bg-white border border-blue-200 shadow-2xs"
              >
                <div class="flex items-center gap-2 truncate pr-2">
                  <span class="text-base">{{ getFileIcon({ original_name: pf.name }) }}</span>
                  <div class="truncate">
                    <div class="font-bold text-slate-800 truncate">{{ pf.name }}</div>
                    <div class="text-[10px] text-slate-400">{{ formatBytes(pf.size) }}</div>
                  </div>
                </div>
                <button
                  type="button"
                  @click="pendingFiles.splice(pIdx, 1)"
                  class="px-2 py-1 rounded-lg text-red-600 hover:bg-red-50 font-bold text-[11px] cursor-pointer"
                >
                  ✕
                </button>
              </div>
            </div>
          </div>

          <!-- Existing Files (when editing) -->
          <div v-if="isEdit && existingFiles.length > 0" class="mt-3 pt-3 border-t border-blue-200/60 space-y-1.5">
            <div class="text-[11px] font-bold text-slate-700">Berkas yang Sudah Terlampir ({{ existingFiles.length }}):</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div
                v-for="ef in existingFiles"
                :key="ef.id"
                class="flex items-center justify-between p-2 rounded-xl bg-white/80 border border-slate-200"
              >
                <div class="flex items-center gap-2 truncate pr-2">
                  <span>{{ getFileIcon(ef) }}</span>
                  <span class="font-semibold text-slate-700 truncate">{{ ef.original_name }}</span>
                </div>
                <button
                  type="button"
                  @click="deleteExistingFile(ef)"
                  class="px-2 py-0.5 rounded bg-red-50 text-red-600 hover:bg-red-100 font-bold text-[10px] cursor-pointer shrink-0"
                >
                  Hapus
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="md:col-span-2">
            <label class="block font-bold text-slate-700 mb-1">Judul Flow / Nama Dokumen <span class="text-red-500">*</span></label>
            <input
              v-model="form.title"
              required
              type="text"
              placeholder="Otomatis terisi dari nama file jika dikosongkan saat pilih file, atau ketik judul..."
              class="w-full py-2 px-3 border border-slate-300 rounded-xl"
            />
          </div>
          <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-3.5 p-3.5 rounded-xl bg-slate-50/90 border border-slate-200/80">
            <div>
              <label class="block font-bold text-slate-700 mb-1">🏢 Aplikasi <span class="text-red-500">*</span></label>
              <select v-model="form.application_id" @change="handleAppChange" required class="w-full py-2 px-3 border border-slate-300 bg-white rounded-xl">
                <option value="" disabled>Pilih Aplikasi</option>
                <option v-for="a in apps" :key="a.id" :value="a.id">{{ a.name }}</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">🗂️ Menu Utama (Modul)</label>
              <select v-model="form.module_id" @change="handleModuleChange" class="w-full py-2 px-3 border border-slate-300 bg-white rounded-xl">
                <option value="">Seluruh Modul / Umum</option>
                <option v-for="m in moduleOptions" :key="m.id" :value="m.id">{{ m.name }}</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">
                📌 Sub-Menu / Fitur
              </label>
              <select
                v-model="form.menu_id"
                :disabled="!form.module_id"
                class="w-full py-2 px-3 border border-slate-300 bg-white rounded-xl disabled:bg-slate-100 disabled:text-slate-400"
              >
                <option value="">{{ form.module_id ? 'Pilih Sub-Menu (Opsional)' : 'Pilih Modul dahulu...' }}</option>
                <option v-for="mn in menuOptions" :key="mn.id" :value="mn.id">📌 {{ mn.name }}</option>
              </select>
            </div>
          </div>
          <div class="md:col-span-2 grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Versi</label>
              <input v-model="form.version" type="text" placeholder="1.0" class="w-full py-2 px-3 border border-slate-300 rounded-xl" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Status <span class="text-red-500">*</span></label>
              <select v-model="form.status" required class="w-full py-2 px-3 border border-slate-300 rounded-xl">
                <option value="Published">Published</option>
                <option value="Draft">Draft</option>
                <option value="Archived">Archived</option>
              </select>
            </div>
          </div>
        </div>

        <div>
          <label class="block font-bold text-slate-700 mb-1">Keterangan / Deskripsi Singkat (Opsional)</label>
          <textarea
            v-model="form.description"
            rows="2"
            placeholder="Catatan atau penjelasan singkat mengenai flow aplikasi ini..."
            class="w-full py-2 px-3 border border-slate-300 rounded-xl"
          ></textarea>
        </div>

        <!-- Optional Manual Steps Accordion (Collapsed by default so Admin doesn't have to input 1 by 1) -->
        <div class="border border-slate-200 rounded-2xl overflow-hidden">
          <button
            type="button"
            @click="showManualSteps = !showManualSteps"
            class="w-full px-4 py-2.5 bg-slate-50 hover:bg-slate-100 flex items-center justify-between text-left font-bold text-slate-700 cursor-pointer transition-colors"
          >
            <span>⚙️ Opsional: Susun Langkah Diagram Node Secara Manual ({{ form.steps.length }} Node)</span>
            <span class="text-blue-600 text-[11px]">{{ showManualSteps ? '▲ Sembunyikan' : '▼ Buka Jika Ingin Input Manual' }}</span>
          </button>

          <div v-if="showManualSteps" class="p-4 space-y-3 bg-white border-t border-slate-200">
            <div class="flex items-center justify-between">
              <span class="text-[11px] text-slate-500">Langkah manual ini bersifat opsional jika Anda sudah mengunggah berkas di atas.</span>
              <button
                type="button"
                @click="addStep"
                class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 font-bold text-xs cursor-pointer"
              >
                + Tambah Node
              </button>
            </div>

            <div v-if="form.steps.length === 0" class="p-3 bg-slate-50 rounded-xl text-center text-slate-400 italic">
              Tidak menggunakan langkah manual (menggunakan berkas yang diunggah).
            </div>

            <div v-else class="space-y-2 max-h-56 overflow-y-auto pr-1">
              <div v-for="(st, idx) in form.steps" :key="idx" class="p-3 rounded-xl border border-slate-200 bg-slate-50/70 space-y-2">
                <div class="flex items-center justify-between gap-2">
                  <span class="px-2 py-0.5 rounded bg-blue-600 text-white font-bold text-[10px]">#{{ idx + 1 }}</span>
                  <select v-model="st.step_type" class="py-1 px-2 border border-slate-300 rounded bg-white text-xs font-semibold">
                    <option value="Start">🟢 Start</option>
                    <option value="Process">🔵 Process</option>
                    <option value="Decision">🟡 Decision</option>
                    <option value="End">🔴 End</option>
                  </select>
                  <input v-model="st.title" type="text" placeholder="Judul Langkah" class="flex-1 py-1 px-2.5 border border-slate-300 rounded bg-white" />
                  <input v-model="st.actor" type="text" placeholder="Aktor" class="w-28 py-1 px-2.5 border border-slate-300 rounded bg-white" />
                  <button type="button" @click="removeStep(idx)" class="px-2 py-1 rounded bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 font-bold text-[10px] cursor-pointer">
                    Hapus
                  </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                  <input v-model="st.activity_description" type="text" placeholder="Deskripsi aktivitas..." class="w-full py-1 px-2.5 border border-slate-300 rounded bg-white" />
                  <input v-model="st.expected_result" type="text" placeholder="Hasil yang diharapkan..." class="w-full py-1 px-2.5 border border-slate-300 rounded bg-white" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
          <button type="button" @click="showFormModal = false" class="px-4 py-2 border border-slate-200 rounded-xl font-semibold text-slate-600 hover:bg-slate-50 cursor-pointer">
            Batal
          </button>
          <button
            type="submit"
            :disabled="saving"
            class="px-5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-extrabold rounded-xl cursor-pointer disabled:opacity-50"
          >
            {{ saving ? 'Menyimpan & Mengunggah...' : '💾 Simpan & Upload Flow' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- IN-SYSTEM LIVE PREVIEW & DIAGRAM MODAL -->
    <Modal
      :show="showDiagramModal"
      :title="`Preview Flow: ${activeFlow?.title || ''}`"
      :subtitle="`Kode: ${activeFlow?.flow_code || ''} | Aplikasi: ${activeFlow?.application?.name || '-'}`"
      size="full"
      @close="showDiagramModal = false"
    >
      <div v-if="activeFlow" class="space-y-5">
        <!-- Top File Selector Bar + Upload Button -->
        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
          <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div>
              <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                <span>📂 Daftar Berkas Dokumentasi Flow ({{ activeFlow.files?.length || 0 }} File)</span>
              </h4>
              <p class="text-[11px] text-slate-500">
                Klik pada nama berkas di bawah untuk menampilkan <strong>Preview Langsung di Dalam Sistem</strong> (PPTX, PDF, JPG/PNG, Excel, Word, dll).
              </p>
            </div>

            <label
              v-if="isAdmin"
              class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-extrabold cursor-pointer transition-colors shadow-xs"
            >
              + Upload File Baru (PPT / JPG / PDF / Dll)
              <input
                type="file"
                multiple
                accept=".ppt,.pptx,.pdf,.jpg,.jpeg,.png,.gif,.webp,.svg,.doc,.docx,.xls,.xlsx,.csv,.mp4,.txt,.json,.drawio"
                class="hidden"
                @change="uploadFlowDoc"
              />
            </label>
          </div>

          <div v-if="!activeFlow.files || activeFlow.files.length === 0" class="p-4 rounded-xl bg-white border border-dashed border-slate-200 text-xs text-slate-400 text-center">
            Belum ada berkas yang diunggah pada flow ini. Klik <strong>+ Upload File Baru</strong> untuk mengunggah file PPT, JPG, PDF, dll.
          </div>

          <div v-else class="flex flex-wrap gap-2">
            <div
              v-for="f in activeFlow.files"
              :key="f.id"
              @click="selectFileForPreview(f)"
              :class="[
                'group flex items-center gap-2.5 px-3.5 py-2 rounded-xl border text-xs font-bold cursor-pointer transition-all',
                selectedPreviewFile?.id === f.id
                  ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-600/20'
                  : 'bg-white text-slate-700 border-slate-200 hover:border-blue-300 hover:bg-blue-50/40'
              ]"
            >
              <span class="text-base">{{ getFileIcon(f) }}</span>
              <div class="text-left">
                <div class="truncate max-w-56">{{ f.original_name }}</div>
                <div :class="['text-[10px] font-normal', selectedPreviewFile?.id === f.id ? 'text-blue-100' : 'text-slate-400']">
                  {{ (f.file_type || '').toUpperCase() }} • {{ formatBytes(f.file_size) }}
                </div>
              </div>
              <button
                v-if="isAdmin"
                type="button"
                @click.stop="deleteFileFromModal(f)"
                :class="[
                  'ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold transition-colors cursor-pointer',
                  selectedPreviewFile?.id === f.id
                    ? 'bg-blue-700 hover:bg-red-600 text-white'
                    : 'bg-slate-100 hover:bg-red-100 text-slate-500 hover:text-red-600'
                ]"
                title="Hapus file ini"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <!-- IN-SYSTEM LIVE PREVIEW STAGE -->
        <div v-if="selectedPreviewFile" class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
          <!-- Preview Header Toolbar -->
          <div class="bg-slate-900 text-white px-4 py-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center space-x-2.5 truncate">
              <span class="text-lg">{{ getFileIcon(selectedPreviewFile) }}</span>
              <div class="truncate">
                <div class="text-xs font-extrabold truncate">{{ selectedPreviewFile.original_name }}</div>
                <div class="text-[10px] text-slate-400">
                  Mode Preview Langsung di Dalam Sistem • Format {{ (selectedPreviewFile.file_type || '').toUpperCase() }}
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <!-- Image Zoom Controls -->
              <template v-if="isImageFile(selectedPreviewFile)">
                <button
                  type="button"
                  @click="imageZoom = Math.max(25, imageZoom - 25)"
                  class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-bold cursor-pointer"
                >
                  🔍 -
                </button>
                <span class="text-xs font-mono font-bold px-1">{{ imageZoom }}%</span>
                <button
                  type="button"
                  @click="imageZoom = Math.min(300, imageZoom + 25)"
                  class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-bold cursor-pointer"
                >
                  🔍 +
                </button>
                <button
                  type="button"
                  @click="imageZoom = 100"
                  class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-[11px] font-semibold cursor-pointer"
                >
                  Reset
                </button>
              </template>

              <a
                :href="`/api/files/flow/${selectedPreviewFile.id}/preview`"
                target="_blank"
                class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition-colors"
              >
                ↗ Buka Tab Penuh
              </a>
              <a
                :href="`/api/files/flow/${selectedPreviewFile.id}/download`"
                target="_blank"
                class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-extrabold transition-colors"
              >
                ⬇️ Unduh File
              </a>
            </div>
          </div>

          <!-- 1. IMAGE PREVIEW (JPG, PNG, WEBP, GIF, SVG) -->
          <div
            v-if="isImageFile(selectedPreviewFile)"
            class="p-6 bg-slate-100/80 flex items-center justify-center overflow-auto max-h-[650px]"
          >
            <img
              :src="`/api/files/flow/${selectedPreviewFile.id}/preview`"
              :alt="selectedPreviewFile.original_name"
              :style="{ width: `${imageZoom}%`, maxWidth: imageZoom === 100 ? '100%' : 'none' }"
              class="rounded-xl shadow-md border border-slate-200 bg-white transition-all duration-150"
            />
          </div>

          <!-- 2. PDF PREVIEW -->
          <div v-else-if="isPdfFile(selectedPreviewFile)" class="bg-slate-800">
            <iframe
              :src="`/api/files/flow/${selectedPreviewFile.id}/preview`"
              class="w-full h-[650px] border-0"
              title="PDF In-System Preview"
            ></iframe>
          </div>

          <!-- 3. VIDEO PREVIEW (MP4, WEBM) -->
          <div v-else-if="isVideoFile(selectedPreviewFile)" class="p-6 bg-slate-950 flex justify-center">
            <video
              :src="`/api/files/flow/${selectedPreviewFile.id}/preview`"
              controls
              class="max-h-[560px] rounded-xl shadow-lg"
            ></video>
          </div>

          <!-- 4. RICH EXTRACTED PREVIEW (PPTX, XLSX, DOCX, TXT, JSON) -->
          <div v-else class="p-5 bg-slate-50">
            <LoadingSpinner v-if="loadingPreviewContent" text="Menyiapkan pratinjau dokumen di dalam sistem..." />

            <!-- 4A. POWERPOINT (PPTX) INTERACTIVE SLIDE VIEWER -->
            <div v-else-if="extractedPreview?.preview_type === 'pptx'" class="space-y-4">
              <div v-if="!extractedPreview.slides || extractedPreview.slides.length === 0" class="p-6 text-center text-xs text-slate-500">
                File presentasi tidak memiliki slide yang dapat ditampilkan.
              </div>
              <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                <!-- Slide Thumbnails List (Left 3 cols) -->
                <div class="lg:col-span-3 flex lg:flex-col gap-2 overflow-x-auto lg:overflow-y-auto lg:max-h-[540px] pr-1">
                  <button
                    v-for="(sl, sIdx) in extractedPreview.slides"
                    :key="sl.slide_number"
                    type="button"
                    @click="activeSlideIndex = sIdx"
                    :class="[
                      'p-3 rounded-xl border text-left transition-all cursor-pointer shrink-0 w-48 lg:w-full',
                      activeSlideIndex === sIdx
                        ? 'bg-orange-600 text-white border-orange-600 shadow-sm'
                        : 'bg-white text-slate-700 border-slate-200 hover:border-orange-300'
                    ]"
                  >
                    <div class="text-[10px] font-extrabold uppercase opacity-80">Slide {{ sl.slide_number }}</div>
                    <div class="text-xs font-bold truncate mt-0.5">{{ sl.title }}</div>
                  </button>
                </div>

                <!-- Main Slide Canvas (Right 9 cols) -->
                <div class="lg:col-span-9 bg-white rounded-2xl border border-slate-200 shadow-md p-6 min-h-[440px] flex flex-col justify-between">
                  <div v-if="currentSlide">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                      <span class="px-2.5 py-1 rounded-lg bg-orange-50 text-orange-700 border border-orange-200 text-xs font-extrabold">
                        📊 Slide {{ currentSlide.slide_number }} dari {{ extractedPreview.slides.length }}
                      </span>
                      <div class="flex items-center gap-2">
                        <button
                          type="button"
                          :disabled="activeSlideIndex <= 0"
                          @click="activeSlideIndex--"
                          class="px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-bold disabled:opacity-40 cursor-pointer"
                        >
                          ◀ Slide Sebelumnya
                        </button>
                        <button
                          type="button"
                          :disabled="activeSlideIndex >= extractedPreview.slides.length - 1"
                          @click="activeSlideIndex++"
                          class="px-3 py-1.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold disabled:opacity-40 cursor-pointer"
                        >
                          Slide Berikutnya ▶
                        </button>
                      </div>
                    </div>

                    <!-- Slide Title -->
                    <h3 class="text-lg font-black text-slate-900 mb-4">{{ currentSlide.title }}</h3>

                    <!-- Slide Images (Diagrams / Screenshots inside PPTX) -->
                    <div v-if="currentSlide.images && currentSlide.images.length > 0" class="grid grid-cols-1 gap-4 mb-4">
                      <div
                        v-for="(imgData, iIdx) in currentSlide.images"
                        :key="iIdx"
                        class="flex justify-center bg-slate-50 p-3 rounded-xl border border-slate-200"
                      >
                        <img :src="imgData" class="max-h-96 object-contain rounded-lg" alt="Slide Diagram" />
                      </div>
                    </div>

                    <!-- Slide Bullet Points / Text -->
                    <ul v-if="currentSlide.bullets && currentSlide.bullets.length > 0" class="space-y-2 text-xs text-slate-700 list-disc pl-5 leading-relaxed">
                      <li v-for="(bText, bIdx) in currentSlide.bullets" :key="bIdx">
                        {{ bText }}
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <!-- 4B. SPREADSHEET (XLSX / XLS / CSV) TABLE VIEWER -->
            <div v-else-if="extractedPreview?.preview_type === 'spreadsheet'" class="space-y-3">
              <div class="flex flex-wrap items-center gap-1.5 border-b border-slate-200 pb-2">
                <button
                  v-for="(sh, shIdx) in extractedPreview.sheets"
                  :key="shIdx"
                  type="button"
                  @click="activeSheetIndex = shIdx"
                  :class="[
                    'px-3 py-1.5 rounded-xl text-xs font-bold cursor-pointer transition-colors',
                    activeSheetIndex === shIdx
                      ? 'bg-emerald-600 text-white'
                      : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
                  ]"
                >
                  📊 {{ sh.name }}
                </button>
              </div>

              <div v-if="currentSheet" class="overflow-auto max-h-[520px] bg-white rounded-xl border border-slate-200">
                <table class="w-full text-left border-collapse text-xs">
                  <tbody>
                    <tr
                      v-for="(row, rIdx) in currentSheet.rows"
                      :key="rIdx"
                      :class="rIdx === 0 ? 'bg-slate-100 font-bold text-slate-900' : 'border-t border-slate-100 hover:bg-slate-50'"
                    >
                      <td
                        v-for="(cell, cIdx) in row"
                        :key="cIdx"
                        class="py-2 px-3 border-r border-slate-100 whitespace-nowrap"
                      >
                        {{ cell }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 4C. WORD DOCUMENT (DOCX) READER -->
            <div v-else-if="extractedPreview?.preview_type === 'docx'" class="bg-white p-6 rounded-2xl border border-slate-200 max-h-[560px] overflow-y-auto space-y-3">
              <div v-if="extractedPreview.images && extractedPreview.images.length > 0" class="space-y-3 mb-4">
                <img
                  v-for="(img, idx) in extractedPreview.images"
                  :key="idx"
                  :src="img"
                  class="max-h-96 rounded-xl border border-slate-200 mx-auto"
                />
              </div>
              <p
                v-for="(p, pIdx) in extractedPreview.paragraphs"
                :key="pIdx"
                class="text-xs text-slate-800 leading-relaxed"
              >
                {{ p }}
              </p>
            </div>

            <!-- 4D. TEXT / JSON / XML PREVIEW -->
            <div v-else-if="extractedPreview?.preview_type === 'text'" class="bg-slate-900 text-emerald-300 p-4 rounded-xl font-mono text-xs overflow-auto max-h-[520px] whitespace-pre-wrap">
              {{ extractedPreview.content }}
            </div>

            <!-- 4E. FALLBACK FOR LEGACY BINARY FORMATS -->
            <div v-else class="p-8 text-center space-y-3 bg-white rounded-xl border border-slate-200">
              <div class="text-3xl">{{ getFileIcon(selectedPreviewFile) }}</div>
              <div class="text-sm font-bold text-slate-800">{{ selectedPreviewFile.original_name }}</div>
              <p class="text-xs text-slate-500 max-w-md mx-auto">
                 {{ extractedPreview?.message || 'Berkas ini siap dibuka atau diunduh ke perangkat Anda.' }}
              </p>
              <div class="pt-2">
                <a
                  :href="`/api/files/flow/${selectedPreviewFile.id}/download`"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold"
                >
                  ⬇️ Unduh Berkas ({{ selectedPreviewFile.original_name }})
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Optional Manual Flow Diagram (Only shown if the flow actually has manual steps) -->
        <div v-if="activeFlow.steps && activeFlow.steps.length > 0" class="pt-2">
          <h4 class="text-xs font-extrabold text-slate-900 mb-2 uppercase tracking-wider">
            🔀 Visualisasi Diagram Node ({{ activeFlow.steps.length }} Langkah):
          </h4>
          <FlowDiagram :steps="activeFlow.steps" @select-step="handleStepClick" />
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import api from '../api';
import { useAuth } from '../auth';
import Badge from '../components/Badge.vue';
import Modal from '../components/Modal.vue';
import EmptyState from '../components/EmptyState.vue';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import FlowDiagram from '../components/FlowDiagram.vue';
import OfficeSkylineBg from '../components/OfficeSkylineBg.vue';

const { isAdmin } = useAuth();

const loading = ref(false);
const saving = ref(false);
const flows = ref([]);
const apps = ref([]);
const moduleOptions = ref([]);
const menuOptions = ref([]);

const filters = reactive({
  search: '',
  application_id: '',
});

const showDiagramModal = ref(false);
const activeFlow = ref(null);
const selectedPreviewFile = ref(null);
const imageZoom = ref(100);
const loadingPreviewContent = ref(false);
const extractedPreview = ref(null);
const activeSlideIndex = ref(0);
const activeSheetIndex = ref(0);

const showFormModal = ref(false);
const showManualSteps = ref(false);
const isEdit = ref(false);
const currentId = ref(null);
const pendingFiles = ref([]);
const existingFiles = ref([]);

const form = reactive({
  title: '',
  application_id: '',
  module_id: '',
  menu_id: '',
  version: '1.0',
  status: 'Published',
  process_goal: '',
  actors: '',
  prerequisites: '',
  description: '',
  steps: [],
});

const currentSlide = computed(() => {
  if (!extractedPreview.value?.slides) return null;
  return extractedPreview.value.slides[activeSlideIndex.value] || extractedPreview.value.slides[0] || null;
});

const currentSheet = computed(() => {
  if (!extractedPreview.value?.sheets) return null;
  return extractedPreview.value.sheets[activeSheetIndex.value] || extractedPreview.value.sheets[0] || null;
});

const getFileExt = (f) => {
  if (!f) return '';
  if (f.file_type) return f.file_type.toLowerCase();
  const name = f.original_name || f.name || '';
  const parts = name.split('.');
  return parts.length > 1 ? parts.pop().toLowerCase() : '';
};

const isImageFile = (f) => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(getFileExt(f));
const isPdfFile = (f) => getFileExt(f) === 'pdf';
const isVideoFile = (f) => ['mp4', 'webm', 'ogg'].includes(getFileExt(f));

const getFileIcon = (f) => {
  const ext = getFileExt(f);
  if (['ppt', 'pptx'].includes(ext)) return '📊';
  if (ext === 'pdf') return '📕';
  if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext)) return '🖼️';
  if (['xls', 'xlsx', 'csv'].includes(ext)) return '📗';
  if (['doc', 'docx'].includes(ext)) return '📘';
  if (['mp4', 'webm'].includes(ext)) return '🎬';
  return '📄';
};

const formatBytes = (bytes) => {
  const b = Number(bytes || 0);
  if (!b) return '';
  if (b < 1024) return `${b} B`;
  if (b < 1024 * 1024) return `${(b / 1024).toFixed(1)} KB`;
  return `${(b / (1024 * 1024)).toFixed(1)} MB`;
};

const fetchFlows = async () => {
  loading.value = true;
  try {
    const res = await api.get('/flows', { params: filters });
    flows.value = res.data.data;
  } catch (e) {
    console.error('Fetch flows error:', e);
  } finally {
    loading.value = false;
  }
};

const fetchLookups = async () => {
  try {
    const res = await api.get('/cascade/apps');
    apps.value = res.data;
  } catch (e) {}
};

const handleAppChange = async () => {
  form.module_id = '';
  form.menu_id = '';
  moduleOptions.value = [];
  menuOptions.value = [];
  if (form.application_id) {
    try {
      const res = await api.get(`/cascade/modules?application_id=${form.application_id}`);
      moduleOptions.value = res.data;
    } catch (e) {}
  }
};

const handleModuleChange = async () => {
  form.menu_id = '';
  menuOptions.value = [];
  if (form.module_id) {
    try {
      const res = await api.get(`/cascade/menus?module_id=${form.module_id}`);
      menuOptions.value = res.data;
    } catch (e) {}
  }
};

const resetFilters = () => {
  filters.search = '';
  filters.application_id = '';
  fetchFlows();
};

const handleFormFilesSelect = (e) => {
  const selected = Array.from(e.target.files || []);
  if (selected.length === 0) return;
  pendingFiles.value.push(...selected);

  // Auto-fill flow title from first filename if title is still empty
  if (!form.title.trim() && selected[0]) {
    const rawName = selected[0].name.replace(/\.[^/.]+$/, '').replace(/[_-]+/g, ' ');
    form.title = `Flow ${rawName}`;
  }
  e.target.value = '';
};

const addStep = () => {
  form.steps.push({
    step_number: form.steps.length + 1,
    step_type: form.steps.length === 0 ? 'Start' : 'Process',
    title: '',
    actor: '',
    activity_description: '',
    expected_result: '',
  });
};

const removeStep = (idx) => {
  form.steps.splice(idx, 1);
  form.steps.forEach((s, i) => {
    s.step_number = i + 1;
  });
};

const openCreateModal = async () => {
  isEdit.value = false;
  currentId.value = null;
  pendingFiles.value = [];
  existingFiles.value = [];
  showManualSteps.value = false;
  form.title = '';
  form.application_id = apps.value[0]?.id || '';
  await handleAppChange();
  form.module_id = '';
  form.menu_id = '';
  form.version = '1.0';
  form.status = 'Published';
  form.process_goal = '';
  form.actors = '';
  form.prerequisites = '';
  form.description = '';
  form.steps = [];
  showFormModal.value = true;
};

const openEditModal = async (f) => {
  try {
    const res = await api.get(`/flows/${f.id}`);
    const detail = res.data.flow;
    isEdit.value = true;
    currentId.value = detail.id;
    pendingFiles.value = [];
    existingFiles.value = detail.files || [];
    form.title = detail.title;
    form.application_id = detail.application_id;
    await handleAppChange();
    form.module_id = detail.module_id || '';
    if (form.module_id) {
      await handleModuleChange();
    }
    form.menu_id = detail.menu_id || '';
    form.version = detail.version || '1.0';
    form.status = detail.status || 'Published';
    form.process_goal = detail.process_goal || '';
    form.actors = detail.actors || '';
    form.prerequisites = detail.prerequisites || '';
    form.description = detail.description || '';
    form.steps = (detail.steps || []).map((s, idx) => ({
      step_number: idx + 1,
      step_type: s.step_type || 'Process',
      title: s.title || '',
      actor: s.actor || '',
      activity_description: s.activity_description || '',
      expected_result: s.expected_result || '',
    }));
    showManualSteps.value = form.steps.length > 0;
    showFormModal.value = true;
  } catch (e) {
    alert('Gagal memuat detail flow untuk diedit.');
  }
};

const saveFlow = async () => {
  saving.value = true;
  try {
    const fd = new FormData();
    fd.append('title', form.title);
    fd.append('application_id', form.application_id);
    if (form.module_id) fd.append('module_id', form.module_id);
    if (form.menu_id) fd.append('menu_id', form.menu_id);
    fd.append('version', form.version || '1.0');
    fd.append('status', form.status || 'Published');
    if (form.process_goal) fd.append('process_goal', form.process_goal);
    if (form.actors) fd.append('actors', form.actors);
    if (form.prerequisites) fd.append('prerequisites', form.prerequisites);
    if (form.description) fd.append('description', form.description);

    const validSteps = form.steps
      .filter((s) => s.title && s.title.trim() !== '')
      .map((s, idx) => ({ ...s, step_number: idx + 1 }));
    fd.append('steps', JSON.stringify(validSteps));

    pendingFiles.value.forEach((file) => {
      fd.append('files[]', file);
    });

    if (isEdit.value) {
      fd.append('_method', 'PUT');
      await api.post(`/flows/${currentId.value}`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    } else {
      await api.post('/flows', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    }

    showFormModal.value = false;
    await fetchFlows();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan flow aplikasi.');
  } finally {
    saving.value = false;
  }
};

const deleteExistingFile = async (fileObj) => {
  if (!confirm(`Hapus berkas '${fileObj.original_name}'?`)) return;
  try {
    await api.delete(`/flows/${currentId.value}/files/${fileObj.id}`);
    existingFiles.value = existingFiles.value.filter((f) => f.id !== fileObj.id);
    fetchFlows();
  } catch (e) {
    alert('Gagal menghapus berkas.');
  }
};

const deleteFileFromModal = async (fileObj) => {
  if (!activeFlow.value || !confirm(`Hapus berkas '${fileObj.original_name}'?`)) return;
  try {
    await api.delete(`/flows/${activeFlow.value.id}/files/${fileObj.id}`);
    const res = await api.get(`/flows/${activeFlow.value.id}`);
    activeFlow.value = res.data.flow;
    if (selectedPreviewFile.value?.id === fileObj.id) {
      selectedPreviewFile.value = activeFlow.value.files?.[0] || null;
      if (selectedPreviewFile.value) {
        selectFileForPreview(selectedPreviewFile.value);
      }
    }
    fetchFlows();
  } catch (e) {
    alert('Gagal menghapus berkas.');
  }
};

const quickUploadToCard = async (e, flow) => {
  const files = Array.from(e.target.files || []);
  if (files.length === 0) return;
  const fd = new FormData();
  files.forEach((file) => fd.append('files[]', file));
  try {
    await api.post(`/flows/${flow.id}/files`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    await fetchFlows();
    openPreviewModal(flow);
  } catch (err) {
    alert('Gagal mengunggah berkas ke flow.');
  } finally {
    e.target.value = '';
  }
};

const confirmDelete = async (f) => {
  if (!confirm(`Hapus flow aplikasi '${f.title}' (${f.flow_code})?`)) return;
  try {
    await api.delete(`/flows/${f.id}`);
    fetchFlows();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus flow aplikasi.');
  }
};

const selectFileForPreview = async (fileObj) => {
  selectedPreviewFile.value = fileObj;
  imageZoom.value = 100;
  extractedPreview.value = null;
  activeSlideIndex.value = 0;
  activeSheetIndex.value = 0;

  if (!fileObj) return;
  if (isImageFile(fileObj) || isPdfFile(fileObj) || isVideoFile(fileObj)) {
    return;
  }

  loadingPreviewContent.value = true;
  try {
    const res = await api.get(`/files/flow/${fileObj.id}/content-preview`);
    extractedPreview.value = res.data;
  } catch (e) {
    extractedPreview.value = {
      preview_type: 'error',
      message: 'Pratinjau langsung tidak tersedia untuk berkas ini. Silakan unduh berkas.',
    };
  } finally {
    loadingPreviewContent.value = false;
  }
};

const openPreviewModal = async (f, targetFile = null) => {
  try {
    const res = await api.get(`/flows/${f.id}`);
    activeFlow.value = res.data.flow;
    showDiagramModal.value = true;
    const initialFile = targetFile || activeFlow.value.files?.[0] || null;
    if (initialFile) {
      await selectFileForPreview(initialFile);
    } else {
      selectedPreviewFile.value = null;
    }
  } catch (e) {}
};

const handleStepClick = (step) => {
  alert(`Langkah #${step.step_number}: ${step.title}\n\nAktivitas: ${step.activity_description || '-'}\nAktor: ${step.actor || '-'}\nExpected Result: ${step.expected_result || '-'}`);
};

const uploadFlowDoc = async (e) => {
  const files = Array.from(e.target.files || []);
  if (files.length === 0 || !activeFlow.value) return;
  const fd = new FormData();
  files.forEach((file) => fd.append('files[]', file));
  try {
    await api.post(`/flows/${activeFlow.value.id}/files`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    const res = await api.get(`/flows/${activeFlow.value.id}`);
    activeFlow.value = res.data.flow;
    const newestFile = activeFlow.value.files?.[activeFlow.value.files.length - 1];
    if (newestFile) {
      await selectFileForPreview(newestFile);
    }
    fetchFlows();
  } catch (err) {
    alert('Gagal mengunggah berkas flow.');
  } finally {
    e.target.value = '';
  }
};

onMounted(() => {
  fetchLookups();
  fetchFlows();
});
</script>

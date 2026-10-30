<template>
  <div class="space-y-6">
    <!-- ==================== MODERN EXECUTIVE HERO WITH ANIMATED OFFICE SKYLINE ==================== -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-b from-[#060d21] via-[#0c1d42] to-[#102a5c] p-6 sm:p-8 text-white shadow-xl border border-slate-800/90">
      <!-- Ambient Sky Glows -->
      <div class="absolute -left-16 -top-20 w-72 h-72 rounded-full bg-blue-500/20 blur-3xl pointer-events-none"></div>
      <div class="absolute right-1/4 -top-16 w-64 h-64 rounded-full bg-cyan-400/15 blur-3xl pointer-events-none"></div>

      <!-- Animated Corporate Office Skyline SVG Background -->
      <div class="absolute inset-0 pointer-events-none overflow-hidden opacity-85">
        <svg
          class="absolute bottom-0 left-0 w-full h-full min-h-[190px] object-cover"
          viewBox="0 0 1600 260"
          preserveAspectRatio="xMidYMax slice"
          xmlns="http://www.w3.org/2000/svg"
        >
          <defs>
            <linearGradient id="dashBgTower" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#153166" stop-opacity="0.55" />
              <stop offset="100%" stop-color="#07132c" stop-opacity="0.85" />
            </linearGradient>
            <linearGradient id="dashMidTower" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#1e4287" stop-opacity="0.8" />
              <stop offset="100%" stop-color="#091836" stop-opacity="0.95" />
            </linearGradient>
            <linearGradient id="dashFrontTower" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="#2563eb" stop-opacity="0.5" />
              <stop offset="50%" stop-color="#163570" stop-opacity="0.85" />
              <stop offset="100%" stop-color="#08152e" stop-opacity="0.98" />
            </linearGradient>
            <linearGradient id="dashBeam" x1="0" y1="1" x2="0" y2="0">
              <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.22" />
              <stop offset="100%" stop-color="#38bdf8" stop-opacity="0" />
            </linearGradient>
          </defs>

          <!-- Sweeping Architectural Spotlights -->
          <polygon class="dash-beam-left" points="380,250 290,0 440,0" fill="url(#dashBeam)" />
          <polygon class="dash-beam-right" points="1180,250 1090,0 1250,0" fill="url(#dashBeam)" />

          <!-- Distant Skyline Silhouette -->
          <g fill="url(#dashBgTower)">
            <rect x="30" y="95" width="75" height="155" rx="2" />
            <rect x="120" y="65" width="90" height="185" rx="2" />
            <rect x="340" y="80" width="85" height="170" rx="2" />
            <rect x="560" y="105" width="95" height="145" rx="2" />
            <rect x="670" y="75" width="110" height="175" rx="2" />
            <rect x="920" y="85" width="90" height="165" rx="2" />
            <rect x="1150" y="60" width="105" height="190" rx="2" />
            <rect x="1480" y="80" width="90" height="170" rx="2" />
          </g>

          <!-- Mid & Foreground Animated Office Buildings (Concentrated on Right & Center-Right + Left Accent) -->
          <!-- Left Corporate Tower -->
          <g>
            <line x1="265" y1="22" x2="265" y2="55" stroke="#60a5fa" stroke-width="2.5" />
            <circle class="dash-beacon" cx="265" cy="20" r="3.5" fill="#ef4444" />
            <rect x="220" y="55" width="90" height="195" rx="3" fill="url(#dashMidTower)" stroke="#38bdf8" stroke-opacity="0.3" />
            <!-- Windows -->
            <g fill="#fde68a" class="dash-win-1">
              <rect x="232" y="72" width="12" height="6" rx="1" />
              <rect x="252" y="72" width="12" height="6" rx="1" />
              <rect x="272" y="92" width="12" height="6" rx="1" />
              <rect x="232" y="112" width="12" height="6" rx="1" />
              <rect x="252" y="132" width="12" height="6" rx="1" />
              <rect x="272" y="152" width="12" height="6" rx="1" />
              <rect x="232" y="172" width="12" height="6" rx="1" />
            </g>
            <g fill="#38bdf8" class="dash-win-2">
              <rect x="272" y="72" width="12" height="6" rx="1" />
              <rect x="232" y="92" width="12" height="6" rx="1" />
              <rect x="252" y="112" width="12" height="6" rx="1" />
              <rect x="272" y="132" width="12" height="6" rx="1" />
              <rect x="232" y="152" width="12" height="6" rx="1" />
              <rect x="252" y="172" width="12" height="6" rx="1" />
            </g>
          </g>

          <!-- Center-Right Stepped Glass HQ Tower with Radar & Moving Elevator -->
          <g>
            <line x1="855" y1="18" x2="855" y2="48" stroke="#93c5fd" stroke-width="2.5" />
            <circle cx="855" cy="18" r="3.5" fill="#38bdf8" />
            <circle class="dash-radar" cx="855" cy="18" r="12" stroke="#38bdf8" stroke-width="1.5" fill="none" />
            <rect x="805" y="48" width="100" height="202" rx="4" fill="url(#dashFrontTower)" stroke="#38bdf8" stroke-opacity="0.4" />
            <rect x="816" y="56" width="78" height="4" rx="2" fill="#38bdf8" opacity="0.85" />

            <!-- Panoramic Elevator Shaft -->
            <rect x="888" y="68" width="9" height="175" fill="#071226" stroke="#38bdf8" stroke-opacity="0.35" />
            <rect class="dash-elevator-1" x="889.5" y="75" width="6" height="13" rx="1.5" fill="#fde68a" />

            <!-- Office Windows -->
            <g fill="#e0f2fe" class="dash-win-2">
              <rect x="816" y="72" width="15" height="6" rx="1" />
              <rect x="838" y="72" width="15" height="6" rx="1" />
              <rect x="860" y="90" width="15" height="6" rx="1" />
              <rect x="816" y="108" width="15" height="6" rx="1" />
              <rect x="838" y="126" width="15" height="6" rx="1" />
              <rect x="860" y="144" width="15" height="6" rx="1" />
              <rect x="816" y="162" width="15" height="6" rx="1" />
              <rect x="838" y="180" width="15" height="6" rx="1" />
            </g>
            <g fill="#fde68a" class="dash-win-3">
              <rect x="860" y="72" width="15" height="6" rx="1" />
              <rect x="816" y="90" width="15" height="6" rx="1" />
              <rect x="838" y="108" width="15" height="6" rx="1" />
              <rect x="860" y="126" width="15" height="6" rx="1" />
              <rect x="816" y="144" width="15" height="6" rx="1" />
              <rect x="838" y="162" width="15" height="6" rx="1" />
              <rect x="860" y="180" width="15" height="6" rx="1" />
            </g>
          </g>

          <!-- Right Twin Diagonal-Braced Skyscraper -->
          <g>
            <polygon points="1025,52 1125,30 1125,250 1025,250" fill="url(#dashMidTower)" stroke="#38bdf8" stroke-opacity="0.35" />
            <g stroke="#38bdf8" stroke-opacity="0.22" stroke-width="1.2">
              <line x1="1025" y1="75" x2="1125" y2="125" />
              <line x1="1125" y1="75" x2="1025" y2="125" />
              <line x1="1025" y1="145" x2="1125" y2="195" />
              <line x1="1125" y1="145" x2="1025" y2="195" />
            </g>
            <!-- Twin Tower Windows -->
            <g fill="#38bdf8" class="dash-win-1">
              <rect x="1040" y="65" width="70" height="4" rx="2" />
              <rect x="1040" y="95" width="70" height="4" rx="2" />
              <rect x="1040" y="125" width="70" height="4" rx="2" />
              <rect x="1040" y="155" width="70" height="4" rx="2" />
              <rect x="1040" y="185" width="70" height="4" rx="2" />
            </g>
          </g>

          <!-- Far Right Executive Glass Tower with Second Elevator -->
          <g>
            <line x1="1335" y1="15" x2="1335" y2="42" stroke="#60a5fa" stroke-width="2.5" />
            <circle class="dash-beacon" cx="1335" cy="13" r="3.5" fill="#ef4444" />
            <rect x="1275" y="42" width="125" height="208" rx="4" fill="url(#dashFrontTower)" stroke="#38bdf8" stroke-opacity="0.4" />
            <rect x="1285" y="52" width="105" height="5" rx="2.5" fill="#38bdf8" opacity="0.85" />

            <!-- Elevator Shaft 2 -->
            <rect x="1286" y="66" width="9" height="176" fill="#071226" stroke="#38bdf8" stroke-opacity="0.35" />
            <rect class="dash-elevator-2" x="1287.5" y="140" width="6" height="13" rx="1.5" fill="#38bdf8" />

            <!-- Office Windows -->
            <g fill="#fde68a" class="dash-win-1">
              <rect x="1305" y="70" width="18" height="6" rx="1.5" />
              <rect x="1332" y="70" width="18" height="6" rx="1.5" />
              <rect x="1360" y="88" width="18" height="6" rx="1.5" />
              <rect x="1305" y="106" width="18" height="6" rx="1.5" />
              <rect x="1332" y="124" width="18" height="6" rx="1.5" />
              <rect x="1360" y="142" width="18" height="6" rx="1.5" />
              <rect x="1305" y="160" width="18" height="6" rx="1.5" />
              <rect x="1332" y="178" width="18" height="6" rx="1.5" />
            </g>
            <g fill="#38bdf8" class="dash-win-3">
              <rect x="1360" y="70" width="18" height="6" rx="1.5" />
              <rect x="1305" y="88" width="18" height="6" rx="1.5" />
              <rect x="1332" y="106" width="18" height="6" rx="1.5" />
              <rect x="1360" y="124" width="18" height="6" rx="1.5" />
              <rect x="1305" y="142" width="18" height="6" rx="1.5" />
              <rect x="1332" y="160" width="18" height="6" rx="1.5" />
              <rect x="1360" y="178" width="18" height="6" rx="1.5" />
            </g>
          </g>

          <!-- Ground Smart Highway & Moving Traffic Trails -->
          <rect x="0" y="242" width="1600" height="18" fill="#040a18" />
          <line x1="0" y1="242" x2="1600" y2="242" stroke="#38bdf8" stroke-opacity="0.45" stroke-width="1.5" />
          <g class="dash-traffic-right">
            <rect x="120" y="247" width="48" height="3.5" rx="1.75" fill="#fde68a" />
            <rect x="164" y="246.5" width="12" height="4.5" rx="2" fill="#ffffff" />
          </g>
          <g class="dash-traffic-left">
            <rect x="1280" y="253" width="52" height="3.5" rx="1.75" fill="#f43f5e" />
            <rect x="1272" y="252.5" width="10" height="4.5" rx="2" fill="#fda4af" />
          </g>
        </svg>
      </div>

      <!-- Hero Content -->
      <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
        <div>
          <div class="flex flex-wrap items-center gap-2 mb-2.5">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-sky-500/20 border border-sky-400/30 text-sky-300 text-[10px] font-extrabold uppercase tracking-wider backdrop-blur-xs">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
              <span>{{ canViewAllData ? 'Real-time Corporate QA Intelligence' : 'Personal QA Tester Workspace' }}</span>
            </div>
            <span
              v-if="!isAdmin"
              class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/20 border border-amber-400/35 text-amber-200 text-[10px] font-extrabold uppercase tracking-wider backdrop-blur-xs"
            >
              <span>🔒 Akses Terbatas (Role User)</span>
            </span>
          </div>
          <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white drop-shadow">
            {{ canViewAllData ? 'Pusat Kendali & Analitik Quality Assurance' : `Dashboard Pengujian Saya — ${currentUser?.name || 'Tester'}` }}
          </h2>
          <p class="text-xs text-slate-200/90 mt-1 max-w-2xl leading-relaxed drop-shadow-xs">
            <template v-if="canViewAllData">
              Pantau kesehatan rilis aplikasi, kurva kelulusan skenario pengujian, temuan bug kritis, dan progres jadwal QA secara *real-time* dalam satu ekosistem terpadu.
            </template>
            <template v-else>
              Menampilkan ringkasan progres skenario pengujian yang ditugaskan kepada akun Anda. Data tester lain dan grafik analitik eksekutif lintas aplikasi dibatasi sesuai hak akses Role User.
            </template>
          </p>
        </div>

        <!-- Quick Navigation Pills (Only shown if permitted by Role Checklist) -->
        <div v-if="canUseQuickActions" class="flex flex-wrap items-center gap-2.5 shrink-0">
          <router-link
            v-if="hasMenuAccess('/test-cases') && hasFeatureAccess('execute_test')"
            to="/test-cases"
            class="px-4 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-xs font-bold text-white transition-all shadow-sm"
          >
            📝 Eksekusi Test
          </router-link>
          <router-link
            v-if="hasMenuAccess('/bugs') && hasFeatureAccess('create_bug')"
            to="/bugs"
            class="px-4 py-2.5 rounded-2xl bg-rose-500/25 hover:bg-rose-500/35 backdrop-blur-md border border-rose-400/35 text-xs font-bold text-rose-100 transition-all shadow-sm"
          >
            🐞 Lapor Bug
          </router-link>
          <router-link
            v-if="hasMenuAccess('/reports/wa')"
            to="/reports/wa"
            class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-xs font-extrabold text-white shadow-lg shadow-emerald-600/30 transition-all"
          >
            💬 Report WA Harian
          </router-link>
        </div>
      </div>
    </div>

    <!-- ==================== FILTER BAR ==================== -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
        <div class="flex items-center space-x-3">
          <div class="w-9 h-9 rounded-2xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center text-base">
            🎛️
          </div>
          <div>
            <h2 class="text-sm font-extrabold text-slate-900 tracking-tight">
              {{ canViewAllData ? 'Filter Cakupan Analitik QA' : 'Filter Tugas Pengujian Saya' }}
            </h2>
            <p class="text-xs text-slate-500">
              {{ canViewAllData ? 'Saring grafik dan metrik berdasarkan Aplikasi, Modul, Sub-Menu, Tester, dan Periode.' : 'Saring progres pengujian Anda berdasarkan Aplikasi dan Modul.' }}
            </p>
          </div>
        </div>
        <div class="flex items-center space-x-2">
          <!-- Chart Style Switcher (Only if executive charts are enabled) -->
          <div v-if="canViewExecCharts" class="bg-slate-100 p-1 rounded-xl flex items-center text-[11px] font-bold">
            <button
              type="button"
              @click="chartStyle = 'wave'"
              :class="[
                'px-3 py-1 rounded-lg transition-all cursor-pointer',
                chartStyle === 'wave' ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-500 hover:text-slate-800'
              ]"
            >
              🌊 Kurva Modern
            </button>
            <button
              type="button"
              @click="chartStyle = 'capsule'"
              :class="[
                'px-3 py-1 rounded-lg transition-all cursor-pointer',
                chartStyle === 'capsule' ? 'bg-white text-blue-600 shadow-xs' : 'text-slate-500 hover:text-slate-800'
              ]"
            >
              📊 Kapsul Halus
            </button>
          </div>

          <button
            @click="resetFilters"
            class="px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-600 transition-colors cursor-pointer"
          >
            Reset
          </button>
          <button
            @click="fetchDashboardData"
            class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-bold text-white transition-colors flex items-center space-x-1.5 shadow-xs cursor-pointer"
          >
            <span v-if="loading" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>Segarkan</span>
          </button>
        </div>
      </div>

      <!-- Filter Controls -->
      <div :class="['grid grid-cols-1 sm:grid-cols-2 gap-3 pt-4', canUseQuickActions ? 'lg:grid-cols-5' : 'lg:grid-cols-3']">
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Aplikasi</label>
          <select
            v-model="filters.application_id"
            @change="handleAppChange"
            class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
          >
            <option value="">Semua Aplikasi</option>
            <option v-for="app in appOptions" :key="app.id" :value="app.id">{{ app.name }}</option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Menu Utama / Modul</label>
          <select
            v-model="filters.module_id"
            @change="handleModuleChange"
            class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
          >
            <option value="">Semua Modul</option>
            <option v-for="mod in moduleOptions" :key="mod.id" :value="mod.id">{{ mod.name }}</option>
          </select>
        </div>

        <div v-if="canUseQuickActions">
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Sub-Menu / Fitur</label>
          <select
            v-model="filters.menu_id"
            @change="fetchDashboardData"
            class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
          >
            <option value="">Semua Sub-Menu</option>
            <option v-for="menu in menuOptions" :key="menu.id" :value="menu.id">{{ menu.name }}</option>
          </select>
        </div>

        <div v-if="canViewAllData">
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Tester</label>
          <select
            v-model="filters.tester_id"
            @change="fetchDashboardData"
            class="w-full text-xs py-2 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
          >
            <option value="">Semua Tester</option>
            <option v-for="user in userOptions" :key="user.id" :value="user.id">{{ user.name }}</option>
          </select>
        </div>
        <div v-else>
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Cakupan Data Tester</label>
          <div class="w-full text-xs py-2 px-3 rounded-xl border border-amber-200 bg-amber-50/60 text-amber-800 font-bold flex items-center justify-between">
            <span class="truncate">🔒 Khusus Akun: {{ currentUser?.name }}</span>
          </div>
        </div>

        <div v-if="canUseQuickActions">
          <label class="block text-[11px] font-bold text-slate-600 mb-1">Tanggal Mulai</label>
          <input
            type="date"
            v-model="filters.start_date"
            @change="fetchDashboardData"
            class="w-full text-xs py-1.5 px-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <LoadingSpinner v-if="loading && !metrics" text="Menghitung statistik QA aktual..." />

    <!-- ==================== KPI & MODERN FLUID CHARTS ==================== -->
    <div v-else-if="metrics" class="space-y-6">
      <!-- Primary KPI Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col justify-between">
          <div class="flex items-center justify-between text-slate-500 text-xs font-bold">
            <span>Total Test Case</span>
            <span class="w-7 h-7 rounded-xl bg-slate-100 flex items-center justify-center text-sm">📝</span>
          </div>
          <div class="mt-3 flex items-baseline justify-between">
            <span class="text-2xl font-black text-slate-900">{{ metrics.total_test_cases }}</span>
            <span class="text-[10px] text-slate-400 font-bold uppercase">Skenario</span>
          </div>
          <div class="w-full bg-slate-100 h-1.5 rounded-full mt-2.5 overflow-hidden">
            <div class="bg-slate-700 h-1.5 rounded-full w-full"></div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-emerald-200/80 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col justify-between bg-gradient-to-b from-emerald-50/40 to-white">
          <div class="flex items-center justify-between text-emerald-800 text-xs font-extrabold">
            <span>PASS</span>
            <span class="w-7 h-7 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-black">✓</span>
          </div>
          <div class="mt-3 flex items-baseline justify-between">
            <span class="text-2xl font-black text-emerald-700">{{ metrics.pass }}</span>
            <span class="text-[10px] text-emerald-600 font-bold">Lulus Uji</span>
          </div>
          <div class="w-full bg-emerald-100 h-1.5 rounded-full mt-2.5 overflow-hidden">
            <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-700" :style="{ width: `${metrics.total_test_cases ? (metrics.pass / metrics.total_test_cases) * 100 : 0}%` }"></div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-red-200/80 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col justify-between bg-gradient-to-b from-red-50/40 to-white">
          <div class="flex items-center justify-between text-red-800 text-xs font-extrabold">
            <span>FAILED</span>
            <span class="w-7 h-7 rounded-xl bg-red-100 text-red-700 flex items-center justify-center text-xs font-black">✕</span>
          </div>
          <div class="mt-3 flex items-baseline justify-between">
            <span class="text-2xl font-black text-red-700">{{ metrics.failed }}</span>
            <span class="text-[10px] text-red-600 font-bold">Gagal Uji</span>
          </div>
          <div class="w-full bg-red-100 h-1.5 rounded-full mt-2.5 overflow-hidden">
            <div class="bg-red-500 h-1.5 rounded-full transition-all duration-700" :style="{ width: `${metrics.total_test_cases ? (metrics.failed / metrics.total_test_cases) * 100 : 0}%` }"></div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-amber-200/80 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col justify-between bg-gradient-to-b from-amber-50/40 to-white">
          <div class="flex items-center justify-between text-amber-800 text-xs font-extrabold">
            <span>PENDING</span>
            <span class="w-7 h-7 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-black">⏳</span>
          </div>
          <div class="mt-3 flex items-baseline justify-between">
            <span class="text-2xl font-black text-amber-700">{{ metrics.pending }}</span>
            <span class="text-[10px] text-amber-600 font-bold">Tertunda</span>
          </div>
          <div class="w-full bg-amber-100 h-1.5 rounded-full mt-2.5 overflow-hidden">
            <div class="bg-amber-500 h-1.5 rounded-full transition-all duration-700" :style="{ width: `${metrics.total_test_cases ? (metrics.pending / metrics.total_test_cases) * 100 : 0}%` }"></div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col justify-between bg-gradient-to-b from-slate-50/60 to-white">
          <div class="flex items-center justify-between text-slate-600 text-xs font-extrabold">
            <span>NOT TESTED</span>
            <span class="w-7 h-7 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center text-xs font-black">—</span>
          </div>
          <div class="mt-3 flex items-baseline justify-between">
            <span class="text-2xl font-black text-slate-700">{{ metrics.not_tested }}</span>
            <span class="text-[10px] text-slate-500 font-bold">Belum Diuji</span>
          </div>
          <div class="w-full bg-slate-100 h-1.5 rounded-full mt-2.5 overflow-hidden">
            <div class="bg-slate-400 h-1.5 rounded-full transition-all duration-700" :style="{ width: `${metrics.total_test_cases ? (metrics.not_tested / metrics.total_test_cases) * 100 : 0}%` }"></div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-blue-600 via-indigo-600 to-sky-600 rounded-2xl p-4 border border-blue-500 shadow-lg shadow-blue-600/20 hover:-translate-y-0.5 transition-all flex flex-col justify-between text-white">
          <div class="flex items-center justify-between text-blue-100 text-xs font-bold">
            <span>Success Rate</span>
            <span class="w-7 h-7 rounded-xl bg-white/15 text-white flex items-center justify-center text-xs font-black">%</span>
          </div>
          <div class="mt-3 flex items-baseline justify-between">
            <span class="text-2xl font-black text-white">{{ metrics.success_rate }}%</span>
            <span class="text-[10px] text-blue-100 font-bold">Pass Rate</span>
          </div>
          <div class="w-full bg-white/20 h-1.5 rounded-full mt-2.5 overflow-hidden">
            <div class="bg-emerald-300 h-1.5 rounded-full transition-all duration-700" :style="{ width: `${metrics.success_rate || 0}%` }"></div>
          </div>
        </div>
      </div>

      <!-- Secondary Metric Cards (Filtered by Role Menu & Feature Access) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div v-if="hasMenuAccess('/bugs')" class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center space-x-3.5">
          <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-rose-50 to-red-100 text-red-600 flex items-center justify-center font-bold text-lg shrink-0 border border-red-200/60">
            🐞
          </div>
          <div>
            <div class="text-[11px] font-semibold text-slate-500">{{ canViewAllData ? 'Bug Aktif (Open)' : 'Laporan Bug Saya' }}</div>
            <div class="text-lg font-black text-slate-900">{{ metrics.total_active_bugs }} Tiket</div>
          </div>
        </div>

        <div v-if="canViewExecCharts && hasMenuAccess('/bugs')" class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center space-x-3.5">
          <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-amber-50 to-orange-100 text-amber-600 flex items-center justify-center font-bold text-lg shrink-0 border border-amber-200/60">
            ⚠️
          </div>
          <div>
            <div class="text-[11px] font-semibold text-slate-500">Critical &amp; Major</div>
            <div class="text-lg font-black text-red-600">{{ metrics.critical_major_bugs }} Tiket</div>
          </div>
        </div>

        <div v-if="hasMenuAccess('/schedules')" class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center space-x-3.5">
          <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-50 to-blue-100 text-indigo-600 flex items-center justify-center font-bold text-lg shrink-0 border border-indigo-200/60">
            📅
          </div>
          <div>
            <div class="text-[11px] font-semibold text-slate-500">{{ canViewAllData ? 'Jadwal Aktif / Overdue' : 'Jadwal QA Saya' }}</div>
            <div class="text-lg font-black text-slate-900">{{ metrics.active_schedules }} / <span class="text-red-600">{{ metrics.overdue_schedules }}</span></div>
          </div>
        </div>

        <div v-if="hasMenuAccess('/retests')" class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center space-x-3.5">
          <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-100 text-emerald-600 flex items-center justify-center font-bold text-lg shrink-0 border border-emerald-200/60">
            🔄
          </div>
          <div>
            <div class="text-[11px] font-semibold text-slate-500">Menunggu Retesting</div>
            <div class="text-lg font-black text-slate-900">{{ metrics.waiting_retests }} Bug</div>
          </div>
        </div>
      </div>

      <!-- ==================== MODERN FLUID CHARTS GRID ==================== -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Chart 1: Modern Ring Gauge with Center KPI & Interactive Status Breakdown -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs hover:shadow-md transition-all">
          <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-2.5">
              <span class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center text-sm">🎯</span>
              <div>
                <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Distribusi Status Pengujian</h3>
                <p class="text-[11px] text-slate-400">Komposisi hasil eksekusi mutakhir per Test Case</p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-extrabold">
              {{ metrics.success_rate }}% Lulus
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-12 gap-5 items-center">
            <!-- Ring Gauge with Center Overlay -->
            <div class="sm:col-span-6 relative h-56 flex items-center justify-center">
              <Doughnut v-if="statusChartData" :data="statusChartData" :options="doughnutModernOptions" />
              <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Pass Rate</span>
                <span class="text-2xl font-black text-slate-900 mt-0.5">{{ metrics.success_rate }}%</span>
                <span class="text-[10px] font-semibold text-slate-500">{{ metrics.total_test_cases }} Skenario</span>
              </div>
            </div>

            <!-- Interactive Status Progress Breakdown -->
            <div class="sm:col-span-6 space-y-3">
              <div
                v-for="item in statusBreakdownList"
                :key="item.label"
                class="p-2.5 rounded-2xl bg-slate-50/80 border border-slate-100 hover:bg-slate-50 transition-colors"
              >
                <div class="flex items-center justify-between text-xs mb-1.5">
                  <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: item.color }"></span>
                    <span class="font-bold text-slate-700">{{ item.label }}</span>
                  </div>
                  <div class="font-black text-slate-900">
                    {{ item.count }} <span class="text-[10px] font-semibold text-slate-400">({{ item.percent }}%)</span>
                  </div>
                </div>
                <div class="w-full bg-slate-200/70 h-1.5 rounded-full overflow-hidden">
                  <div
                    class="h-1.5 rounded-full transition-all duration-700"
                    :style="{ width: `${item.percent}%`, backgroundColor: item.color }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Role User Restricted Info Card when Executive Charts are disabled -->
        <div
          v-if="!canViewExecCharts"
          class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between"
        >
          <div>
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
              <div class="flex items-center space-x-2.5">
                <span class="w-8 h-8 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center text-sm">🔒</span>
                <div>
                  <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Cakupan Akses Dashboard Role User</h3>
                  <p class="text-[11px] text-slate-400">Mode tampilan operasional khusus QA Tester</p>
                </div>
              </div>
              <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-extrabold">
                Akses Terbatas
              </span>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed mb-4">
              Pada akun <strong>Role User</strong>, Dashboard hanya menampilkan ringkasan skenario pengujian milik Anda sendiri. Grafik analitik eksekutif (Kurva Keparahan Bug, Performa Lintas Aplikasi, dan Siklus Resolusi Bug) serta filter lintas tester dikhususkan bagi <strong>Administrator</strong>.
            </p>

            <div class="space-y-2 text-xs">
              <div class="font-extrabold text-slate-700 text-[11px] uppercase tracking-wider mb-2">
                📌 Menu Kerja Anda yang Aktif Saat Ini:
              </div>
              <div class="flex flex-wrap gap-2">
                <router-link
                  v-if="hasMenuAccess('/schedules')"
                  to="/schedules"
                  class="px-3 py-2 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 text-slate-700 hover:text-blue-700 font-bold transition-all"
                >
                  📅 Jadwal QA Saya
                </router-link>
                <router-link
                  v-if="hasMenuAccess('/test-cases')"
                  to="/test-cases"
                  class="px-3 py-2 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 text-slate-700 hover:text-blue-700 font-bold transition-all"
                >
                  📝 Test Management
                </router-link>
                <router-link
                  v-if="hasMenuAccess('/bugs')"
                  to="/bugs"
                  class="px-3 py-2 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 text-slate-700 hover:text-blue-700 font-bold transition-all"
                >
                  🐞 Bug Tracking
                </router-link>
                <router-link
                  v-if="hasMenuAccess('/retests')"
                  to="/retests"
                  class="px-3 py-2 rounded-xl bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 text-slate-700 hover:text-blue-700 font-bold transition-all"
                >
                  🔄 Retesting
                </router-link>
              </div>
            </div>
          </div>

          <div class="mt-5 pt-3 border-t border-slate-100 text-[11px] text-slate-400 flex items-center justify-between">
            <span>Diatur melalui matriks hak akses Role User</span>
            <span class="font-semibold text-slate-600">User: {{ currentUser?.name }}</span>
          </div>
        </div>

        <!-- Chart 2: Smooth Spline Area / Modern Capsule for Bug Severity (Admin / Permitted Only) -->
        <div v-if="canViewExecCharts" class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs hover:shadow-md transition-all">
          <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-2.5">
              <span class="w-8 h-8 rounded-xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center text-sm">🔥</span>
              <div>
                <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Kurva Tingkat Keparahan Bug (Severity)</h3>
                <p class="text-[11px] text-slate-400">Distribusi intensitas dampak bug dari Critical hingga Trivial</p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-extrabold">
              {{ metrics.critical_major_bugs }} Prioritas Tinggi
            </span>
          </div>

          <div class="h-52">
            <Line v-if="chartStyle === 'wave' && severityWaveData" :data="severityWaveData" :options="smoothWaveOptions" />
            <Bar v-else-if="severityChartData" :data="severityChartData" :options="modernCapsuleOptions" />
          </div>

          <!-- Severity Mini Pills -->
          <div v-if="charts?.bugs_by_severity" class="grid grid-cols-4 gap-2 mt-4 pt-3 border-t border-slate-100">
            <div
              v-for="(label, idx) in charts.bugs_by_severity.labels"
              :key="label"
              class="px-2.5 py-1.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-[11px]"
            >
              <span class="font-semibold text-slate-600 truncate">{{ label }}</span>
              <span class="font-black text-slate-900">{{ charts.bugs_by_severity.counts[idx] || 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Chart 3: Smooth Dual-Wave Area / Capsule for Cakupan Pengujian per Aplikasi (Admin / Permitted Only) -->
        <div v-if="canViewExecCharts" class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs hover:shadow-md transition-all">
          <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-2.5">
              <span class="w-8 h-8 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center text-sm">📈</span>
              <div>
                <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Performa &amp; Cakupan Pengujian per Aplikasi</h3>
                <p class="text-[11px] text-slate-400">Perbandingan kurva Total Skenario terhadap kelulusan (PASS)</p>
              </div>
            </div>
            <div class="flex items-center space-x-3 text-[11px] font-bold">
              <span class="inline-flex items-center space-x-1 text-blue-600">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                <span>Total TC</span>
              </span>
              <span class="inline-flex items-center space-x-1 text-emerald-600">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span>PASS</span>
              </span>
            </div>
          </div>

          <div class="h-60">
            <Line v-if="chartStyle === 'wave' && appWaveData" :data="appWaveData" :options="dualWaveOptions" />
            <Bar v-else-if="appChartData" :data="appChartData" :options="groupedCapsuleOptions" />
          </div>
        </div>

        <!-- Chart 4: Smooth Lifecycle Flow Curve for Bug Status Resolution (Admin / Permitted Only) -->
        <div v-if="canViewExecCharts" class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs hover:shadow-md transition-all">
          <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-2.5">
              <span class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-sm">🔄</span>
              <div>
                <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Alur Siklus Penyelesaian Bug</h3>
                <p class="text-[11px] text-slate-400">Progres penanganan tiket dari Open hingga Closed</p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-extrabold">
              Siklus Resolusi
            </span>
          </div>

          <div class="h-60">
            <Line v-if="chartStyle === 'wave' && bugStatusWaveData" :data="bugStatusWaveData" :options="smoothWaveOptions" />
            <Bar v-else-if="bugStatusChartData" :data="bugStatusChartData" :options="modernCapsuleOptions" />
          </div>
        </div>
      </div>

      <!-- Explanatory Note Box -->
      <div class="bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-sky-50/80 rounded-2xl p-4 border border-blue-100 text-xs text-blue-900 flex items-start space-x-3">
        <span class="text-blue-600 text-base mt-0.5 shrink-0">ℹ️</span>
        <div class="space-y-1">
          <div class="font-bold">Ketentuan &amp; Definisi Perhitungan Metrik QA SISQA:</div>
          <ul class="list-disc list-inside space-y-0.5 text-blue-800 text-[11px]">
            <li>{{ notes.success_rate }}</li>
            <li>{{ notes.latest_execution }}</li>
            <li>{{ notes.bugs_uniqueness }}</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import api from '../api';
import { useAuth } from '../auth';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement,
  PointElement,
  LineElement,
  Filler,
} from 'chart.js';
import { Doughnut, Bar, Line } from 'vue-chartjs';

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement,
  PointElement,
  LineElement,
  Filler
);

const { currentUser, isAdmin, hasMenuAccess, hasFeatureAccess } = useAuth();
const canViewAllData = computed(() => isAdmin.value || hasFeatureAccess('dashboard_all_data'));
const canViewExecCharts = computed(() => isAdmin.value || hasFeatureAccess('dashboard_exec_charts'));
const canUseQuickActions = computed(() => isAdmin.value || hasFeatureAccess('dashboard_quick_actions'));

const loading = ref(true);
const chartStyle = ref('wave'); // 'wave' (Kurva Modern) or 'capsule' (Kapsul Halus)
const metrics = ref(null);
const notes = ref({});
const charts = ref({});

const appOptions = ref([]);
const moduleOptions = ref([]);
const menuOptions = ref([]);
const userOptions = ref([]);

const filters = reactive({
  application_id: '',
  module_id: '',
  menu_id: '',
  tester_id: '',
  start_date: '',
  end_date: '',
});

// Modern Tooltip & Axis Styling
const commonTooltip = {
  backgroundColor: 'rgba(15, 23, 42, 0.92)',
  titleColor: '#f8fafc',
  bodyColor: '#e2e8f0',
  borderColor: 'rgba(56, 189, 248, 0.3)',
  borderWidth: 1,
  padding: 12,
  cornerRadius: 12,
  displayColors: true,
  boxPadding: 5,
};

const doughnutModernOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '74%',
  plugins: {
    legend: { display: false },
    tooltip: commonTooltip,
  },
  animation: {
    animateScale: true,
    animateRotate: true,
  },
};

const smoothWaveOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: { display: false },
    tooltip: commonTooltip,
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: '#64748b', font: { size: 11, weight: '600' } },
      border: { display: false },
    },
    y: {
      beginAtZero: true,
      grid: { color: 'rgba(226, 232, 240, 0.6)', drawTicks: false },
      ticks: { precision: 0, color: '#94a3b8', font: { size: 10 }, padding: 8 },
      border: { display: false, dash: [4, 4] },
    },
  },
};

const dualWaveOptions = {
  ...smoothWaveOptions,
  plugins: {
    legend: { display: false },
    tooltip: commonTooltip,
  },
};

const modernCapsuleOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: commonTooltip,
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: '#64748b', font: { size: 11, weight: '600' } },
      border: { display: false },
    },
    y: {
      beginAtZero: true,
      grid: { color: 'rgba(226, 232, 240, 0.6)' },
      ticks: { precision: 0, color: '#94a3b8', font: { size: 10 } },
      border: { display: false },
    },
  },
};

const groupedCapsuleOptions = {
  ...modernCapsuleOptions,
  plugins: {
    legend: { display: false },
    tooltip: commonTooltip,
  },
};

const statusBreakdownList = computed(() => {
  if (!charts.value?.status_distribution) return [];
  const { labels, counts, colors } = charts.value.status_distribution;
  const total = counts.reduce((acc, v) => acc + Number(v || 0), 0);
  return labels.map((label, idx) => {
    const count = Number(counts[idx] || 0);
    const percent = total > 0 ? Math.round((count / total) * 100) : 0;
    return {
      label,
      count,
      percent,
      color: colors[idx] || '#3b82f6',
    };
  });
});

const statusChartData = computed(() => {
  if (!charts.value?.status_distribution) return null;
  return {
    labels: charts.value.status_distribution.labels,
    datasets: [
      {
        data: charts.value.status_distribution.counts,
        backgroundColor: charts.value.status_distribution.colors,
        borderWidth: 0,
        borderRadius: 10,
        spacing: 4,
        hoverOffset: 6,
      },
    ],
  };
});

// Smooth Wave & Capsule Data for Bug Severity
const severityWaveData = computed(() => {
  if (!charts.value?.bugs_by_severity) return null;
  return {
    labels: charts.value.bugs_by_severity.labels,
    datasets: [
      {
        label: 'Jumlah Bug',
        data: charts.value.bugs_by_severity.counts,
        borderColor: '#f43f5e',
        backgroundColor: (ctx) => {
          const canvas = ctx.chart.ctx;
          const grad = canvas.createLinearGradient(0, 0, 0, 220);
          grad.addColorStop(0, 'rgba(244, 63, 94, 0.35)');
          grad.addColorStop(1, 'rgba(244, 63, 94, 0.01)');
          return grad;
        },
        fill: true,
        tension: 0.45,
        borderWidth: 3,
        pointBackgroundColor: charts.value.bugs_by_severity.colors || '#f43f5e',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 7,
      },
    ],
  };
});

const severityChartData = computed(() => {
  if (!charts.value?.bugs_by_severity) return null;
  return {
    labels: charts.value.bugs_by_severity.labels,
    datasets: [
      {
        label: 'Jumlah Bug',
        data: charts.value.bugs_by_severity.counts,
        backgroundColor: charts.value.bugs_by_severity.colors,
        borderRadius: 999,
        borderSkipped: false,
        maxBarThickness: 32,
      },
    ],
  };
});

// Smooth Dual-Wave & Capsule Data for Application Coverage
const appWaveData = computed(() => {
  if (!charts.value?.testing_per_app) return null;
  return {
    labels: charts.value.testing_per_app.labels,
    datasets: [
      {
        label: 'Total Test Case',
        data: charts.value.testing_per_app.total,
        borderColor: '#3b82f6',
        backgroundColor: (ctx) => {
          const canvas = ctx.chart.ctx;
          const grad = canvas.createLinearGradient(0, 0, 0, 240);
          grad.addColorStop(0, 'rgba(59, 130, 246, 0.28)');
          grad.addColorStop(1, 'rgba(59, 130, 246, 0.01)');
          return grad;
        },
        fill: true,
        tension: 0.44,
        borderWidth: 3,
        pointBackgroundColor: '#3b82f6',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 7,
      },
      {
        label: 'PASS (Lulus Uji)',
        data: charts.value.testing_per_app.passed,
        borderColor: '#10b981',
        backgroundColor: (ctx) => {
          const canvas = ctx.chart.ctx;
          const grad = canvas.createLinearGradient(0, 0, 0, 240);
          grad.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
          grad.addColorStop(1, 'rgba(16, 185, 129, 0.01)');
          return grad;
        },
        fill: true,
        tension: 0.44,
        borderWidth: 3,
        pointBackgroundColor: '#10b981',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 7,
      },
    ],
  };
});

const appChartData = computed(() => {
  if (!charts.value?.testing_per_app) return null;
  return {
    labels: charts.value.testing_per_app.labels,
    datasets: [
      {
        label: 'Total Test Case',
        data: charts.value.testing_per_app.total,
        backgroundColor: '#3b82f6',
        borderRadius: 999,
        borderSkipped: false,
        maxBarThickness: 24,
      },
      {
        label: 'PASS',
        data: charts.value.testing_per_app.passed,
        backgroundColor: '#10b981',
        borderRadius: 999,
        borderSkipped: false,
        maxBarThickness: 24,
      },
    ],
  };
});

// Smooth Wave & Capsule Data for Bug Resolution Status
const bugStatusWaveData = computed(() => {
  if (!charts.value?.bugs_by_status) return null;
  return {
    labels: charts.value.bugs_by_status.labels,
    datasets: [
      {
        label: 'Tiket Bug',
        data: charts.value.bugs_by_status.counts,
        borderColor: '#6366f1',
        backgroundColor: (ctx) => {
          const canvas = ctx.chart.ctx;
          const grad = canvas.createLinearGradient(0, 0, 0, 240);
          grad.addColorStop(0, 'rgba(99, 102, 241, 0.34)');
          grad.addColorStop(1, 'rgba(56, 189, 248, 0.02)');
          return grad;
        },
        fill: true,
        tension: 0.45,
        borderWidth: 3,
        pointBackgroundColor: charts.value.bugs_by_status.colors || '#6366f1',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 7,
      },
    ],
  };
});

const bugStatusChartData = computed(() => {
  if (!charts.value?.bugs_by_status) return null;
  return {
    labels: charts.value.bugs_by_status.labels,
    datasets: [
      {
        label: 'Tiket Bug',
        data: charts.value.bugs_by_status.counts,
        backgroundColor: charts.value.bugs_by_status.colors,
        borderRadius: 999,
        borderSkipped: false,
        maxBarThickness: 28,
      },
    ],
  };
});

const fetchCascadeApps = async () => {
  try {
    const res = await api.get('/cascade/apps');
    appOptions.value = res.data;
  } catch (e) {}
};

const handleAppChange = async () => {
  filters.module_id = '';
  filters.menu_id = '';
  moduleOptions.value = [];
  menuOptions.value = [];
  if (filters.application_id) {
    const res = await api.get(`/cascade/modules?application_id=${filters.application_id}`);
    moduleOptions.value = res.data;
  }
  fetchDashboardData();
};

const handleModuleChange = async () => {
  filters.menu_id = '';
  menuOptions.value = [];
  if (filters.module_id) {
    const res = await api.get(`/cascade/menus?module_id=${filters.module_id}`);
    menuOptions.value = res.data;
  }
  fetchDashboardData();
};

const fetchUsers = async () => {
  try {
    const res = await api.get('/cascade/users');
    userOptions.value = res.data;
  } catch (e) {}
};

const fetchDashboardData = async () => {
  loading.value = true;
  try {
    const res = await api.get('/dashboard', { params: filters });
    metrics.value = res.data.metrics;
    notes.value = res.data.calculation_notes;
    charts.value = res.data.charts;
  } catch (e) {
    console.error('Failed to load dashboard:', e);
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  filters.application_id = '';
  filters.module_id = '';
  filters.menu_id = '';
  filters.tester_id = '';
  filters.start_date = '';
  filters.end_date = '';
  moduleOptions.value = [];
  menuOptions.value = [];
  fetchDashboardData();
};

onMounted(() => {
  fetchCascadeApps();
  fetchUsers();
  fetchDashboardData();
});
</script>

<style scoped>
/* Animated Office Building Skyline inside Dashboard Hero */
.dash-win-1 {
  animation: dashWindowGlow 5s ease-in-out infinite;
}
.dash-win-2 {
  animation: dashWindowGlow 7s ease-in-out 1.5s infinite;
}
.dash-win-3 {
  animation: dashWindowGlow 6s ease-in-out 3s infinite;
}
@keyframes dashWindowGlow {
  0%, 100% { opacity: 0.85; }
  50% { opacity: 0.2; }
}

.dash-elevator-1 {
  animation: dashElev1 9s ease-in-out infinite alternate;
}
.dash-elevator-2 {
  animation: dashElev2 11s ease-in-out infinite alternate;
}
@keyframes dashElev1 {
  0% { transform: translateY(0px); }
  100% { transform: translateY(135px); }
}
@keyframes dashElev2 {
  0% { transform: translateY(70px); }
  100% { transform: translateY(-65px); }
}

.dash-beacon {
  animation: dashBeaconBlink 1.8s ease-in-out infinite;
}
@keyframes dashBeaconBlink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.2; }
}

.dash-radar {
  transform-origin: 855px 18px;
  animation: dashRadarPulse 3s ease-out infinite;
}
@keyframes dashRadarPulse {
  0% { transform: scale(0.3); opacity: 0.9; }
  100% { transform: scale(2.6); opacity: 0; }
}

.dash-beam-left {
  transform-origin: 380px 250px;
  animation: dashSweepLeft 10s ease-in-out infinite alternate;
}
.dash-beam-right {
  transform-origin: 1180px 250px;
  animation: dashSweepRight 12s ease-in-out infinite alternate;
}
@keyframes dashSweepLeft {
  0% { transform: rotate(-8deg); }
  100% { transform: rotate(9deg); }
}
@keyframes dashSweepRight {
  0% { transform: rotate(8deg); }
  100% { transform: rotate(-9deg); }
}

.dash-traffic-right {
  animation: dashDriveRight 10s linear infinite;
}
.dash-traffic-left {
  animation: dashDriveLeft 11s linear infinite;
}
@keyframes dashDriveRight {
  0% { transform: translateX(-200px); }
  100% { transform: translateX(1600px); }
}
@keyframes dashDriveLeft {
  0% { transform: translateX(300px); }
  100% { transform: translateX(-1500px); }
}
</style>

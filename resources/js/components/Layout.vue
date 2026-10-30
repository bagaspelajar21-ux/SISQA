<template>
  <div class="flex h-screen overflow-hidden bg-[#eef4fb] font-sans text-slate-800">
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div
      v-if="mobileMenuOpen"
      @click="mobileMenuOpen = false"
      class="fixed inset-0 z-40 bg-slate-950/65 backdrop-blur-xs lg:hidden transition-opacity"
    ></div>

    <!-- Modern Corporate Skyline Sidebar (Slide-over Drawer on Mobile, Fixed Sidebar on Desktop) -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-50 lg:static lg:z-30 overflow-hidden bg-gradient-to-b from-[#050b1a] via-[#0a1733] to-[#0e2248] text-slate-300 flex flex-col shrink-0 transition-all duration-300 shadow-2xl border-r border-sky-400/15',
        mobileMenuOpen ? 'translate-x-0 w-72' : '-translate-x-full lg:translate-x-0',
        sidebarCollapsed ? 'lg:w-20' : 'lg:w-68'
      ]"
    >
      <!-- Animated Office Skyline Backdrop in Sidebar -->
      <div class="absolute bottom-14 left-0 right-0 h-48 pointer-events-none z-0">
        <OfficeSkylineBg opacity-class="opacity-45" />
      </div>
      <!-- Logo & App Title -->
      <div class="relative z-10 h-16 flex items-center justify-between px-4 border-b border-sky-400/15 bg-slate-950/50 backdrop-blur-md">
        <router-link :to="firstAllowedPath" @click="mobileMenuOpen = false" class="overflow-hidden flex items-center">
          <SisqaLogo
            size="md"
            theme="dark"
            :show-text="!sidebarCollapsed || mobileMenuOpen"
            subtitle="Software QA System"
          />
        </router-link>
        <!-- Desktop Collapse Toggle -->
        <button
          @click="toggleSidebar"
          class="hidden lg:inline-flex p-2 rounded-xl text-sky-300/70 hover:text-white hover:bg-sky-500/15 border border-transparent hover:border-sky-400/25 transition-all cursor-pointer"
          :title="sidebarCollapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
          </svg>
        </button>
        <!-- Mobile Drawer Close Button -->
        <button
          @click="mobileMenuOpen = false"
          class="lg:hidden p-2 rounded-xl text-sky-300 hover:text-white hover:bg-sky-500/20 border border-sky-400/25 transition-all cursor-pointer"
          title="Tutup Menu"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Navigation Links -->
      <nav class="relative z-10 flex-1 overflow-y-auto px-3 py-4 space-y-1.5">
        <!-- Main QA Navigation -->
        <div v-if="!sidebarCollapsed" class="px-3 pb-1 text-[10px] font-extrabold uppercase tracking-widest text-sky-300/50">
          Workspace QA
        </div>

        <router-link
          v-for="item in visibleMainNavItems"
          :key="item.path"
          :to="item.path"
          :class="[
            'relative flex items-center px-2.5 py-2 rounded-2xl text-xs font-semibold transition-all group',
            $route.path === item.path
              ? 'bg-gradient-to-r from-sky-500/90 via-blue-600/90 to-indigo-600/90 text-white shadow-lg shadow-sky-500/25 border border-sky-300/30 backdrop-blur-md'
              : 'text-slate-300/85 hover:text-white hover:bg-white/8 border border-transparent hover:border-sky-400/15'
          ]"
          :title="sidebarCollapsed ? item.label : ''"
        >
          <span
            :class="[
              'w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-all',
              $route.path === item.path
                ? 'bg-white/20 text-white shadow-inner'
                : 'bg-slate-900/60 text-sky-400 border border-sky-400/15 group-hover:bg-sky-500/20 group-hover:text-sky-300 group-hover:border-sky-400/30'
            ]"
          >
            <ModernIcon :name="item.icon" size="md" />
          </span>
          <span v-if="!sidebarCollapsed" class="ml-3 truncate">{{ item.label }}</span>
          <span
            v-if="!sidebarCollapsed && $route.path === item.path"
            class="ml-auto w-2 h-2 rounded-full bg-sky-200 shadow-[0_0_8px_#bae6fd]"
          ></span>
        </router-link>

        <!-- Admin & Authorized Master Data Section -->
        <div v-if="isAdmin || visibleMasterSubItems.length > 0" class="pt-4 space-y-1.5">
          <div v-if="!sidebarCollapsed" class="px-3 pb-1 text-[10px] font-extrabold uppercase tracking-widest text-sky-300/50">
            Administrasi Sistem
          </div>

          <!-- Dropdown Parent Menu: Master Data -->
          <div v-if="visibleMasterSubItems.length > 0">
            <button
              type="button"
              @click="masterDropdownOpen = !masterDropdownOpen"
              :class="[
                'w-full flex items-center justify-between px-2.5 py-2 rounded-2xl text-xs font-semibold transition-all cursor-pointer group',
                $route.path.startsWith('/master')
                  ? 'bg-sky-500/15 text-sky-200 border border-sky-400/30 backdrop-blur-md'
                  : 'text-slate-300/85 hover:text-white hover:bg-white/8 border border-transparent hover:border-sky-400/15'
              ]"
              :title="sidebarCollapsed ? 'Master Data' : ''"
            >
              <div class="flex items-center truncate">
                <span
                  :class="[
                    'w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-all',
                    $route.path.startsWith('/master')
                      ? 'bg-sky-500/25 text-sky-300 border border-sky-400/35'
                      : 'bg-slate-900/60 text-sky-400 border border-sky-400/15 group-hover:bg-sky-500/20 group-hover:text-sky-300'
                  ]"
                >
                  <ModernIcon name="master-data" size="md" />
                </span>
                <span v-if="!sidebarCollapsed" class="ml-3 truncate">Master Data</span>
              </div>
              <svg
                v-if="!sidebarCollapsed"
                :class="['w-4 h-4 transition-transform duration-200 shrink-0', masterDropdownOpen ? 'rotate-180 text-sky-400' : 'text-slate-500']"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Sub-menu Items inside Master Data Dropdown -->
            <div
              v-show="masterDropdownOpen || sidebarCollapsed"
              :class="[
                'mt-1.5 space-y-1',
                !sidebarCollapsed ? 'pl-3 ml-4 border-l border-sky-400/20' : ''
              ]"
            >
              <router-link
                v-for="sub in visibleMasterSubItems"
                :key="sub.path"
                :to="sub.path"
                :class="[
                  'flex items-center px-2.5 py-2 rounded-xl text-xs font-semibold transition-all group',
                  $route.path === sub.path
                    ? 'bg-gradient-to-r from-sky-500/90 to-blue-600/90 text-white shadow-md shadow-sky-500/20 border border-sky-300/30'
                    : 'text-slate-300/80 hover:text-white hover:bg-white/8'
                ]"
                :title="sidebarCollapsed ? sub.label : ''"
              >
                <span
                  :class="[
                    'w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-all',
                    $route.path === sub.path
                      ? 'bg-white/20 text-white'
                      : 'bg-slate-900/50 text-sky-400/90 border border-sky-400/15 group-hover:text-sky-300'
                  ]"
                >
                  <ModernIcon :name="sub.icon" size="sm" />
                </span>
                <span v-if="!sidebarCollapsed" class="ml-2.5 truncate">{{ sub.label }}</span>
              </router-link>
            </div>
          </div>

          <!-- Other Admin Direct Links (e.g. User Management) -->
          <router-link
            v-for="item in adminNavItems"
            v-show="isAdmin"
            :key="item.path"
            :to="item.path"
            :class="[
              'flex items-center px-2.5 py-2 rounded-2xl text-xs font-semibold transition-all group',
              $route.path.startsWith(item.path)
                ? 'bg-gradient-to-r from-sky-500/90 via-blue-600/90 to-indigo-600/90 text-white shadow-md shadow-sky-500/25 border border-sky-300/30'
                : 'text-slate-300/85 hover:text-white hover:bg-white/8 border border-transparent hover:border-sky-400/15'
            ]"
            :title="sidebarCollapsed ? item.label : ''"
          >
            <span
              :class="[
                'w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-all',
                $route.path.startsWith(item.path)
                  ? 'bg-white/20 text-white'
                  : 'bg-slate-900/60 text-sky-400 border border-sky-400/15 group-hover:bg-sky-500/20 group-hover:text-sky-300'
              ]"
            >
              <ModernIcon :name="item.icon" size="md" />
            </span>
            <span v-if="!sidebarCollapsed" class="ml-3 truncate">{{ item.label }}</span>
          </router-link>
        </div>
      </nav>

      <!-- User Profile Card in Sidebar Footer -->
      <div class="relative z-10 p-3 border-t border-sky-400/15 bg-slate-950/65 backdrop-blur-md shrink-0">
        <div class="flex items-center justify-between">
          <router-link to="/profile" class="flex items-center space-x-3 truncate group">
            <div class="relative shrink-0">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 text-white font-extrabold flex items-center justify-center text-xs shadow-sm ring-1 ring-white/20">
                {{ userInitials }}
              </div>
              <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400 ring-2 ring-slate-950"></span>
            </div>
            <div v-if="!sidebarCollapsed" class="truncate text-left">
              <div class="text-xs font-bold text-white truncate group-hover:text-sky-300 transition-colors">
                {{ currentUser?.name }}
              </div>
              <div class="text-[10px] text-sky-200/60 capitalize flex items-center gap-1">
                <span>{{ currentUser?.role }}</span>
                <span>• Aktif</span>
              </div>
            </div>
          </router-link>
          <button
            v-if="!sidebarCollapsed"
            @click="handleLogout"
            class="text-slate-400 hover:text-rose-400 p-2 rounded-xl hover:bg-rose-500/15 transition-colors cursor-pointer"
            title="Keluar"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-gradient-to-br from-[#eef4fb] via-[#f4f8ff] to-[#e6f0fd]">
      <!-- Top Skyline Glassmorphism Navigation Header -->
      <header class="h-16 bg-gradient-to-r from-[#060e22]/95 via-[#0b1a3a]/95 to-[#0f234c]/95 backdrop-blur-xl border-b border-sky-400/20 px-3 sm:px-6 flex items-center justify-between shrink-0 shadow-md z-20 text-white">
        <!-- Breadcrumb / Page Title + Mobile Menu Toggle -->
        <div class="flex items-center space-x-2.5 sm:space-x-3.5 min-w-0">
          <!-- Mobile Hamburger Drawer Toggle -->
          <button
            @click="mobileMenuOpen = true"
            class="lg:hidden w-10 h-10 rounded-2xl bg-sky-500/20 hover:bg-sky-500/30 border border-sky-400/35 text-sky-200 flex items-center justify-center shrink-0 transition-all cursor-pointer"
            title="Buka Menu Navigasi"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <div class="hidden sm:flex w-10 h-10 rounded-2xl bg-sky-500/15 border border-sky-400/30 text-sky-300 items-center justify-center shrink-0 shadow-inner">
            <ModernIcon :name="currentHeaderIcon" size="md" />
          </div>
          <div class="min-w-0">
            <div class="flex items-center space-x-1.5 text-[10px] font-bold uppercase tracking-wider text-sky-300/70 truncate">
              <span class="hidden sm:inline">SISQA Corporate</span>
              <span class="hidden sm:inline">/</span>
              <span class="text-sky-300 truncate">{{ currentMenuName }}</span>
            </div>
            <h1 class="text-xs sm:text-sm font-extrabold text-white leading-tight tracking-tight truncate">
              {{ currentTitle }}
            </h1>
          </div>
        </div>

        <!-- Right Header Items -->
        <div class="flex items-center space-x-1.5 sm:space-x-2.5 shrink-0">
          <!-- Compact User Guide Button for Current Menu -->
          <button
            @click="openGuideModal"
            class="px-2.5 sm:px-3.5 py-1.5 rounded-xl bg-sky-500/20 hover:bg-sky-500/30 border border-sky-400/35 text-sky-100 text-xs font-bold shadow-xs transition-all flex items-center space-x-1.5 cursor-pointer backdrop-blur-md"
            title="Klik untuk membuka panduan penggunaan menu halaman ini"
          >
            <ModernIcon name="guides" size="sm" class="text-sky-300" />
            <span class="hidden xs:inline">Panduan</span>
            <span class="hidden md:inline text-sky-200/80">({{ currentMenuName }})</span>
          </button>

          <!-- Role Badge -->
          <span
            :class="[
              'px-2.5 py-1 rounded-xl text-[11px] font-extrabold tracking-wide uppercase border hidden lg:inline-block backdrop-blur-md',
              isAdmin
                ? 'bg-purple-500/20 text-purple-200 border-purple-400/35'
                : 'bg-sky-500/20 text-sky-200 border-sky-400/35'
            ]"
          >
            {{ currentUser?.role }}
          </span>

          <!-- Profile Link -->
          <router-link
            to="/profile"
            class="px-2.5 sm:px-3 py-1.5 rounded-xl border border-white/15 hover:border-sky-400/40 bg-white/10 hover:bg-white/15 text-xs font-semibold text-white transition-all flex items-center space-x-1.5 backdrop-blur-md"
            title="Profil Saya"
          >
            <ModernIcon name="users" size="sm" class="text-sky-300" />
            <span class="hidden sm:inline">Profil</span>
          </router-link>

          <!-- Logout Button -->
          <button
            @click="handleLogout"
            class="px-2.5 sm:px-3 py-1.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 border border-rose-400/35 text-xs font-bold transition-colors flex items-center space-x-1 cursor-pointer backdrop-blur-md"
            title="Keluar"
          >
            <span>Keluar</span>
          </button>
        </div>
      </header>

      <!-- Main Body Router View -->
      <main class="relative flex-1 overflow-y-auto p-3.5 sm:p-6 pb-24 lg:pb-6 space-y-5">
        <!-- Ambient Animated Office Skyline Horizon across All Menus -->
        <div class="fixed bottom-0 left-0 right-0 h-52 pointer-events-none z-0 opacity-25">
          <OfficeSkylineBg opacity-class="opacity-50" />
        </div>
        <!-- Page Content Slot (Strictly Guarded by Active Role Menu Permission) -->
        <div class="relative">
          <slot v-if="hasMenuAccess($route.path) || $route.path === '/profile'" />
          <div
            v-else
            class="max-w-xl mx-auto mt-12 bg-white rounded-3xl p-8 border border-rose-200/80 shadow-lg text-center space-y-4"
          >
            <div class="w-14 h-14 mx-auto rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center text-2xl">
              🔒
            </div>
            <div>
              <h2 class="text-base font-black text-slate-900">Akses Menu Dinonaktifkan</h2>
              <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                Halaman menu <strong>{{ currentMenuName }}</strong> saat ini tidak diaktifkan pada pengaturan hak akses Role akun Anda oleh Administrator.
              </p>
            </div>
            <div class="pt-2">
              <router-link
                :to="firstAllowedPath"
                class="inline-flex items-center px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-xs font-extrabold text-white shadow-md shadow-blue-600/20 transition-all"
              >
                Buka Menu yang Diizinkan →
              </router-link>
            </div>
          </div>
        </div>
      </main>

      <!-- Mobile Quick Bottom Navigation Bar (< 1024px) -->
      <nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 h-16 bg-gradient-to-r from-[#060e22]/98 via-[#0b1a3a]/98 to-[#0f234c]/98 backdrop-blur-xl border-t border-sky-400/25 px-2 flex items-center justify-around shadow-[0_-8px_24px_rgba(2,6,23,0.35)]">
        <router-link
          v-for="item in mobileBottomNavItems"
          :key="item.path"
          :to="item.path"
          :class="[
            'flex flex-col items-center justify-center flex-1 py-1.5 rounded-xl transition-all',
            $route.path === item.path
              ? 'text-sky-300 font-extrabold'
              : 'text-slate-400 hover:text-white font-semibold'
          ]"
        >
          <div
            :class="[
              'w-8 h-7 rounded-xl flex items-center justify-center transition-all',
              $route.path === item.path ? 'bg-sky-500/25 text-sky-300 border border-sky-400/35' : ''
            ]"
          >
            <ModernIcon :name="item.icon" size="sm" />
          </div>
          <span class="text-[10px] mt-0.5 truncate max-w-[68px]">{{ item.shortLabel || item.label }}</span>
        </router-link>

        <!-- More Menus Drawer Trigger on Mobile Bottom Bar -->
        <button
          type="button"
          @click="mobileMenuOpen = true"
          :class="[
            'flex flex-col items-center justify-center flex-1 py-1.5 rounded-xl transition-all cursor-pointer',
            mobileMenuOpen ? 'text-sky-300 font-extrabold' : 'text-slate-400 hover:text-white font-semibold'
          ]"
        >
          <div class="w-8 h-7 rounded-xl flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </div>
          <span class="text-[10px] mt-0.5">Menu Lain</span>
        </button>
      </nav>
    </div>

    <!-- Full Contextual User Guide Modal for Current Menu -->
    <Modal
      :show="showGuideModal"
      :title="activeMenuGuide ? `Panduan Menu: ${activeMenuGuide.menu_name}` : 'Panduan Penggunaan Menu'"
      :subtitle="activeMenuGuide ? `${activeMenuGuide.title} (Kode: ${activeMenuGuide.guide_code || '-'} | Versi ${activeMenuGuide.version || '1.0'})` : ''"
      size="xl"
      @close="showGuideModal = false"
    >
      <div v-if="activeMenuGuide" class="space-y-5 text-xs">
        <div class="bg-blue-50/70 p-4 rounded-xl border border-blue-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div class="text-sm font-extrabold text-slate-900">{{ activeMenuGuide.title }}</div>
            <div class="text-slate-600 mt-0.5">Kategori: <strong>{{ activeMenuGuide.category }}</strong></div>
          </div>
          <div class="flex items-center space-x-2 shrink-0">
            <Badge :value="activeMenuGuide.status || 'Published'" />
            <Badge :value="activeMenuGuide.observation_status || 'Sudah Diamati'" />
            <button
              v-if="isAdmin && activeMenuGuide.id && activeMenuGuide.observation_status !== 'Sudah Diamati'"
              @click="markCurrentGuideObserved"
              class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs cursor-pointer"
            >
              ✓ Tandai Sudah Diamati
            </button>
          </div>
        </div>

        <!-- Tujuan & Fungsi -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
            <div class="font-bold text-slate-800 mb-1">🎯 Tujuan Menu:</div>
            <p class="text-slate-600 leading-relaxed">{{ activeMenuGuide.menu_purpose }}</p>
          </div>
          <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
            <div class="font-bold text-slate-800 mb-1">⚙️ Fungsi Utama:</div>
            <p class="text-slate-600 leading-relaxed">{{ activeMenuGuide.menu_function }}</p>
          </div>
        </div>

        <!-- Langkah-Langkah Penggunaan -->
        <div>
          <h4 class="font-extrabold text-slate-900 uppercase tracking-wider text-[11px] mb-2.5">
            📋 Langkah-Langkah Cara Penggunaan Menu {{ activeMenuGuide.menu_name }}:
          </h4>
          <div class="space-y-2.5">
            <div
              v-for="st in activeMenuGuide.steps"
              :key="st.step_number"
              class="p-3.5 rounded-xl border border-slate-200 bg-white flex items-start space-x-3 shadow-2xs"
            >
              <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs shrink-0 mt-0.5">
                {{ st.step_number }}
              </span>
              <div class="space-y-1">
                <div class="font-bold text-slate-900 text-xs">{{ st.title }}</div>
                <div class="text-slate-600 leading-relaxed">{{ st.description }}</div>
                <div v-if="st.notes" class="text-[11px] text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg inline-block mt-1">
                  📌 <strong>Catatan:</strong> {{ st.notes }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tips & Troubleshooting -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div v-if="activeMenuGuide.tips" class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200 text-amber-900">
            <div class="font-bold mb-1">💡 Tips Penggunaan Efektif:</div>
            <p class="text-amber-800 leading-relaxed">{{ activeMenuGuide.tips }}</p>
          </div>
          <div v-if="activeMenuGuide.troubleshooting" class="p-3.5 rounded-xl bg-red-50/70 border border-red-200 text-red-900">
            <div class="font-bold mb-1">🔧 Solusi Masalah Umum (Troubleshooting):</div>
            <p class="text-red-800 leading-relaxed">{{ activeMenuGuide.troubleshooting }}</p>
          </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <router-link
            v-if="hasMenuAccess('/guides')"
            to="/guides"
            @click="showGuideModal = false"
            class="text-blue-600 hover:underline font-bold"
          >
            Lihat Seluruh Daftar Panduan SISQA →
          </router-link>
          <span v-else></span>
          <button
            @click="showGuideModal = false"
            class="px-4 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold cursor-pointer"
          >
            Mengerti & Tutup
          </button>
        </div>
      </div>
    </Modal>

    <!-- Modern Logout Confirmation Modal -->
    <ConfirmDialog
      :show="showLogoutConfirm"
      title="Konfirmasi Keluar dari SISQA"
      message="Apakah Anda yakin ingin mengakhiri sesi kerja saat ini dan keluar dari aplikasi SISQA?"
      confirmText="Ya, Keluar Sekarang"
      cancelText="Tetap di Aplikasi"
      :isDanger="true"
      @confirm="executeLogout"
      @cancel="showLogoutConfirm = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '../auth';
import api from '../api';
import Modal from './Modal.vue';
import ConfirmDialog from './ConfirmDialog.vue';
import Badge from './Badge.vue';
import SisqaLogo from './SisqaLogo.vue';
import OfficeSkylineBg from './OfficeSkylineBg.vue';
import ModernIcon from './ModernIcon.vue';

const route = useRoute();
const router = useRouter();
const { currentUser, isAdmin, hasMenuAccess, getFirstAllowedPath, checkAuth, logout } = useAuth();

const firstAllowedPath = computed(() => getFirstAllowedPath());

const sidebarCollapsed = ref(false);
const mobileMenuOpen = ref(false);
const masterDropdownOpen = ref(true);
const showGuideModal = ref(false);
const showLogoutConfirm = ref(false);
const dbGuide = ref(null);

const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value;
};

const userInitials = computed(() => {
  const name = currentUser.value?.name || 'QA';
  return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
});

const currentTitle = computed(() => {
  return route.meta?.title || 'Dashboard SISQA';
});

const mainNavItems = [
  { path: '/', label: 'Dashboard QA', shortLabel: 'Dashboard', icon: 'dashboard' },
  { path: '/schedules', label: 'Jadwal QA', shortLabel: 'Jadwal', icon: 'schedules' },
  { path: '/test-cases', label: 'Test Management', shortLabel: 'Test Case', icon: 'test-cases' },
  { path: '/bugs', label: 'Bug Tracking', shortLabel: 'Bug', icon: 'bugs' },
  { path: '/retests', label: 'Retesting', shortLabel: 'Retest', icon: 'retests' },
  { path: '/flows', label: 'Flow Aplikasi', shortLabel: 'Flow', icon: 'flows' },
  { path: '/guides', label: 'Panduan SISQA', shortLabel: 'Panduan', icon: 'guides' },
  { path: '/reports', label: 'QA Report', shortLabel: 'Report', icon: 'reports' },
  { path: '/reports/wa', label: 'Report WA', shortLabel: 'Report WA', icon: 'wa-report' },
  { path: '/activity-logs', label: 'Activity Logs', shortLabel: 'Log', icon: 'activity-logs' },
];

const masterSubItems = [
  { path: '/master/apps', label: 'Master Aplikasi', icon: 'master-apps' },
  { path: '/master/modules', label: 'Master Modul (Menu Utama)', icon: 'master-modules' },
  { path: '/master/menus', label: 'Master Sub-Menu / Fitur', icon: 'master-menus' },
];

const adminNavItems = [
  { path: '/users', label: 'Manajemen Pengguna', icon: 'users' },
];

const currentHeaderIcon = computed(() => {
  const all = [...mainNavItems, ...masterSubItems, ...adminNavItems];
  const found = all.find((i) => i.path === route.path);
  return found ? found.icon : 'dashboard';
});

const visibleMainNavItems = computed(() => {
  return mainNavItems.filter((item) => hasMenuAccess(item.path));
});

const mobileBottomNavItems = computed(() => {
  return visibleMainNavItems.value.slice(0, 4);
});

const visibleMasterSubItems = computed(() => {
  return masterSubItems.filter((item) => hasMenuAccess(item.path));
});

// Built-in default guides per route path so every menu always has instant rich instructions
const routeMenuMap = {
  '/': 'Dashboard QA',
  '/schedules': 'Jadwal QA',
  '/test-cases': 'Test Management',
  '/bugs': 'Bug Tracking',
  '/retests': 'Retesting',
  '/flows': 'Flow Aplikasi',
  '/guides': 'Panduan SISQA',
  '/reports': 'QA Report',
  '/reports/wa': 'Report WA',
  '/activity-logs': 'Activity Logs',
  '/master/apps': 'Master Aplikasi',
  '/master/modules': 'Master Modul',
  '/master/menus': 'Master Menu',
  '/users': 'Manajemen Pengguna',
  '/profile': 'Profil Saya',
};

const fallbackGuides = {
  'Dashboard QA': {
    guide_code: 'GUIDE-DASH-01',
    title: 'Panduan Membaca & Menganalisis Dashboard QA',
    menu_name: 'Dashboard QA',
    category: 'Monitoring & Analitik',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Menampilkan ringkasan kondisi kualitas perangkat lunak secara real-time dari database aktual.',
    menu_function: 'Memantau kartu statistik (PASS, FAILED, PENDING, NOT TESTED, Bug Aktif, Jadwal Terlambat), melihat grafik distribusi status, tren bug per severity, dan menyaring data lintas aplikasi.',
    tips: 'Gunakan filter Aplikasi -> Modul -> Menu secara berurutan untuk mempersempit analisis kualitas pada satu fitur spesifik.',
    troubleshooting: 'Jika grafik kosong atau persentase PASS 0%, periksa apakah filter rentang tanggal atau aplikasi yang dipilih sudah memiliki riwayat eksekusi test case.',
    steps: [
      { step_number: 1, title: 'Gunakan Filter Cakupan Data', description: 'Pilih Aplikasi, Modul, Menu, Tester, serta Tanggal Mulai dan Akhir. Seluruh statistik dan grafik otomatis dihitung ulang.', notes: 'Mengubah Aplikasi otomatis mereset Modul & Menu.' },
      { step_number: 2, title: 'Perhatikan Kartu Statistik Utama', description: 'Pantau Total Test Case, PASS (hijau), FAILED (merah), PENDING (kuning), NOT TESTED (abu-abu), serta Persentase Keberhasilan (Pass Rate).', notes: 'PENDING & NOT TESTED tidak dihitung sebagai PASS.' },
      { step_number: 3, title: 'Analisis Grafik Tren & Distribusi Bug', description: 'Lihat grafik distribusi status testing, bug berdasarkan severity, serta progres pengujian per aplikasi.', notes: 'Arahkan kursor pada grafik untuk melihat detail angka.' },
    ],
  },
  'Jadwal QA': {
    guide_code: 'GUIDE-SCH-01',
    title: 'Panduan Manajemen Jadwal QA & Kalender Pengujian',
    menu_name: 'Jadwal QA',
    category: 'Perencanaan QA',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Mengatur jadwal pelaksanaan pengujian perangkat lunak, penugasan tester, target test case, dan pemantauan progres aktual.',
    menu_function: 'Menampilkan jadwal dalam mode Daftar maupun Kalender, mendeteksi bentrok jadwal pada tester yang sama, dan menghitung progres penyelesaian dari eksekusi aktual.',
    tips: 'Gunakan tombol toggle "📋 Daftar" dan "📅 Kalender" di kanan atas untuk beralih tampilan.',
    troubleshooting: 'Jika muncul peringatan jadwal bentrok, artinya tester yang dipilih memiliki jadwal aktif lain pada waktu yang beririsan.',
    steps: [
      { step_number: 1, title: 'Pilih Mode Daftar atau Kalender', description: 'Gunakan tombol toggle di kanan atas dan saring jadwal berdasarkan Aplikasi, Status, atau Tester.', notes: 'Status Overdue menandakan jadwal melewati batas waktu selesai.' },
      { step_number: 2, title: 'Tambah / Edit Jadwal (Admin)', description: 'Klik "+ Tambah Jadwal", pilih Aplikasi & Modul, jenis testing, Tester, waktu pelaksanaan, dan centang Test Case yang ditargetkan.', notes: 'Sistem memvalidasi potensi jadwal bentrok secara otomatis.' },
      { step_number: 3, title: 'Pantau Progres di Tombol Detail', description: 'Klik tombol "Detail" untuk melihat daftar test case yang terhubung dan berapa yang sudah dieksekusi.', notes: 'Status jadwal terpisah dari status kelulusan test case.' },
    ],
  },
  'Test Management': {
    guide_code: 'GUIDE-TC-01',
    title: 'Panduan Manajemen dan Eksekusi Test Case',
    menu_name: 'Test Management',
    category: 'Pengujian',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Menyediakan repositori skenario pengujian, langkah pengujian, ekspektasi hasil, dan pencatatan hasil aktual testing beserta bukti (evidence).',
    menu_function: 'Membuat test case baru, menyaring skenario per aplikasi/modul, menjalankan eksekusi (PASS/FAILED/PENDING/NOT TESTED), dan mengunggah file bukti testing.',
    tips: 'Selalu sertakan hasil aktual yang jelas dan lampirkan bukti screenshot/video apabila status pengujian FAILED.',
    troubleshooting: 'Pastikan file bukti yang diunggah berformat gambar/PDF/dokumen/MP4 dengan ukuran di bawah 50MB.',
    steps: [
      { step_number: 1, title: 'Cari atau Tambah Skenario Test Case', description: 'Saring daftar test case menggunakan filter Aplikasi dan Modul. Admin dapat menambahkan skenario baru melalui tombol "+ Tambah Test Case".', notes: 'Test case dapat ditautkan ke Flow Aplikasi.' },
      { step_number: 2, title: 'Klik Tombol "▶ Eksekusi"', description: 'Klik tombol hijau "▶ Eksekusi" pada test case yang diuji, pilih status hasil (PASS / FAILED / PENDING / NOT TESTED), isi Hasil Aktual, dan unggah bukti.', notes: 'Setiap eksekusi tercatat sebagai riwayat baru tanpa menghapus riwayat lama.' },
      { step_number: 3, title: 'Lihat Riwayat di Tombol "Detail"', description: 'Klik tombol "Detail" untuk meninjau langkah pengujian lengkap serta seluruh histori eksekusi sebelumnya.', notes: 'Anda dapat mengunduh file evidence langsung dari modal detail.' },
    ],
  },
  'Bug Tracking': {
    guide_code: 'GUIDE-BUG-01',
    title: 'Panduan Pelaporan Bug & Defect Tracking',
    menu_name: 'Bug Tracking',
    category: 'Defect Tracking',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Mendokumentasikan temuan bug perangkat lunak, menentukan tingkat Severity & Priority, menugaskan developer, dan melacak riwayat penyelesaian.',
    menu_function: 'Membuat tiket bug baru, mengunggah bukti error, memperbarui status penyelesaian bug, dan mencatat audit trail perubahan status.',
    tips: 'Ubah status bug menjadi "Ready for Retest" setelah developer selesai memperbaiki bug agar dapat diuji ulang di menu Retesting.',
    troubleshooting: 'Tiket bug hanya dapat ditutup (Closed) setelah terverifikasi Passed pada proses Retesting.',
    steps: [
      { step_number: 1, title: 'Klik "+ Laporkan Bug"', description: 'Isi judul bug, pilih Aplikasi, Modul, dan Test Case terkait, tulis langkah reproduksi error, pilih Severity & Priority, lalu unggah bukti temuan.', notes: 'Kode tiket bug dibuat otomatis oleh sistem.' },
      { step_number: 2, title: 'Ubah Status & Penugasan via Tombol "Edit"', description: 'Klik tombol "Edit" untuk menugaskan Developer (Assignee), mengubah status tiket (Open -> Assigned -> In Progress -> Fixed -> Ready for Retest), dan mengisi catatan resolusi.', notes: 'Semua perubahan status terekam di histori bug.' },
      { step_number: 3, title: 'Periksa Bukti & Histori di Tombol "Detail"', description: 'Klik tombol "Detail" untuk mengunduh lampiran evidence dan melihat kronologi penanganan tiket bug.', notes: 'Mendukung multi-file evidence.' },
    ],
  },
  'Retesting': {
    guide_code: 'GUIDE-RET-01',
    title: 'Panduan Verifikasi Pengujian Ulang (Retesting)',
    menu_name: 'Retesting',
    category: 'Verifikasi Perbaikan',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Memverifikasi apakah tiket bug yang telah diperbaiki oleh developer sudah benar-benar tuntas.',
    menu_function: 'Mencatat hasil pengujian ulang atas tiket bug, melampirkan bukti retesting, serta otomatis mengubah status bug menjadi Closed (jika Passed) atau Reopened (jika Failed).',
    tips: 'Cantumkan nomor versi aplikasi yang diuji ulang (contoh: 3.4.2-patch1) sebagai referensi rilis.',
    troubleshooting: 'Jika bug belum muncul di pilihan dropdown, pastikan statusnya di menu Bug Tracking sudah diatur ke "Ready for Retest" atau "Fixed".',
    steps: [
      { step_number: 1, title: 'Klik "+ Input Hasil Retest"', description: 'Buka formulir retesting melalui tombol di kanan atas halaman.', notes: 'Daftar menampilkan tiket bug yang siap diuji ulang.' },
      { step_number: 2, title: 'Pilih Tiket Bug & Status Hasil', description: 'Pilih tiket bug, isi versi aplikasi, pilih status Passed (otomatis menutup bug menjadi Closed) atau Failed/Reopened (otomatis membuka kembali bug), dan unggah bukti.', notes: 'Isi hasil aktual pengujian ulang dengan jelas.' },
      { step_number: 3, title: 'Unduh Bukti pada Tabel Riwayat', description: 'Gunakan tombol "📎 Bukti" pada tabel untuk melihat lampiran hasil verifikasi retesting.', notes: 'Riwayat retesting tersimpan permanen per tiket bug.' },
    ],
  },
  'Flow Aplikasi': {
    guide_code: 'GUIDE-FLOW-01',
    title: 'Panduan Dokumentasi & Diagram Flow Aplikasi',
    menu_name: 'Flow Aplikasi',
    category: 'Dokumentasi Sistem',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Mendokumentasikan alur proses bisnis aplikasi dalam bentuk visualisasi diagram node (Start, Process, Decision, End) beserta dokumen pendukung.',
    menu_function: 'Menyusun langkah-langkah proses bisnis, menampilkan diagram alur interaktif, dan mengelola berkas dokumentasi flow.',
    tips: 'Klik pada setiap node langkah di dalam tampilan diagram untuk melihat detail aktor, input, dan expected result.',
    troubleshooting: 'Pastikan status flow adalah "Published" agar dapat dilihat oleh seluruh anggota tim QA.',
    steps: [
      { step_number: 1, title: 'Klik "📊 Lihat Diagram"', description: 'Buka visualisasi diagram alur proses bisnis dan unduh berkas dokumentasi yang terlampir.', notes: 'Admin juga dapat mengunggah file dokumen/diagram tambahan di modal ini.' },
      { step_number: 2, title: 'Tambah / Edit Flow & Langkah Node (Admin)', description: 'Klik "+ Tambah Flow Baru" atau "Edit" untuk menyusun urutan langkah Start, Process, Decision, dan End.', notes: 'Flow dapat dihubungkan ke Jadwal QA, Test Case, dan Bug.' },
    ],
  },
  'Panduan SISQA': {
    guide_code: 'GUIDE-MAN-01',
    title: 'Panduan Mengelola & Mengamati Panduan SISQA',
    menu_name: 'Panduan SISQA',
    category: 'Dokumentasi Operasional',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Menyediakan pusat dokumentasi penggunaan seluruh menu SISQA sekaligus mencatat verifikasi pengamatan oleh Administrator.',
    menu_function: 'Membaca panduan per menu, menambah/mengedit panduan, serta menandai panduan sebagai "Sudah Diamati oleh Admin".',
    tips: 'Setiap kali isi panduan diedit, status pengamatan otomatis kembali menjadi "Belum Diamati" hingga diverifikasi ulang oleh Admin.',
    troubleshooting: 'Hanya pengguna dengan role Admin yang dapat melihat tombol "+ Tambah Panduan", "Edit", "Hapus", dan "✓ Tandai Diamati".',
    steps: [
      { step_number: 1, title: 'Cari & Klik Tombol "📖 Baca"', description: 'Gunakan kolom pencarian atau filter status pengamatan, lalu klik "📖 Baca" untuk melihat isi lengkap panduan.', notes: 'Menampilkan tujuan, langkah-langkah, tips, dan riwayat pengamatan.' },
      { step_number: 2, title: 'Tandai Sudah Diamati (Admin)', description: 'Klik tombol hijau "✓ Tandai Diamati" untuk memverifikasi bahwa panduan telah ditinjau oleh Administrator.', notes: 'Nama Admin, tanggal, dan versi tercatat otomatis.' },
    ],
  },
  'QA Report': {
    guide_code: 'GUIDE-RPT-01',
    title: 'Panduan Penyusunan & Ekspor Laporan QA (Excel & PDF)',
    menu_name: 'QA Report',
    category: 'Pelaporan & Ekspor',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Menyusun laporan komprehensif hasil pengujian QA dan mengekspornya ke format Excel (.xlsx) maupun PDF (.pdf).',
    menu_function: 'Menyaring cakupan laporan berdasarkan aplikasi/modul/tester/tanggal, memilih metode perhitungan, dan mengunduh dokumen laporan.',
    tips: 'Pilih mode "Status Terbaru" untuk melihat potret kualitas terkini, atau "Historis" untuk menghitung seluruh riwayat eksekusi pada periode terpilih.',
    troubleshooting: 'Jika file tidak terunduh otomatis, periksa izin unduhan otomatis pada browser Anda.',
    steps: [
      { step_number: 1, title: 'Pilih Filter & Klik "Terapkan Filter"', description: 'Tentukan Aplikasi, Modul, Tester, Periode Tanggal, dan Mode Perhitungan laporan.', notes: 'Keterangan rumus perhitungan ditampilkan secara transparan di halaman.' },
      { step_number: 2, title: 'Klik "📊 Ekspor Excel" atau "📄 Ekspor PDF"', description: 'Unduh laporan dalam bentuk spreadsheet Excel multi-sheet atau dokumen PDF siap cetak.', notes: 'Aktivitas ekspor tercatat otomatis di Activity Logs.' },
    ],
  },
  'Report WA': {
    guide_code: 'GUIDE-WA-01',
    title: 'Panduan Menyusun & Mengirim QA Daily Testing & Progress Report (WA)',
    menu_name: 'Report WA',
    category: 'Pelaporan Harian WA',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Menyusun laporan harian QA berformat standar WhatsApp (*QA DAILY TESTING & PROGRESS REPORT*) secara otomatis maupun kustom.',
    menu_function: 'Mengatur informasi Tanggal, QA, Server/Environment, Progress Testing, Persentase 9 Modul (Master Data, Pembelian, Penjualan, Stock, Service, MRP, Keuangan, Laporan, Perbaikan), Temuan Bug, Retest, Catatan, Rencana Selanjutnya, dan Status QA Hari Ini.',
    tips: 'Setiap perubahan pada form di sebelah kiri langsung memperbarui tampilan Live Preview WhatsApp di sebelah kanan secara real-time.',
    troubleshooting: 'Jika menyalin dari IP jaringan lokal non-HTTPS, tombol Salin Teks WA sudah dilengkapi fallback otomatis agar tetap tersalin ke clipboard.',
    steps: [
      { step_number: 1, title: 'Pilih Filter Sumber Data Otomatis', description: 'Pilih Aplikasi atau rentang tanggal di bagian atas untuk menarik angka Complete, On Progress, Temuan Bug, dan daftar Retest dari database.', notes: 'Anda juga bisa membuka menu ini langsung dari tombol "💬 Report WA" di halaman QA Report.' },
      { step_number: 2, title: 'Sesuaikan Persentase Modul, Bug & Catatan', description: 'Atur persentase progres pada 9 modul standar (Master Data s/d Perbaikan), periksa daftar Temuan Bug & Retest, lalu pilih Status QA Hari Ini (AMAN / ON PROGRESS / ADA TEMUAN).', notes: 'Anda dapat menambah atau menghapus baris modul, bug, dan retest sesuai kebutuhan.' },
      { step_number: 3, title: 'Klik "📋 Salin Teks WA" atau "📲 Kirim ke WhatsApp"', description: 'Salin teks laporan yang sudah terformat tebal/miring khas WhatsApp atau langsung buka aplikasi WhatsApp.', notes: 'Teks di dalam kotak preview hijau juga dapat diedit langsung sebelum dikirim.' },
    ],
  },
  'Activity Logs': {
    guide_code: 'GUIDE-LOG-01',
    title: 'Panduan Audit Trail & Activity Logs',
    menu_name: 'Activity Logs',
    category: 'Keamanan & Audit',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Mencatat dan menampilkan jejak aktivitas seluruh pengguna di dalam aplikasi SISQA.',
    menu_function: 'Menyaring log berdasarkan kata kunci atau modul, serta melihat waktu, pelaku aksi, deskripsi aktivitas, dan alamat IP.',
    tips: 'Admin dapat memantau log seluruh pengguna, sedangkan User melihat log aktivitas miliknya sendiri.',
    troubleshooting: 'Password dan token rahasia tidak pernah dicatat ke dalam log demi menjaga keamanan data.',
    steps: [
      { step_number: 1, title: 'Gunakan Pencarian & Filter Modul', description: 'Ketik kata kunci (seperti kode bug/test case) atau pilih modul untuk menyaring daftar riwayat aktivitas.', notes: 'Log ditampilkan mulai dari kejadian terbaru.' },
      { step_number: 2, title: 'Tinjau Rincian Aksi & Waktu', description: 'Periksa kolom Waktu, Pengguna, Modul, Aktivitas, dan Alamat IP untuk keperluan audit.', notes: 'Mendukung navigasi halaman (pagination).' },
    ],
  },
  'Master Aplikasi': {
    guide_code: 'GUIDE-MAPP-01',
    title: 'Panduan Pengelolaan Master Data Aplikasi',
    menu_name: 'Master Aplikasi',
    category: 'Administrasi Master',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Mengelola daftar aplikasi yang berada di bawah pengawasan kualitas tim QA.',
    menu_function: 'Menambah, mengedit, dan menghapus data aplikasi beserta kode unik, versi, dan environment.',
    tips: 'Selalu perbarui versi aplikasi saat rilis baru naik ke Staging atau Production.',
    troubleshooting: 'Pastikan kode aplikasi unik saat menambahkan aplikasi baru.',
    steps: [
      { step_number: 1, title: 'Klik "+ Tambah Aplikasi"', description: 'Masukkan Nama Aplikasi, Kode Unik, Versi, Environment, dan Deskripsi.', notes: 'Aplikasi yang aktif akan muncul di seluruh pilihan filter.' },
      { step_number: 2, title: 'Gunakan Tombol "Edit" atau "Hapus"', description: 'Klik "Edit" untuk mengubah data aplikasi atau "Hapus" untuk menghapus aplikasi.', notes: 'Tabel menampilkan jumlah modul dan test case terkait.' },
    ],
  },
  'Master Modul': {
    guide_code: 'GUIDE-MMOD-01',
    title: 'Panduan Pengelolaan Master Data Modul',
    menu_name: 'Master Modul',
    category: 'Administrasi Master',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Mengelola pembagian modul fitur di dalam setiap aplikasi.',
    menu_function: 'Menambah, mengubah, dan menghapus modul yang berelasi dengan aplikasi induk.',
    tips: 'Gunakan kode modul singkat dan jelas untuk mempermudah identifikasi.',
    troubleshooting: 'Pilih aplikasi induk yang tepat saat membuat modul baru.',
    steps: [
      { step_number: 1, title: 'Klik "+ Tambah Modul"', description: 'Pilih Aplikasi Induk, lalu isi Nama Modul dan Kode Modul.', notes: 'Modul otomatis terhubung pada dropdown bertingkat.' },
      { step_number: 2, title: 'Kelola dengan Tombol "Edit" & "Hapus"', description: 'Perbarui informasi modul atau hapus modul yang tidak diperlukan.', notes: 'Menampilkan jumlah menu di bawah modul tersebut.' },
    ],
  },
  'Master Menu': {
    guide_code: 'GUIDE-MMEN-01',
    title: 'Panduan Pengelolaan Master Data Menu / Fitur',
    menu_name: 'Master Menu',
    category: 'Administrasi Master',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Mengelola daftar halaman menu atau fitur spesifik pada setiap modul aplikasi.',
    menu_function: 'Menambah, mengubah, dan menghapus menu dengan relasi Aplikasi -> Modul -> Menu.',
    tips: 'Cantumkan path URL/Route menu agar tester dapat langsung merujuk ke halaman terkait.',
    troubleshooting: 'Saat mengganti Aplikasi di form, pilihan Modul akan otomatis menyesuaikan.',
    steps: [
      { step_number: 1, title: 'Klik "+ Tambah Menu"', description: 'Pilih Aplikasi, pilih Modul terkait, lalu isi Nama Menu, Kode Menu, dan Route URL.', notes: 'Konsistensi relasi dijaga oleh validasi backend.' },
      { step_number: 2, title: 'Edit atau Hapus Menu', description: 'Gunakan tombol "Edit" atau "Hapus" pada kolom aksi.', notes: 'Menu akan tampil di filter bertingkat saat memilih modul.' },
    ],
  },
  'Manajemen Pengguna': {
    guide_code: 'GUIDE-USR-01',
    title: 'Panduan Manajemen Pengguna & Hak Akses Role',
    menu_name: 'Manajemen Pengguna',
    category: 'Administrasi Sistem',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Mengelola akun pengguna sistem SISQA, penugasan role Admin/User, dan status aktif.',
    menu_function: 'Menambah pengguna, mengubah profil/role/password, serta mengaktifkan atau menonaktifkan akun.',
    tips: 'Klik langsung pada lencana status "● Aktif / ○ Nonaktif" di tabel untuk mengubah status aktif pengguna dengan cepat.',
    troubleshooting: 'Pengguna yang berstatus Nonaktif tidak dapat login ke dalam aplikasi.',
    steps: [
      { step_number: 1, title: 'Klik "+ Tambah Pengguna"', description: 'Isi Nama Lengkap, Username, pilih Role (Admin / User), Email, dan Password.', notes: 'Email dan Username harus unik.' },
      { step_number: 2, title: 'Kelola Status Aktif, Edit & Hapus', description: 'Klik lencana status untuk toggle aktif/nonaktif, atau klik tombol "Edit" dan "Hapus".', notes: 'Kosongkan kolom password saat edit jika tidak ingin mengganti password.' },
    ],
  },
  'Profil Saya': {
    guide_code: 'GUIDE-PRF-01',
    title: 'Panduan Pengaturan Profil Saya & Keamanan Password',
    menu_name: 'Profil Saya',
    category: 'Akun Pengguna',
    version: '1.0',
    status: 'Published',
    observation_status: 'Sudah Diamati',
    menu_purpose: 'Mengelola informasi identitas akun pribadi dan mengganti password keamanan.',
    menu_function: 'Memperbarui nama, username, email, serta mengganti password akun yang sedang login.',
    tips: 'Gunakan password minimal 8 karakter dengan kombinasi huruf dan angka.',
    troubleshooting: 'Pastikan password lama diisi dengan benar saat melakukan perubahan password.',
    steps: [
      { step_number: 1, title: 'Ubah Data Profil', description: 'Perbarui Nama Lengkap, Username, atau Email pada formulir kiri lalu klik Simpan.', notes: 'Perubahan langsung diperbarui pada sesi aktif.' },
      { step_number: 2, title: 'Ganti Password', description: 'Masukkan Password Saat Ini, Password Baru, dan Konfirmasi Password Baru pada formulir kanan.', notes: 'Password disimpan dalam bentuk hash yang aman.' },
    ],
  },
};

const currentMenuName = computed(() => {
  return routeMenuMap[route.path] || 'Dashboard QA';
});

const activeMenuGuide = computed(() => {
  if (dbGuide.value && dbGuide.value.menu_name === currentMenuName.value) {
    return dbGuide.value;
  }
  return fallbackGuides[currentMenuName.value] || fallbackGuides['Dashboard QA'];
});

const fetchGuideForCurrentMenu = async () => {
  const menuName = currentMenuName.value;
  try {
    const res = await api.get('/guides', { params: { menu_name: menuName } });
    if (res.data?.guide) {
      dbGuide.value = res.data.guide;
    } else {
      dbGuide.value = null;
    }
  } catch (e) {
    dbGuide.value = null;
  }
};

const markCurrentGuideObserved = async () => {
  if (!activeMenuGuide.value?.id) return;
  try {
    await api.post(`/guides/${activeMenuGuide.value.id}/observe`);
    await fetchGuideForCurrentMenu();
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menandai panduan.');
  }
};

const openGuideModal = () => {
  showGuideModal.value = true;
  fetchGuideForCurrentMenu();
};

const enforceRoutePermission = () => {
  if (!isAdmin.value && route.path !== '/profile' && !hasMenuAccess(route.path)) {
    const target = getFirstAllowedPath();
    if (target && target !== route.path) {
      router.replace(target);
    }
  }
};

let lastAuthSync = 0;
const syncPermissionsOnFocus = async () => {
  const now = Date.now();
  if (now - lastAuthSync < 10000) return;
  lastAuthSync = now;
  await checkAuth();
  enforceRoutePermission();
};

watch(
  () => route.path,
  () => {
    mobileMenuOpen.value = false;
    enforceRoutePermission();
  }
);

onMounted(async () => {
  window.addEventListener('focus', syncPermissionsOnFocus);
  lastAuthSync = Date.now();
  await checkAuth();
  enforceRoutePermission();
});

onUnmounted(() => {
  window.removeEventListener('focus', syncPermissionsOnFocus);
});

const handleLogout = () => {
  showLogoutConfirm.value = true;
};

const executeLogout = async () => {
  showLogoutConfirm.value = false;
  await logout();
};
</script>

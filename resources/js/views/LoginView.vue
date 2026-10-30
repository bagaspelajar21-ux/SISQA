<template>
  <div class="min-h-screen bg-gradient-to-b from-[#050b1a] via-[#0b1938] to-[#0f2447] relative overflow-hidden flex flex-col justify-center py-12 sm:px-6 lg:px-8 select-none">
    <!-- ==================== ANIMATED OFFICE SKYLINE BACKGROUND ==================== -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden z-0">
      <!-- Ambient Sky Glows -->
      <div class="absolute top-0 left-1/4 w-[520px] h-[520px] rounded-full bg-blue-500/15 blur-[130px]"></div>
      <div class="absolute bottom-24 right-1/4 w-[480px] h-[480px] rounded-full bg-cyan-400/15 blur-[120px]"></div>
      <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-full h-56 bg-gradient-to-t from-amber-500/10 via-blue-500/5 to-transparent"></div>

      <!-- Twinkling Stars & Data Nodes -->
      <div class="stars-layer absolute inset-0">
        <span
          v-for="star in stars"
          :key="star.id"
          class="star-dot absolute rounded-full bg-white"
          :style="{
            top: star.top,
            left: star.left,
            width: star.size,
            height: star.size,
            animationDelay: star.delay,
            animationDuration: star.duration,
          }"
        ></span>
      </div>

      <!-- Drifting Night Clouds -->
      <svg class="cloud-slow absolute top-12 left-0 w-full h-40 opacity-20" viewBox="0 0 1440 200" fill="none">
        <path d="M120 90C120 65 145 50 170 55C185 30 225 25 245 50C275 45 300 65 295 90H120Z" fill="url(#cloudGrad)" />
        <path d="M820 120C820 95 850 80 875 85C895 55 940 50 965 80C995 75 1020 95 1015 120H820Z" fill="url(#cloudGrad)" />
        <path d="M1180 65C1180 45 1205 32 1228 38C1242 15 1278 15 1295 38C1320 35 1340 50 1335 65H1180Z" fill="url(#cloudGrad)" />
        <defs>
          <linearGradient id="cloudGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#93c5fd" stop-opacity="0.6" />
            <stop offset="100%" stop-color="#1e3a8a" stop-opacity="0" />
          </linearGradient>
        </defs>
      </svg>

      <!-- Full Panoramic Animated Corporate Office Buildings SVG -->
      <svg
        class=" bottom-0 left-0 w-full h-[68vh] min-h-[420px] max-h-[680px] absolute object-cover"
        viewBox="0 0 1600 650"
        preserveAspectRatio="xMidYMax slice"
        xmlns="http://www.w3.org/2000/svg"
      >
        <defs>
          <!-- Building Gradients -->
          <linearGradient id="bgTowerGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#0f2347" />
            <stop offset="100%" stop-color="#071226" />
          </linearGradient>
          <linearGradient id="midTowerGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#153163" />
            <stop offset="100%" stop-color="#09162e" />
          </linearGradient>
          <linearGradient id="frontTowerGrad" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#1e3f7c" />
            <stop offset="50%" stop-color="#122852" />
            <stop offset="100%" stop-color="#081329" />
          </linearGradient>
          <linearGradient id="glassFacade" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.28" />
            <stop offset="100%" stop-color="#1d4ed8" stop-opacity="0.06" />
          </linearGradient>
          <linearGradient id="warmWindow" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#fde68a" />
            <stop offset="100%" stop-color="#f59e0b" />
          </linearGradient>
          <linearGradient id="cyanWindow" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#e0f2fe" />
            <stop offset="100%" stop-color="#38bdf8" />
          </linearGradient>
          <linearGradient id="beamGrad" x1="0" y1="1" x2="0" y2="0">
            <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.25" />
            <stop offset="100%" stop-color="#38bdf8" stop-opacity="0" />
          </linearGradient>
        </defs>

        <!-- Upward Architectural Spotlights -->
        <polygon class="searchlight-left" points="260,600 140,40 320,40" fill="url(#beamGrad)" />
        <polygon class="searchlight-right" points="1320,600 1200,50 1390,50" fill="url(#beamGrad)" />

        <!-- ==================== LAYER 1: DISTANT SKYLINE ==================== -->
        <g opacity="0.55">
          <rect x="20" y="250" width="95" height="360" fill="url(#bgTowerGrad)" />
          <rect x="130" y="190" width="110" height="420" fill="url(#bgTowerGrad)" />
          <rect x="260" y="230" width="85" height="380" fill="url(#bgTowerGrad)" />
          <rect x="370" y="160" width="120" height="450" fill="url(#bgTowerGrad)" />
          <rect x="1080" y="175" width="125" height="435" fill="url(#bgTowerGrad)" />
          <rect x="1225" y="220" width="95" height="390" fill="url(#bgTowerGrad)" />
          <rect x="1340" y="150" width="130" height="460" fill="url(#bgTowerGrad)" />
          <rect x="1485" y="240" width="95" height="370" fill="url(#bgTowerGrad)" />

          <!-- Vertical Curtain Wall Lines on Distant Towers -->
          <g stroke="#3b82f6" stroke-opacity="0.18" stroke-width="1">
            <line x1="155" y1="205" x2="155" y2="590" />
            <line x1="185" y1="205" x2="185" y2="590" />
            <line x1="215" y1="205" x2="215" y2="590" />
            <line x1="400" y1="175" x2="400" y2="590" />
            <line x1="430" y1="175" x2="430" y2="590" />
            <line x1="460" y1="175" x2="460" y2="590" />
            <line x1="1370" y1="165" x2="1370" y2="590" />
            <line x1="1405" y1="165" x2="1405" y2="590" />
            <line x1="1440" y1="165" x2="1440" y2="590" />
          </g>
        </g>

        <!-- ==================== LAYER 2: MID-GROUND CORPORATE TOWERS ==================== -->
        <!-- Left High-Rise Office Complex -->
        <g>
          <!-- Spire & Antenna -->
          <line x1="135" y1="95" x2="135" y2="145" stroke="#60a5fa" stroke-width="3" />
          <circle class="beacon-blink" cx="135" cy="92" r="4" fill="#ef4444" />
          <rect x="85" y="145" width="100" height="465" rx="3" fill="url(#midTowerGrad)" stroke="#2563eb" stroke-opacity="0.3" />
          <rect x="93" y="155" width="84" height="440" fill="url(#glassFacade)" />

          <!-- Office Windows Grid (Left High-Rise) -->
          <g>
            <rect v-for="(w, idx) in leftTowerWindows" :key="'lt-' + idx" :x="w.x" :y="w.y" width="12" height="7" rx="1" :fill="w.fill" :class="w.animClass" />
          </g>
        </g>

        <!-- Left-Center Stepped Modern Office Tower (With Panoramic Elevator) -->
        <g>
          <!-- Signal Tower & Radar Pulse -->
          <line x1="330" y1="65" x2="330" y2="120" stroke="#93c5fd" stroke-width="3" />
          <circle cx="330" cy="65" r="4" fill="#38bdf8" />
          <circle class="radar-ring" cx="330" cy="65" r="14" stroke="#38bdf8" stroke-width="1.5" fill="none" />
          <circle class="radar-ring-delayed" cx="330" cy="65" r="14" stroke="#38bdf8" stroke-width="1.5" fill="none" />

          <rect x="275" y="120" width="110" height="25" rx="2" fill="#1d4ed8" opacity="0.45" />
          <rect x="255" y="140" width="150" height="470" rx="4" fill="url(#frontTowerGrad)" stroke="#38bdf8" stroke-opacity="0.35" />

          <!-- Illuminated Rooftop Brand Strip -->
          <rect x="270" y="152" width="120" height="6" rx="3" fill="#38bdf8" opacity="0.85" />

          <!-- Exterior Glass Elevator Shaft -->
          <rect x="382" y="170" width="12" height="410" fill="#09152b" stroke="#38bdf8" stroke-opacity="0.3" />
          <rect class="elevator-cab-1" x="383.5" y="200" width="9" height="18" rx="2" fill="#fde68a" />

          <!-- Office Windows (Left-Center Tower) -->
          <g>
            <rect v-for="(w, idx) in leftCenterWindows" :key="'lct-' + idx" :x="w.x" :y="w.y" width="18" height="8" rx="1.5" :fill="w.fill" :class="w.animClass" />
          </g>
        </g>

        <!-- Center Background Twin Corporate Towers (Framing the Login Card) -->
        <g opacity="0.82">
          <!-- Left Twin -->
          <polygon points="455,210 555,175 555,610 455,610" fill="url(#midTowerGrad)" stroke="#38bdf8" stroke-opacity="0.25" />
          <!-- Right Twin -->
          <polygon points="1045,175 1145,210 1145,610 1045,610" fill="url(#midTowerGrad)" stroke="#38bdf8" stroke-opacity="0.25" />

          <!-- Diagonal Structural Bracing (High-Tech Skyscraper Look) -->
          <g stroke="#38bdf8" stroke-opacity="0.2" stroke-width="1.5">
            <line x1="455" y1="250" x2="555" y2="310" />
            <line x1="555" y1="250" x2="455" y2="310" />
            <line x1="455" y1="370" x2="555" y2="430" />
            <line x1="555" y1="370" x2="455" y2="430" />

            <line x1="1045" y1="250" x2="1145" y2="310" />
            <line x1="1145" y1="250" x2="1045" y2="310" />
            <line x1="1045" y1="370" x2="1145" y2="430" />
            <line x1="1145" y1="370" x2="1045" y2="430" />
          </g>
        </g>

        <!-- Right-Center Flagship Corporate HQ Tower -->
        <g>
          <!-- Dual Spires -->
          <line x1="1235" y1="75" x2="1235" y2="135" stroke="#60a5fa" stroke-width="3" />
          <line x1="1285" y1="95" x2="1285" y2="135" stroke="#60a5fa" stroke-width="2" />
          <circle class="beacon-blink" cx="1235" cy="72" r="4" fill="#ef4444" />
          <circle class="beacon-blink-delayed" cx="1285" cy="92" r="3.5" fill="#38bdf8" />

          <!-- Crown & Main Body -->
          <polygon points="1200,135 1320,115 1340,135 1340,610 1185,610 1185,135" fill="url(#frontTowerGrad)" stroke="#38bdf8" stroke-opacity="0.35" />
          <rect x="1198" y="142" width="128" height="5" rx="2.5" fill="#38bdf8" opacity="0.9" />

          <!-- Exterior Glass Elevator Shaft 2 -->
          <rect x="1196" y="165" width="11" height="415" fill="#09152b" stroke="#38bdf8" stroke-opacity="0.3" />
          <rect class="elevator-cab-2" x="1197.5" y="340" width="8" height="16" rx="2" fill="#38bdf8" />

          <!-- Office Windows (Right-Center HQ) -->
          <g>
            <rect v-for="(w, idx) in rightCenterWindows" :key="'rct-' + idx" :x="w.x" :y="w.y" width="16" height="8" rx="1.5" :fill="w.fill" :class="w.animClass" />
          </g>
        </g>

        <!-- Far Right Modern Glass Office Building -->
        <g>
          <rect x="1375" y="195" width="135" height="415" rx="4" fill="url(#midTowerGrad)" stroke="#2563eb" stroke-opacity="0.35" />
          <rect x="1387" y="208" width="111" height="385" fill="url(#glassFacade)" />

          <!-- Horizontal Lit Floor Bands -->
          <g>
            <rect
              v-for="(band, idx) in rightFloorBands"
              :key="'rfb-' + idx"
              x="1395"
              :y="band.y"
              width="95"
              height="6"
              rx="2"
              :fill="band.fill"
              :class="band.animClass"
            />
          </g>
        </g>

        <!-- ==================== LAYER 3: FOREGROUND OFFICE CAMPUS & LOBBIES ==================== -->
        <g>
          <!-- Left Low-Rise Podium Office -->
          <rect x="30" y="450" width="205" height="160" rx="4" fill="#0c1d3d" stroke="#3b82f6" stroke-opacity="0.35" />
          <!-- Glowing Ground Floor Lobby -->
          <rect x="48" y="560" width="168" height="38" rx="2" fill="url(#warmWindow)" opacity="0.75" />
          <!-- Lobby Columns -->
          <g stroke="#0c1d3d" stroke-width="4">
            <line x1="80" y1="560" x2="80" y2="598" />
            <line x1="115" y1="560" x2="115" y2="598" />
            <line x1="150" y1="560" x2="150" y2="598" />
            <line x1="185" y1="560" x2="185" y2="598" />
          </g>

          <!-- Right Low-Rise Podium Office -->
          <rect x="1310" y="465" width="230" height="145" rx="4" fill="#0c1d3d" stroke="#3b82f6" stroke-opacity="0.35" />
          <rect x="1330" y="562" width="190" height="36" rx="2" fill="url(#cyanWindow)" opacity="0.65" />
          <g stroke="#0c1d3d" stroke-width="4">
            <line x1="1365" y1="562" x2="1365" y2="598" />
            <line x1="1405" y1="562" x2="1405" y2="598" />
            <line x1="1445" y1="562" x2="1445" y2="598" />
            <line x1="1485" y1="562" x2="1485" y2="598" />
          </g>
        </g>

        <!-- ==================== LAYER 4: SMART HIGHWAY & ANIMATED TRAFFIC ==================== -->
        <g>
          <!-- Ground / Road Deck -->
          <rect x="0" y="600" width="1600" height="50" fill="#050d1d" />
          <line x1="0" y1="600" x2="1600" y2="600" stroke="#38bdf8" stroke-opacity="0.45" stroke-width="2" />
          <line x1="0" y1="624" x2="1600" y2="624" stroke="#475569" stroke-dasharray="18 14" stroke-width="1.5" />

          <!-- Street Lamps -->
          <g v-for="lx in [120, 310, 500, 1100, 1290, 1480]" :key="'lamp-' + lx">
            <line :x1="lx" y1="568" :x2="lx" y2="600" stroke="#64748b" stroke-width="2" />
            <circle :cx="lx" cy="568" r="3.5" fill="#fde68a" />
            <circle :cx="lx" cy="568" r="10" fill="#fde68a" opacity="0.2" />
          </g>

          <!-- Moving Traffic Light Trails (Left to Right) -->
          <g class="traffic-right-1">
            <rect x="80" y="609" width="46" height="4" rx="2" fill="#fde68a" />
            <rect x="122" y="608" width="14" height="6" rx="3" fill="#ffffff" />
          </g>
          <g class="traffic-right-2">
            <rect x="520" y="611" width="64" height="4" rx="2" fill="#38bdf8" />
            <rect x="580" y="610" width="12" height="6" rx="3" fill="#e0f2fe" />
          </g>

          <!-- Moving Traffic Light Trails (Right to Left) -->
          <g class="traffic-left-1">
            <rect x="1350" y="632" width="54" height="4" rx="2" fill="#f43f5e" />
            <rect x="1342" y="631" width="12" height="6" rx="3" fill="#fda4af" />
          </g>
          <g class="traffic-left-2">
            <rect x="880" y="634" width="42" height="4" rx="2" fill="#fb7185" />
            <rect x="872" y="633" width="10" height="6" rx="3" fill="#fecdd3" />
          </g>
        </g>
      </svg>
    </div>

    <!-- ==================== FOREGROUND LOGIN CONTENT ==================== -->
    <div class="relative z-10 sm:mx-auto sm:w-full sm:max-w-md flex flex-col items-center">
      <!-- Modern Emblem Logo -->
      <SisqaLogo size="xl" :show-text="false" :glow="true" />

      <div class="mt-4 flex items-center space-x-2">
        <h2 class="text-3xl font-black tracking-tight text-white drop-shadow-md">
          SIS<span class="text-sky-400">QA</span>
        </h2>
        <span class="px-2 py-0.5 rounded-md bg-sky-500/20 text-sky-300 border border-sky-400/30 text-[10px] font-extrabold uppercase tracking-widest backdrop-blur-xs">
          ENTERPRISE
        </span>
      </div>

      <p class="mt-1.5 text-center text-xs text-slate-300 font-medium tracking-wide drop-shadow">
        Software Quality Assurance &amp; Defect Management System
      </p>
    </div>

    <div class="relative z-10 mt-7 sm:mx-auto sm:w-full sm:max-w-md px-4">
      <div class="bg-slate-900/45 backdrop-blur-2xl py-8 px-6 shadow-[0_25px_80px_-15px_rgba(2,132,199,0.4)] rounded-3xl sm:px-10 border border-sky-400/25 ring-1 ring-white/10">
        <div class="flex items-center justify-between pb-4 mb-5 border-b border-sky-300/15">
          <div>
            <h3 class="text-base font-extrabold text-white tracking-tight">Masuk ke Portal QA</h3>
            <p class="text-xs text-sky-200/75 mt-0.5">Gunakan kredensial email atau username resmi Anda</p>
          </div>
          <div class="w-10 h-10 rounded-2xl bg-sky-500/15 border border-sky-400/30 flex items-center justify-center text-sky-300 shadow-inner">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
        </div>

        <!-- Error Alert -->
        <div v-if="errorMessage" class="mb-5 p-3 rounded-xl bg-rose-500/20 backdrop-blur-md border border-rose-400/40 text-rose-100 text-xs flex items-center space-x-2">
          <svg class="w-4 h-4 shrink-0 text-rose-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <span>{{ errorMessage }}</span>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-sky-100/90 mb-1.5">Email atau Username</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sky-300/70 pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </span>
              <input
                v-model="loginInput"
                type="text"
                required
                placeholder="Masukkan email atau username"
                class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-slate-950/45 hover:bg-slate-950/60 focus:bg-slate-950/75 backdrop-blur-md border border-sky-300/25 focus:border-sky-400 text-xs text-white placeholder-sky-200/45 focus:outline-none focus:ring-2 focus:ring-sky-400/30 transition-all"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-sky-100/90 mb-1.5">Password</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sky-300/70 pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </span>
              <input
                v-model="password"
                type="password"
                required
                placeholder="••••••••"
                class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-slate-950/45 hover:bg-slate-950/60 focus:bg-slate-950/75 backdrop-blur-md border border-sky-300/25 focus:border-sky-400 text-xs text-white placeholder-sky-200/45 focus:outline-none focus:ring-2 focus:ring-sky-400/30 transition-all"
              />
            </div>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full mt-2 py-2.5 px-4 rounded-xl bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 hover:from-sky-400 hover:via-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs shadow-lg shadow-sky-500/30 border border-sky-300/30 transition-all flex items-center justify-center space-x-2 disabled:opacity-50 cursor-pointer"
          >
            <span v-if="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>{{ loading ? 'Memverifikasi Akses...' : 'Masuk ke SISQA' }}</span>
          </button>
        </form>
      </div>

      <p class="text-center text-[11px] text-sky-200/70 mt-5 drop-shadow">
        &copy; {{ new Date().getFullYear() }} SISQA — Software Quality Assurance System
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../auth';
import SisqaLogo from '../components/SisqaLogo.vue';

const router = useRouter();
const { login, getFirstAllowedPath } = useAuth();

const loginInput = ref('');
const password = ref('');
const loading = ref(false);
const errorMessage = ref('');

// Generate deterministic stars in the upper night sky
const stars = Array.from({ length: 28 }, (_, i) => ({
  id: i,
  top: `${(i * 17) % 48}%`,
  left: `${(i * 37 + 11) % 96}%`,
  size: i % 3 === 0 ? '3px' : '2px',
  delay: `${(i % 7) * 0.6}s`,
  duration: `${2.5 + (i % 4)}s`,
}));

// Generate animated office window grids for the towers
const animClasses = ['win-pulse-1', 'win-pulse-2', 'win-pulse-3', 'win-steady'];

const leftTowerWindows = [];
for (let row = 0; row < 22; row++) {
  for (let col = 0; col < 4; col++) {
    if ((row + col) % 5 !== 0) {
      leftTowerWindows.push({
        x: 98 + col * 20,
        y: 168 + row * 18,
        fill: (row * col) % 3 === 0 ? 'url(#warmWindow)' : 'url(#cyanWindow)',
        animClass: animClasses[(row + col) % animClasses.length],
      });
    }
  }
}

const leftCenterWindows = [];
for (let row = 0; row < 21; row++) {
  for (let col = 0; col < 5; col++) {
    if ((row * 2 + col) % 4 !== 0) {
      leftCenterWindows.push({
        x: 268 + col * 22,
        y: 172 + row * 19,
        fill: (row + col) % 2 === 0 ? 'url(#cyanWindow)' : 'url(#warmWindow)',
        animClass: animClasses[(row * 3 + col) % animClasses.length],
      });
    }
  }
}

const rightCenterWindows = [];
for (let row = 0; row < 21; row++) {
  for (let col = 0; col < 5; col++) {
    if ((row + col * 2) % 4 !== 0) {
      rightCenterWindows.push({
        x: 1216 + col * 22,
        y: 168 + row * 19,
        fill: (row + col) % 3 === 0 ? 'url(#warmWindow)' : 'url(#cyanWindow)',
        animClass: animClasses[(row + col * 2) % animClasses.length],
      });
    }
  }
}

const rightFloorBands = Array.from({ length: 18 }, (_, i) => ({
  y: 222 + i * 20,
  fill: i % 3 === 0 ? 'url(#warmWindow)' : 'url(#cyanWindow)',
  animClass: animClasses[i % animClasses.length],
}));

const handleSubmit = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    const data = await login(loginInput.value, password.value);
    router.push(getFirstAllowedPath(data?.user));
  } catch (error) {
    if (error.response?.data?.message) {
      errorMessage.value = error.response.data.message;
    } else if (error.response?.data?.errors?.login) {
      errorMessage.value = error.response.data.errors.login[0];
    } else {
      errorMessage.value = 'Gagal masuk. Periksa kembali kredensial Anda.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* Twinkling Stars */
.star-dot {
  animation: twinkle infinite ease-in-out;
}
@keyframes twinkle {
  0%, 100% { opacity: 0.2; transform: scale(0.85); }
  50% { opacity: 0.95; transform: scale(1.2); }
}

/* Drifting Clouds */
.cloud-slow {
  animation: cloudDrift 36s ease-in-out infinite alternate;
}
@keyframes cloudDrift {
  0% { transform: translateX(-25px); }
  100% { transform: translateX(25px); }
}

/* Office Window Light Animations (Turning On/Off Like Real Active Offices) */
.win-pulse-1 {
  animation: windowGlow 5.5s ease-in-out infinite;
}
.win-pulse-2 {
  animation: windowGlow 7.5s ease-in-out 1.8s infinite;
}
.win-pulse-3 {
  animation: windowGlow 9s ease-in-out 3.2s infinite;
}
.win-steady {
  opacity: 0.72;
}
@keyframes windowGlow {
  0%, 100% { opacity: 0.85; }
  45% { opacity: 0.12; }
  75% { opacity: 0.92; }
}

/* Panoramic Glass Elevators Moving Up & Down */
.elevator-cab-1 {
  animation: elevatorMove1 11s ease-in-out infinite alternate;
}
.elevator-cab-2 {
  animation: elevatorMove2 14s ease-in-out infinite alternate;
}
@keyframes elevatorMove1 {
  0% { transform: translateY(0px); }
  45% { transform: translateY(190px); }
  55% { transform: translateY(190px); }
  100% { transform: translateY(340px); }
}
@keyframes elevatorMove2 {
  0% { transform: translateY(180px); }
  50% { transform: translateY(-150px); }
  100% { transform: translateY(60px); }
}

/* Rooftop Aviation Beacon Lights */
.beacon-blink {
  animation: beaconPulse 1.8s ease-in-out infinite;
}
.beacon-blink-delayed {
  animation: beaconPulse 2.2s ease-in-out 0.9s infinite;
}
@keyframes beaconPulse {
  0%, 100% { opacity: 1; filter: drop-shadow(0 0 6px #ef4444); }
  50% { opacity: 0.2; }
}

/* Rooftop Communication Radar Rings */
.radar-ring {
  transform-origin: 330px 65px;
  animation: radarExpand 3.2s ease-out infinite;
}
.radar-ring-delayed {
  transform-origin: 330px 65px;
  animation: radarExpand 3.2s ease-out 1.6s infinite;
}
@keyframes radarExpand {
  0% { transform: scale(0.3); opacity: 0.9; }
  100% { transform: scale(2.8); opacity: 0; }
}

/* Sweeping Architectural Spotlights */
.searchlight-left {
  transform-origin: 260px 600px;
  animation: sweepLeft 10s ease-in-out infinite alternate;
}
.searchlight-right {
  transform-origin: 1320px 600px;
  animation: sweepRight 12s ease-in-out infinite alternate;
}
@keyframes sweepLeft {
  0% { transform: rotate(-8deg); }
  100% { transform: rotate(10deg); }
}
@keyframes sweepRight {
  0% { transform: rotate(8deg); }
  100% { transform: rotate(-10deg); }
}

/* Highway Traffic Light Trails */
.traffic-right-1 {
  animation: driveRight 9s linear infinite;
}
.traffic-right-2 {
  animation: driveRight 13s linear 3s infinite;
}
.traffic-left-1 {
  animation: driveLeft 10s linear infinite;
}
.traffic-left-2 {
  animation: driveLeft 14s linear 4s infinite;
}
@keyframes driveRight {
  0% { transform: translateX(-250px); }
  100% { transform: translateX(1650px); }
}
@keyframes driveLeft {
  0% { transform: translateX(350px); }
  100% { transform: translateX(-1650px); }
}
</style>

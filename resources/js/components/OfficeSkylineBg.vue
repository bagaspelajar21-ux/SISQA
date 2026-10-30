<template>
  <div class="absolute inset-0 pointer-events-none overflow-hidden select-none" :class="opacityClass">
    <svg
      class="absolute bottom-0 left-0 w-full h-full min-h-[160px] object-cover"
      viewBox="0 0 1600 260"
      preserveAspectRatio="xMidYMax slice"
      xmlns="http://www.w3.org/2000/svg"
    >
      <defs>
        <linearGradient :id="`bgTower-${uid}`" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#1e3a8a" stop-opacity="0.45" />
          <stop offset="100%" stop-color="#07132c" stop-opacity="0.8" />
        </linearGradient>
        <linearGradient :id="`midTower-${uid}`" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#1d4ed8" stop-opacity="0.65" />
          <stop offset="100%" stop-color="#091836" stop-opacity="0.92" />
        </linearGradient>
        <linearGradient :id="`frontTower-${uid}`" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.42" />
          <stop offset="50%" stop-color="#1e3f7c" stop-opacity="0.82" />
          <stop offset="100%" stop-color="#08152e" stop-opacity="0.96" />
        </linearGradient>
        <linearGradient :id="`beam-${uid}`" x1="0" y1="1" x2="0" y2="0">
          <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.22" />
          <stop offset="100%" stop-color="#38bdf8" stop-opacity="0" />
        </linearGradient>
      </defs>

      <!-- Sweeping Architectural Spotlights -->
      <polygon class="sky-beam-left" points="380,250 290,0 440,0" :fill="`url(#beam-${uid})`" />
      <polygon class="sky-beam-right" points="1180,250 1090,0 1250,0" :fill="`url(#beam-${uid})`" />

      <!-- Distant Skyline Silhouette -->
      <g :fill="`url(#bgTower-${uid})`">
        <rect x="30" y="95" width="75" height="155" rx="2" />
        <rect x="120" y="65" width="90" height="185" rx="2" />
        <rect x="340" y="80" width="85" height="170" rx="2" />
        <rect x="445" y="110" width="80" height="140" rx="2" />
        <rect x="560" y="105" width="95" height="145" rx="2" />
        <rect x="670" y="75" width="110" height="175" rx="2" />
        <rect x="920" y="85" width="90" height="165" rx="2" />
        <rect x="1150" y="60" width="105" height="190" rx="2" />
        <rect x="1420" y="95" width="70" height="155" rx="2" />
        <rect x="1500" y="75" width="85" height="175" rx="2" />
      </g>

      <!-- Left Corporate Tower -->
      <g>
        <line x1="265" y1="22" x2="265" y2="55" stroke="#60a5fa" stroke-width="2.5" />
        <circle class="sky-beacon" cx="265" cy="20" r="3.5" fill="#ef4444" />
        <rect x="220" y="55" width="90" height="195" rx="3" :fill="`url(#midTower-${uid})`" stroke="#38bdf8" stroke-opacity="0.3" />
        <g fill="#fde68a" class="sky-win-1">
          <rect x="232" y="72" width="12" height="6" rx="1" />
          <rect x="252" y="72" width="12" height="6" rx="1" />
          <rect x="272" y="92" width="12" height="6" rx="1" />
          <rect x="232" y="112" width="12" height="6" rx="1" />
          <rect x="252" y="132" width="12" height="6" rx="1" />
          <rect x="272" y="152" width="12" height="6" rx="1" />
          <rect x="232" y="172" width="12" height="6" rx="1" />
        </g>
        <g fill="#38bdf8" class="sky-win-2">
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
        <circle class="sky-radar" cx="855" cy="18" r="12" stroke="#38bdf8" stroke-width="1.5" fill="none" />
        <rect x="805" y="48" width="100" height="202" rx="4" :fill="`url(#frontTower-${uid})`" stroke="#38bdf8" stroke-opacity="0.4" />
        <rect x="816" y="56" width="78" height="4" rx="2" fill="#38bdf8" opacity="0.85" />

        <!-- Panoramic Elevator Shaft -->
        <rect x="888" y="68" width="9" height="175" fill="#071226" stroke="#38bdf8" stroke-opacity="0.35" />
        <rect class="sky-elevator-1" x="889.5" y="75" width="6" height="13" rx="1.5" fill="#fde68a" />

        <!-- Office Windows -->
        <g fill="#e0f2fe" class="sky-win-2">
          <rect x="816" y="72" width="15" height="6" rx="1" />
          <rect x="838" y="72" width="15" height="6" rx="1" />
          <rect x="860" y="90" width="15" height="6" rx="1" />
          <rect x="816" y="108" width="15" height="6" rx="1" />
          <rect x="838" y="126" width="15" height="6" rx="1" />
          <rect x="860" y="144" width="15" height="6" rx="1" />
          <rect x="816" y="162" width="15" height="6" rx="1" />
          <rect x="838" y="180" width="15" height="6" rx="1" />
        </g>
        <g fill="#fde68a" class="sky-win-3">
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
        <polygon points="1025,52 1125,30 1125,250 1025,250" :fill="`url(#midTower-${uid})`" stroke="#38bdf8" stroke-opacity="0.35" />
        <g stroke="#38bdf8" stroke-opacity="0.22" stroke-width="1.2">
          <line x1="1025" y1="75" x2="1125" y2="125" />
          <line x1="1125" y1="75" x2="1025" y2="125" />
          <line x1="1025" y1="145" x2="1125" y2="195" />
          <line x1="1125" y1="145" x2="1025" y2="195" />
        </g>
        <g fill="#38bdf8" class="sky-win-1">
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
        <circle class="sky-beacon" cx="1335" cy="13" r="3.5" fill="#ef4444" />
        <rect x="1275" y="42" width="125" height="208" rx="4" :fill="`url(#frontTower-${uid})`" stroke="#38bdf8" stroke-opacity="0.4" />
        <rect x="1285" y="52" width="105" height="5" rx="2.5" fill="#38bdf8" opacity="0.85" />

        <!-- Elevator Shaft 2 -->
        <rect x="1286" y="66" width="9" height="176" fill="#071226" stroke="#38bdf8" stroke-opacity="0.35" />
        <rect class="sky-elevator-2" x="1287.5" y="140" width="6" height="13" rx="1.5" fill="#38bdf8" />

        <!-- Office Windows -->
        <g fill="#fde68a" class="sky-win-1">
          <rect x="1305" y="70" width="18" height="6" rx="1.5" />
          <rect x="1332" y="70" width="18" height="6" rx="1.5" />
          <rect x="1360" y="88" width="18" height="6" rx="1.5" />
          <rect x="1305" y="106" width="18" height="6" rx="1.5" />
          <rect x="1332" y="124" width="18" height="6" rx="1.5" />
          <rect x="1360" y="142" width="18" height="6" rx="1.5" />
          <rect x="1305" y="160" width="18" height="6" rx="1.5" />
          <rect x="1332" y="178" width="18" height="6" rx="1.5" />
        </g>
        <g fill="#38bdf8" class="sky-win-3">
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
      <rect x="0" y="242" width="1600" height="18" fill="#040a18" opacity="0.9" />
      <line x1="0" y1="242" x2="1600" y2="242" stroke="#38bdf8" stroke-opacity="0.45" stroke-width="1.5" />
      <g class="sky-traffic-right">
        <rect x="120" y="247" width="48" height="3.5" rx="1.75" fill="#fde68a" />
        <rect x="164" y="246.5" width="12" height="4.5" rx="2" fill="#ffffff" />
      </g>
      <g class="sky-traffic-left">
        <rect x="1280" y="253" width="52" height="3.5" rx="1.75" fill="#f43f5e" />
        <rect x="1272" y="252.5" width="10" height="4.5" rx="2" fill="#fda4af" />
      </g>
    </svg>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  opacityClass: {
    type: String,
    default: 'opacity-80',
  },
});

const uid = computed(() => Math.random().toString(36).slice(2, 8));
</script>

<style scoped>
.sky-win-1 {
  animation: skyWindowGlow 5s ease-in-out infinite;
}
.sky-win-2 {
  animation: skyWindowGlow 7s ease-in-out 1.5s infinite;
}
.sky-win-3 {
  animation: skyWindowGlow 6s ease-in-out 3s infinite;
}
@keyframes skyWindowGlow {
  0%, 100% { opacity: 0.85; }
  50% { opacity: 0.2; }
}

.sky-elevator-1 {
  animation: skyElev1 9s ease-in-out infinite alternate;
}
.sky-elevator-2 {
  animation: skyElev2 11s ease-in-out infinite alternate;
}
@keyframes skyElev1 {
  0% { transform: translateY(0px); }
  100% { transform: translateY(135px); }
}
@keyframes skyElev2 {
  0% { transform: translateY(70px); }
  100% { transform: translateY(-65px); }
}

.sky-beacon {
  animation: skyBeaconBlink 1.8s ease-in-out infinite;
}
@keyframes skyBeaconBlink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.2; }
}

.sky-radar {
  transform-origin: 855px 18px;
  animation: skyRadarPulse 3s ease-out infinite;
}
@keyframes skyRadarPulse {
  0% { transform: scale(0.3); opacity: 0.9; }
  100% { transform: scale(2.6); opacity: 0; }
}

.sky-beam-left {
  transform-origin: 380px 250px;
  animation: skySweepLeft 10s ease-in-out infinite alternate;
}
.sky-beam-right {
  transform-origin: 1180px 250px;
  animation: skySweepRight 12s ease-in-out infinite alternate;
}
@keyframes skySweepLeft {
  0% { transform: rotate(-8deg); }
  100% { transform: rotate(9deg); }
}
@keyframes skySweepRight {
  0% { transform: rotate(8deg); }
  100% { transform: rotate(-9deg); }
}

.sky-traffic-right {
  animation: skyDriveRight 10s linear infinite;
}
.sky-traffic-left {
  animation: skyDriveLeft 11s linear infinite;
}
@keyframes skyDriveRight {
  0% { transform: translateX(-200px); }
  100% { transform: translateX(1600px); }
}
@keyframes skyDriveLeft {
  0% { transform: translateX(300px); }
  100% { transform: translateX(-1500px); }
}
</style>


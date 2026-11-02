<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95 translate-y-2"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 translate-y-2"
    >
      <div
        v-if="dialogState.current"
        class="fixed inset-0 z-[200] flex items-center justify-center overflow-y-auto bg-slate-950/65 p-4 backdrop-blur-sm"
        @click.self="settleDialog(false)"
        @keydown.esc="settleDialog(false)"
      >
        <section
          class="w-full max-w-md overflow-hidden rounded-3xl border border-white/70 bg-white shadow-2xl shadow-slate-950/30 ring-1 ring-slate-900/10"
          role="alertdialog"
          aria-modal="true"
          :aria-label="dialogState.current.title"
        >
          <div
            :class="[
              'h-1.5 bg-gradient-to-r',
              dialogState.current.variant === 'danger'
                ? 'from-rose-500 via-red-500 to-orange-400'
                : dialogState.current.variant === 'success'
                  ? 'from-emerald-500 via-teal-500 to-cyan-400'
                  : 'from-sky-500 via-blue-600 to-violet-500',
            ]"
          ></div>
          <div class="p-6 sm:p-7">
            <div class="flex items-start gap-4">
              <div
                :class="[
                  'flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ring-8',
                  dialogState.current.variant === 'danger'
                    ? 'bg-rose-50 text-rose-600 ring-rose-50/70'
                    : dialogState.current.variant === 'success'
                      ? 'bg-emerald-50 text-emerald-600 ring-emerald-50/70'
                      : 'bg-blue-50 text-blue-600 ring-blue-50/70',
                ]"
              >
                <svg v-if="dialogState.current.variant === 'danger'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0Z" />
                </svg>
                <svg v-else-if="dialogState.current.variant === 'success'" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" />
                </svg>
                <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 16v-4m0-4h.01M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
                </svg>
              </div>
              <div class="min-w-0 pt-0.5">
                <h2 class="text-base font-black tracking-tight text-slate-900">
                  {{ dialogState.current.title }}
                </h2>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">
                  {{ dialogState.current.message }}
                </p>
              </div>
            </div>

            <div class="mt-7 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
              <button
                v-if="dialogState.current.type === 'confirm'"
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200"
                @click="settleDialog(false)"
              >
                {{ dialogState.current.cancelText }}
              </button>
              <button
                type="button"
                autofocus
                :class="[
                  'rounded-xl px-4 py-2.5 text-sm font-extrabold text-white shadow-lg transition focus:outline-none focus:ring-4',
                  dialogState.current.variant === 'danger'
                    ? 'bg-gradient-to-r from-rose-600 to-red-600 shadow-rose-600/20 hover:from-rose-700 hover:to-red-700 focus:ring-rose-200'
                    : dialogState.current.variant === 'success'
                      ? 'bg-gradient-to-r from-emerald-600 to-teal-600 shadow-emerald-600/20 hover:from-emerald-700 hover:to-teal-700 focus:ring-emerald-200'
                      : 'bg-gradient-to-r from-blue-600 to-indigo-600 shadow-blue-600/20 hover:from-blue-700 hover:to-indigo-700 focus:ring-blue-200',
                ]"
                @click="settleDialog(true)"
              >
                {{ dialogState.current.confirmText }}
              </button>
            </div>
          </div>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { dialogState, settleDialog } from '../dialog';
</script>

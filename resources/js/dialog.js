import { reactive } from 'vue';

const dialogState = reactive({
  current: null,
});

const dialogQueue = [];

function showNextDialog() {
  if (dialogState.current || dialogQueue.length === 0) {
    return;
  }

  dialogState.current = dialogQueue.shift();
}

function enqueueDialog(dialog) {
  return new Promise((resolve) => {
    dialogQueue.push({ ...dialog, resolve });
    showNextDialog();
  });
}

export function showAlert(message, options = {}) {
  return enqueueDialog({
    type: 'alert',
    title: options.title || (options.variant === 'success' ? 'Berhasil' : 'Informasi'),
    message: String(message ?? ''),
    confirmText: options.confirmText || 'Mengerti',
    variant: options.variant || 'info',
  });
}

export function showConfirm(message, options = {}) {
  return enqueueDialog({
    type: 'confirm',
    title: options.title || 'Konfirmasi Tindakan',
    message: String(message ?? ''),
    confirmText: options.confirmText || 'Ya, lanjutkan',
    cancelText: options.cancelText || 'Batal',
    variant: options.variant || 'danger',
  });
}

export function settleDialog(result) {
  const activeDialog = dialogState.current;
  if (!activeDialog) {
    return;
  }

  dialogState.current = null;
  activeDialog.resolve(result);
  queueMicrotask(showNextDialog);
}

export { dialogState };

export function openWhatsAppMessage(message) {
  const searchParams = new URLSearchParams();
  searchParams.set('text', message.normalize('NFC'));
  window.open(`https://api.whatsapp.com/send?${searchParams.toString()}`, '_blank', 'noopener,noreferrer');
}

export async function copyTextToClipboard(text) {
  let clipboardError;

  if (navigator.clipboard?.writeText) {
    try {
      await navigator.clipboard.writeText(text.normalize('NFC'));
      return;
    } catch (error) {
      clipboardError = error;
    }
  }

  const textArea = document.createElement('textarea');
  textArea.value = text.normalize('NFC');
  textArea.setAttribute('readonly', '');
  textArea.style.position = 'fixed';
  textArea.style.left = '-9999px';
  document.body.appendChild(textArea);

  try {
    textArea.select();

    if (!document.execCommand('copy')) {
      throw new Error('Clipboard copy command was rejected.');
    }
  } finally {
    document.body.removeChild(textArea);
  }

  if (clipboardError) {
    console.error('Clipboard API failed; copied text using the fallback.', clipboardError);
  }
}

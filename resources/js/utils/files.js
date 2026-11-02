import api from '../api';

export async function createAuthenticatedFileUrl(type, id, action = 'preview') {
  const response = await api.get(`/files/${type}/${id}/${action}`, {
    responseType: 'blob',
  });

  return URL.createObjectURL(response.data);
}

export async function downloadAuthenticatedFile(type, id, fileName) {
  const response = await api.get(`/files/${type}/${id}/download`, {
    responseType: 'blob',
  });
  const fileUrl = URL.createObjectURL(response.data);
  const link = document.createElement('a');

  link.href = fileUrl;
  link.download = fileName;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.setTimeout(() => URL.revokeObjectURL(fileUrl), 1000);
}

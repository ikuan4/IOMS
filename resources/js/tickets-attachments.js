// Ticket attachments UX
// - Paste image into textarea -> uploads to draft endpoint and shows a local preview
// - Works with SPA navigation (re-inits on spa:navigated)

import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

function getCsrfToken() {
  const el = document.querySelector('meta[name="csrf-token"]');
  return el ? el.getAttribute('content') : null;
}

function isImageFile(file) {
  return !!file && typeof file.type === 'string' && file.type.startsWith('image/');
}

function renderPendingAttachment(listEl, file, onRemove) {
  const wrapper = document.createElement('div');
  wrapper.style.border = '1px solid #e5e7eb';
  wrapper.style.borderRadius = '12px';
  wrapper.style.padding = '10px';
  wrapper.style.minWidth = '220px';
  wrapper.style.maxWidth = '320px';
  wrapper.style.background = '#fafafa';

  const title = document.createElement('div');
  title.textContent = file?.name || 'Attachment';
  title.style.fontWeight = '900';
  title.style.overflow = 'hidden';
  title.style.textOverflow = 'ellipsis';
  title.style.whiteSpace = 'nowrap';

  const status = document.createElement('div');
  status.textContent = 'Uploading...';
  status.style.color = '#6b7280';
  status.style.fontWeight = '800';
  status.style.fontSize = '12px';
  status.style.marginTop = '4px';

  wrapper.appendChild(title);
  wrapper.appendChild(status);

  const actions = document.createElement('div');
  actions.style.marginTop = '10px';
  actions.style.display = 'flex';
  actions.style.gap = '10px';
  actions.style.flexWrap = 'wrap';

  const removeBtn = document.createElement('button');
  removeBtn.type = 'button';
  removeBtn.textContent = 'Remove';
  removeBtn.style.background = '#ef4444';
  removeBtn.style.color = '#fff';
  removeBtn.style.padding = '8px 12px';
  removeBtn.style.border = 'none';
  removeBtn.style.borderRadius = '10px';
  removeBtn.style.fontWeight = '900';
  removeBtn.style.fontSize = '12px';
  removeBtn.style.cursor = 'pointer';

  removeBtn.addEventListener('click', async () => {
    removeBtn.disabled = true;
    try {
      if (typeof onRemove === 'function') {
        await onRemove();
      }
      wrapper.remove();
    } catch (e) {
      removeBtn.disabled = false;
      status.textContent = 'Remove failed';
      status.style.color = '#dc2626';
      console.error(e);
    }
  });

  actions.appendChild(removeBtn);
  wrapper.appendChild(actions);

  if (isImageFile(file)) {
    const img = document.createElement('img');
    img.alt = file.name || 'image';
    img.src = URL.createObjectURL(file);
    img.style.marginTop = '8px';
    img.style.maxWidth = '100%';
    img.style.maxHeight = '180px';
    img.style.borderRadius = '10px';
    img.style.border = '1px solid #e5e7eb';
    wrapper.appendChild(img);

    // Release object URL when loaded
    img.addEventListener('load', () => {
      try { URL.revokeObjectURL(img.src); } catch (e) {}
    }, { once: true });
  }

  listEl.appendChild(wrapper);

  return {
    setDone: () => { status.textContent = 'Uploaded'; status.style.color = '#16a34a'; },
    setError: (message) => { status.textContent = message || 'Upload failed'; status.style.color = '#dc2626'; },
    setRemoving: () => { status.textContent = 'Removing...'; status.style.color = '#6b7280'; },
    setRemoveEnabled: (enabled) => { removeBtn.disabled = !enabled; },
    setOnRemove: (fn) => {
      // eslint-disable-next-line no-param-reassign
      onRemove = fn;
    },
  };
}

async function uploadDraftFile(uploadUrl, draftKey, file) {
  const csrf = getCsrfToken();

  const form = new FormData();
  form.append('draft_key', draftKey);
  form.append('file', file);

  const res = await fetch(uploadUrl, {
    method: 'POST',
    headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {},
    body: form,
    credentials: 'same-origin',
  });

  if (!res.ok) {
    let text = '';
    try { text = await res.text(); } catch (e) {}
    throw new Error(text || `Upload failed (${res.status})`);
  }

  return await res.json();
}

async function deleteDraftLink(deleteUrl, draftKey, storedFileId) {
  const csrf = getCsrfToken();

  const form = new FormData();
  form.append('draft_key', draftKey);
  form.append('stored_file_id', String(storedFileId));

  const res = await fetch(deleteUrl, {
    method: 'POST',
    headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {},
    body: form,
    credentials: 'same-origin',
  });

  if (!res.ok) {
    let text = '';
    try { text = await res.text(); } catch (e) {}
    throw new Error(text || `Delete failed (${res.status})`);
  }

  return await res.json();
}

function bindPasteUploader(textarea) {
  if (!textarea || textarea.dataset.ticketAttachmentsBound === '1') return;

  const uploadUrl = textarea.getAttribute('data-ticket-attachments-upload-url');
  const deleteUrl = textarea.getAttribute('data-ticket-attachments-delete-url');
  const draftKey = textarea.getAttribute('data-ticket-attachments-draft-key');
  const listSelector = textarea.getAttribute('data-ticket-attachments-list');

  if (!uploadUrl || !draftKey || !listSelector) return;

  const listEl = document.querySelector(listSelector);
  if (!listEl) return;

  textarea.addEventListener('paste', async (e) => {
    const dt = e.clipboardData;
    if (!dt || !dt.items) return;

    const files = [];
    for (const item of dt.items) {
      if (item.kind === 'file') {
        const f = item.getAsFile();
        if (f && isImageFile(f)) {
          files.push(f);
        }
      }
    }

    if (files.length === 0) return;

    // Prevent raw image blob paste into textarea
    e.preventDefault();

    for (const file of files) {
      // Initially remove is local-only (no server object yet)
      const ui = renderPendingAttachment(listEl, file, null);
      try {
        const uploaded = await uploadDraftFile(uploadUrl, draftKey, file);
        ui.setDone();

        // Enable server-side remove once we have stored_file_id
        if (deleteUrl && uploaded && uploaded.stored_file_id) {
          ui.setOnRemove(async () => {
            ui.setRemoving();
            await deleteDraftLink(deleteUrl, draftKey, uploaded.stored_file_id);
          });
          ui.setRemoveEnabled(true);
        }
      } catch (err) {
        ui.setError('Upload failed');
        console.error(err);
      }
    }
  });

  textarea.dataset.ticketAttachmentsBound = '1';
}

export function initTicketAttachments() {
  const textareas = document.querySelectorAll('textarea[data-ticket-attachments-paste]');
  textareas.forEach(bindPasteUploader);

  // File picker label/count (match contracts module UI)
  document.querySelectorAll('input[type="file"][data-file-label-id]').forEach((input) => {
    if (input.dataset.filePickerBound === '1') return;

    input.addEventListener('change', () => {
      const labelId = input.getAttribute('data-file-label-id');
      const countId = input.getAttribute('data-file-count-id');
      const labelEl = labelId ? document.getElementById(labelId) : null;
      const countEl = countId ? document.getElementById(countId) : null;

      const fileCount = input.files ? input.files.length : 0;
      if (labelEl) {
        labelEl.textContent = fileCount > 0
          ? `${fileCount} file${fileCount > 1 ? 's' : ''} selected`
          : 'Choose Files';
      }
      if (countEl) {
        countEl.textContent = '';
      }
    });

    input.dataset.filePickerBound = '1';
  });

  // Date picker (match contracts module: flatpickr)
  document.querySelectorAll('input[data-ticket-date-picker]').forEach((input) => {
    try {
      if (input._flatpickr) {
        input._flatpickr.destroy();
      }
    } catch (e) {}

    const inlineStyle = input.getAttribute('style') || '';

    flatpickr(input, {
      dateFormat: 'Y-m-d',
      enableTime: false,
      altInput: true,
      altFormat: 'd M Y',
      allowInput: true,
      onReady: (_selectedDates, _dateStr, instance) => {
        // Flatpickr swaps the visible input when altInput=true; copy sizing/spacing styles.
        if (instance.altInput && inlineStyle) {
          instance.altInput.setAttribute('style', inlineStyle);
        }
      },
    });
  });
}

// Init on first load + SPA swaps
document.addEventListener('DOMContentLoaded', initTicketAttachments);
window.addEventListener('spa:navigated', initTicketAttachments);

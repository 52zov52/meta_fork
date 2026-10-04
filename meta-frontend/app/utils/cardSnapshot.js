import { toBlob } from 'html-to-image';

export const SNAPSHOT_EXCLUDE_ATTR = 'data-snapshot-exclude';

export async function copyElementSnapshotToClipboard(element, filename, options = {}) {
  if (!element) {
    throw new Error('Snapshot target is missing');
  }

  const captureBlob = options.captureBlob ?? defaultCaptureBlob;
  const writeClipboard = options.writeClipboard ?? defaultWriteClipboard;
  const downloadBlob = options.downloadBlob ?? defaultDownloadBlob;

  const blob = await captureBlob(element);
  if (!blob) {
    throw new Error('Failed to render snapshot');
  }

  const copied = await writeClipboard(blob);
  if (copied) {
    return { mode: 'clipboard', blob };
  }

  downloadBlob(blob, filename);
  return { mode: 'download', blob };
}

async function defaultCaptureBlob(element) {
  return toBlob(element, {
    cacheBust: true,
    pixelRatio: 2,
    filter: (node) => !isSnapshotExcluded(node),
  });
}

async function defaultWriteClipboard(blob) {
  try {
    if (typeof navigator === 'undefined' || !navigator.clipboard?.write || typeof ClipboardItem === 'undefined') {
      return false;
    }

    const mimeType = blob.type || 'image/png';
    const item = new ClipboardItem({ [mimeType]: blob });
    await navigator.clipboard.write([item]);
    return true;
  } catch {
    return false;
  }
}

function defaultDownloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');

  link.href = url;
  link.download = filename;
  link.rel = 'noopener';
  link.click();

  setTimeout(() => URL.revokeObjectURL(url), 0);
}

function isSnapshotExcluded(node) {
  return node instanceof Element && node.hasAttribute(SNAPSHOT_EXCLUDE_ATTR);
}

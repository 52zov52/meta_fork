import test from 'node:test';
import assert from 'node:assert/strict';
import { copyElementSnapshotToClipboard } from '../app/utils/cardSnapshot.js';

test('falls back to download when clipboard writing is unavailable', async () => {
  const blob = new Blob(['png-bytes'], { type: 'image/png' });
  let downloaded = null;

  const result = await copyElementSnapshotToClipboard(
    { tagName: 'DIV' },
    'dashboard-card.png',
    {
      captureBlob: async () => blob,
      writeClipboard: async () => false,
      downloadBlob: (nextBlob, filename) => {
        downloaded = { blob: nextBlob, filename };
      },
    },
  );

  assert.equal(result.mode, 'download');
  assert.equal(downloaded?.filename, 'dashboard-card.png');
  assert.equal(downloaded?.blob, blob);
});

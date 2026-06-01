import type { Payload } from 'payload';

import { productDrawingReviewStats, type ProductDraftData } from '../lib/productReviewReadiness';

const batchStatSyncQueues = new Map<string, Promise<void>>();

export async function syncProductImportBatchStats(payload: Payload, batchId: string) {
  const previousSync = batchStatSyncQueues.get(batchId) ?? Promise.resolve();
  const nextSync = previousSync
    .catch(() => undefined)
    .then(() => syncProductImportBatchStatsNow(payload, batchId));

  batchStatSyncQueues.set(batchId, nextSync);

  try {
    await nextSync;
  } finally {
    if (batchStatSyncQueues.get(batchId) === nextSync) {
      batchStatSyncQueues.delete(batchId);
    }
  }
}

async function syncProductImportBatchStatsNow(payload: Payload, batchId: string) {
  const products = await payload.find({
    collection: 'products',
    depth: 0,
    limit: 1000,
    overrideAccess: true,
    pagination: false,
    where: { importBatchId: { equals: batchId } },
  });

  const batches = await payload.find({
    collection: 'product-import-batches',
    depth: 0,
    limit: 1,
    overrideAccess: true,
    pagination: false,
    where: { batchId: { equals: batchId } },
  });

  const batch = batches.docs[0];
  if (!batch) return;

  let needsReviewCount = 0;
  let inReviewCount = 0;
  let verifiedCount = 0;
  let drawingReviewNeededCount = 0;
  let drawingReviewedCount = 0;

  for (const product of products.docs as unknown as ProductDraftData[]) {
    if (product.parameterReviewStatus === 'verified-from-source') verifiedCount += 1;
    else if (product.parameterReviewStatus === 'in-review') inReviewCount += 1;
    else needsReviewCount += 1;

    const drawingStats = productDrawingReviewStats(product.drawingImages);
    drawingReviewNeededCount += drawingStats.neededCount;
    drawingReviewedCount += drawingStats.reviewedCount;
  }

  await payload.update({
    collection: 'product-import-batches',
    id: batch.id,
    overrideAccess: true,
    data: {
      productCount: products.docs.length,
      needsReviewCount,
      inReviewCount,
      verifiedCount,
      drawingReviewNeededCount,
      drawingReviewedCount,
    },
  });
}

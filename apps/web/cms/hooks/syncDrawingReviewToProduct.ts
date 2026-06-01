import type { CollectionConfig } from 'payload';
import { sql } from '@payloadcms/db-postgres';

import { productDrawingReviewStats, productFrontendChecklist, type ProductDraftData } from '../lib/productReviewReadiness';

type ReviewDoc = ProductDraftData;
type ProductDoc = { drawingImages?: ReviewDoc[]; [key: string]: unknown };

type AfterChangeHook = NonNullable<NonNullable<CollectionConfig['hooks']>['afterChange']>[number];
type BeforeChangeHook = NonNullable<NonNullable<CollectionConfig['hooks']>['beforeChange']>[number];
type DrizzleExecutor = { execute: (statement: ReturnType<typeof sql>) => Promise<unknown> };

export const stampDrawingReviewSyncBeforeChange: BeforeChangeHook = async ({ data }) => {
  if (!data) return data;

  return {
    ...data,
    lastSyncedAt: new Date().toISOString(),
    syncStatus: 'synced',
  };
};

export const syncDrawingReviewToProductAfterChange: AfterChangeHook = async ({ doc, req }) => {
  const review = doc as ReviewDoc;
  const productId = relationId(review.product);
  const drawingRowId = typeof review.drawingRowId === 'string' ? review.drawingRowId : '';
  if (!productId || !drawingRowId) return doc;

  const product = await req.payload.findByID({
    collection: 'products',
    depth: 0,
    id: productId,
    overrideAccess: true,
  }) as unknown as ProductDoc;

  const drawingImages = Array.isArray(product.drawingImages) ? product.drawingImages : [];
  let changed = false;
  const nextDrawingImages = drawingImages.map((item) => {
    if (item?.id !== drawingRowId) return item;
    const next = {
      ...item,
      reviewStatus: review.reviewStatus,
      parameterMatchStatus: review.parameterMatchStatus,
      extractedText: review.extractedText,
      reviewNotes: review.reviewNotes,
      reviewMethod: review.reviewMethod,
      reviewer: review.reviewer,
      reviewedAt: review.reviewedAt,
    };
    changed = JSON.stringify(next) !== JSON.stringify(item);
    return next;
  });

  if (!changed) return doc;

  const nextProduct = { ...product, drawingImages: nextDrawingImages };
  const checklist = productFrontendChecklist(nextProduct);
  const drawingReview = productDrawingReviewStats(nextDrawingImages);
  const db = getDrizzleExecutor(req.payload.db);

  await db.execute(sql`
    UPDATE "products_drawing_images"
    SET
      "review_status" = ${textOrNull(review.reviewStatus)},
      "parameter_match_status" = ${textOrNull(review.parameterMatchStatus)},
      "extracted_text" = ${textOrNull(review.extractedText)},
      "review_notes" = ${textOrNull(review.reviewNotes)},
      "review_method" = ${textOrNull(review.reviewMethod) || 'manual'},
      "reviewer" = ${textOrNull(review.reviewer)},
      "reviewed_at" = ${dateOrNull(review.reviewedAt)}
    WHERE "_parent_id" = ${productId}
      AND "id" = ${drawingRowId}
  `);

  await db.execute(sql`
    UPDATE "products"
    SET
      "frontend_content_status" = ${checklist.status},
      "frontend_missing_fields_note" = ${checklist.note},
      "drawing_review_status" = ${drawingReview.status},
      "drawing_review_summary" = ${drawingReview.summary},
      "drawing_review_needed_count" = ${drawingReview.neededCount},
      "drawing_reviewed_count" = ${drawingReview.reviewedCount},
      "updated_at" = now()
    WHERE "id" = ${productId}
  `);

  const batchId = typeof product.importBatchId === 'string' ? product.importBatchId.trim() : '';
  if (batchId) {
    try {
      await syncImportBatchDrawingStats(db, batchId);
    } catch (error) {
      req.payload.logger.warn({ err: error, batchId }, 'Failed to refresh product import batch drawing stats');
    }
  }

  return doc;
};

function relationId(value: unknown): number | string | null {
  if (typeof value === 'number' || typeof value === 'string') return value;
  if (value && typeof value === 'object' && 'id' in value) {
    const id = (value as { id?: unknown }).id;
    if (typeof id === 'number' || typeof id === 'string') return id;
  }
  return null;
}

function textOrNull(value: unknown): string | null {
  return typeof value === 'string' ? value : null;
}

function dateOrNull(value: unknown): Date | string | null {
  if (value instanceof Date) return value;
  return typeof value === 'string' && value.trim() ? value : null;
}

function getDrizzleExecutor(db: unknown): DrizzleExecutor {
  const drizzle = (db as { drizzle?: DrizzleExecutor } | undefined)?.drizzle;
  if (!drizzle) throw new Error('Postgres database executor is not available for drawing review sync.');
  return drizzle;
}

async function syncImportBatchDrawingStats(db: DrizzleExecutor, batchId: string) {
  await db.execute(sql`
    WITH product_stats AS (
      SELECT
        count(*) AS product_count,
        count(*) FILTER (WHERE "parameter_review_status" = 'verified-from-source') AS verified_count,
        count(*) FILTER (WHERE "parameter_review_status" = 'in-review') AS in_review_count,
        count(*) FILTER (
          WHERE coalesce("parameter_review_status", 'needs-review') NOT IN ('verified-from-source', 'in-review')
        ) AS needs_review_count
      FROM "products"
      WHERE "import_batch_id" = ${batchId}
    ),
    drawing_stats AS (
      SELECT
        0::int AS drawing_review_needed_count,
        count(pdi.id)::int AS drawing_reviewed_count
      FROM "products_drawing_images" pdi
      JOIN "products" p ON p.id = pdi."_parent_id"
      WHERE p."import_batch_id" = ${batchId}
    )
    UPDATE "product_import_batches" pib
    SET
      "product_count" = coalesce(ps.product_count, 0),
      "needs_review_count" = coalesce(ps.needs_review_count, 0),
      "in_review_count" = coalesce(ps.in_review_count, 0),
      "verified_count" = coalesce(ps.verified_count, 0),
      "drawing_review_needed_count" = coalesce(ds.drawing_review_needed_count, 0),
      "drawing_reviewed_count" = coalesce(ds.drawing_reviewed_count, 0),
      "updated_at" = now()
    FROM product_stats ps, drawing_stats ds
    WHERE pib."batch_id" = ${batchId}
  `);
}

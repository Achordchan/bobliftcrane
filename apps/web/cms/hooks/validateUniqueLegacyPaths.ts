import type { CollectionBeforeValidateHook } from 'payload';

type LegacyCollectionSlug = 'products' | 'product-categories' | 'articles' | 'cases' | 'pages' | 'redirects';

type DraftData = Record<string, unknown>;

type PathOwner = {
  label: string;
  slug: LegacyCollectionSlug;
};

const pathOwners: PathOwner[] = [
  { slug: 'products', label: '产品' },
  { slug: 'product-categories', label: '产品分类' },
  { slug: 'articles', label: '新闻文章' },
  { slug: 'cases', label: '客户案例' },
  { slug: 'pages', label: '独立页面' },
  { slug: 'redirects', label: 'URL 兼容规则' },
];

export function validateUniqueLegacyPaths(currentCollection: LegacyCollectionSlug): CollectionBeforeValidateHook {
  return async ({ data, originalDoc, req }) => {
    if (!data) return data;

    const draft = { ...(originalDoc as DraftData | undefined), ...data };
    const paths = candidatePaths(currentCollection, draft);
    if (paths.length === 0) return data;

    const duplicateInDraft = firstDuplicate(paths);
    if (duplicateInDraft) {
      throw new Error(`公开 URL / 历史别名路径重复：${duplicateInDraft}。请保留一个主路径，其他路径放到历史别名里且不要重复。`);
    }

    if (currentCollection === 'redirects' && textValue(draft.status) !== 'published') return data;

    const currentId = docId(originalDoc) ?? docId(data);
    const conflicts = [];
    for (const path of paths) {
      const conflict = await findPathConflict(req.payload, path, currentCollection, currentId);
      if (conflict) conflicts.push(`${path} 已被${conflict.label} #${conflict.id} 使用`);
    }

    if (conflicts.length > 0) {
      throw new Error(`旧站 URL 冲突：${conflicts.join('；')}。同一个公开链接只能绑定到一个内容或一条 URL 兼容规则。`);
    }

    return data;
  };
}

function textValue(value: unknown) {
  return typeof value === 'string' ? value.trim() : '';
}

function candidatePaths(collection: LegacyCollectionSlug, draft: DraftData): string[] {
  const values = collection === 'redirects'
    ? [draft.sourcePath]
    : [draft.legacyPath, ...legacyAliasValues(draft.legacyAliases)];

  return values.flatMap((value) => {
    const path = normalizePath(value);
    return path ? [path] : [];
  });
}

function legacyAliasValues(value: unknown): unknown[] {
  if (!Array.isArray(value)) return [];
  return value.map((item) => (item && typeof item === 'object' ? (item as DraftData).path : undefined));
}

function normalizePath(value: unknown): string | null {
  if (typeof value !== 'string') return null;
  const raw = value.trim();
  if (!raw) return null;

  let pathname = raw;
  if (/^https?:\/\//i.test(raw)) {
    try {
      pathname = new URL(raw).pathname;
    } catch {
      pathname = raw;
    }
  }

  const normalized = (pathname.startsWith('/') ? pathname : `/${pathname}`)
    .replace(/\/+/g, '/')
    .replace(/\/$/, '');

  return normalized || '/';
}

function firstDuplicate(paths: string[]) {
  const seen = new Set<string>();
  for (const path of paths) {
    if (seen.has(path)) return path;
    seen.add(path);
  }
  return null;
}

async function findPathConflict(payload: CollectionBeforeValidateHook extends (args: infer Args) => unknown ? Args extends { req: { payload: infer Payload } } ? Payload : never : never, path: string, currentCollection: LegacyCollectionSlug, currentId: string | null) {
  for (const owner of pathOwners) {
    const result = await payload.find({
      collection: owner.slug as never,
      depth: 0,
      limit: 2,
      where: owner.slug === 'redirects'
        ? { and: [{ sourcePath: { equals: path } }, { status: { equals: 'published' } }] }
        : {
            or: [
              { legacyPath: { equals: path } },
              { 'legacyAliases.path': { equals: path } },
            ],
          },
    });

    const conflict = result.docs.find((doc: unknown) => {
      const id = docId(doc);
      return !(owner.slug === currentCollection && currentId && id === currentId);
    });

    if (conflict) return { ...owner, id: docId(conflict) ?? 'unknown' };
  }

  return null;
}

function docId(value: unknown): string | null {
  if (!value || typeof value !== 'object') return null;
  const id = (value as DraftData).id;
  if (typeof id === 'number' || typeof id === 'string') return String(id);
  return null;
}

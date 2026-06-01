export type ProductDraftData = Record<string, unknown>;

export type DrawingReviewStats = {
  neededCount: number;
  reviewedCount: number;
  status: 'not-required' | 'needs-review' | 'partially-reviewed' | 'needs-correction' | 'reviewed';
  summary: string;
};

function hasText(value: unknown): boolean {
  if (typeof value === 'string') return value.trim().length > 0;
  if (value && typeof value === 'object') return Object.values(value).some(hasText);
  return false;
}

function hasReference(value: unknown): boolean {
  if (typeof value === 'number' || typeof value === 'string') return Boolean(value);
  return Boolean(value && typeof value === 'object' && 'id' in value);
}

function hasArrayItems(value: unknown): boolean {
  return Array.isArray(value) && value.some(Boolean);
}

function hasSpecItems(value: unknown): boolean {
  return Array.isArray(value) && value.some((item) => item && typeof item === 'object' && hasText((item as ProductDraftData).label) && hasText((item as ProductDraftData).value));
}

export function productDrawingReviewStats(value: unknown): DrawingReviewStats {
  const drawings = Array.isArray(value) ? value.filter(Boolean) : [];
  const neededCount = 0;
  const reviewedCount = drawings.length;

  if (drawings.length === 0) {
    return {
      neededCount: 0,
      reviewedCount: 0,
      status: 'not-required',
      summary: '未上传图纸/参数图；当前图纸资料不参与发布阻塞',
    };
  }

  return {
    neededCount,
    reviewedCount,
    status: 'not-required',
    summary: `${drawings.length} 张图纸/参数图已记录；当前图纸资料不参与发布阻塞`,
  };
}

export function productFrontendChecklist(data: ProductDraftData) {
  const missing: string[] = [];

  if (!hasReference(data.categoryRef)) missing.push('产品分类');
  if (!hasReference(data.coverImage)) missing.push('封面图');
  if (!hasText(data.intro) && !hasText((data.seo as ProductDraftData | undefined)?.metaDescription)) missing.push('简短介绍');
  if (!hasText(data.capacity) && typeof data.capacityValue !== 'number') missing.push('起重能力');
  if (!hasText(data.boomType)) missing.push('臂架类型');
  if (!hasArrayItems(data.applications)) missing.push('应用场景');
  if (!hasSpecItems(data.specs)) missing.push('规格参数');
  if (!hasArrayItems(data.gallery) && !hasArrayItems(data.drawingImages)) missing.push('产品图库或图纸');
  const reviewStatus = data.parameterReviewStatus;
  if (reviewStatus !== 'verified-from-source') missing.push('参数未按来源核对');
  const hasOnlyReviewMissing = missing.length === 1 && missing[0] === '参数未按来源核对';
  return {
    status: missing.length === 0 ? 'ready' : hasOnlyReviewMissing ? 'needs-parameter-review' : 'needs-content',
    note: missing.length > 0 ? missing.join('、') : '已具备网站测试需要的基础内容',
  };
}

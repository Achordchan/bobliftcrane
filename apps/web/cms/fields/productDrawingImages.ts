import type { Field } from 'payload';

export const productDrawingImagesField: Field = {
  name: 'drawingImages',
  type: 'array',
  admin: {
    description: { zh: '上传从旧站详情、图纸或参数图中提取的资料图片；这些图片只作为资料记录，不参与产品发布阻塞。', en: 'Drawings or parameter images are stored as supporting product material and do not block publishing.' },
    initCollapsed: true,
  },
  fields: [
    { name: 'image', type: 'upload', label: { zh: '图纸/参数图', en: 'Drawing Image' }, relationTo: 'media', required: true },
    { name: 'label', type: 'text', localized: true, label: { zh: '说明', en: 'Label' } },
    {
      name: 'sourceType',
      type: 'select',
      defaultValue: 'parameter-chart',
      label: { zh: '来源类型', en: 'Source Type' },
      options: [
        { label: { zh: '参数图 / 曲线图', en: 'Parameter Chart' }, value: 'parameter-chart' },
        { label: { zh: '结构图纸', en: 'Engineering Drawing' }, value: 'engineering-drawing' },
        { label: { zh: '产品细节图', en: 'Product Detail Photo' }, value: 'product-detail-photo' },
        { label: { zh: '其他来源图', en: 'Other Evidence Image' }, value: 'other' },
      ],
    },
    { name: 'sourceUrl', type: 'text', label: { zh: '旧站原图 URL', en: 'Legacy Source Image URL' } },
    {
      name: 'reviewStatus',
      type: 'select',
      defaultValue: 'needs-review',
      label: { zh: '资料记录状态', en: 'Evidence Record Status' },
      options: [
        { label: { zh: '已记录', en: 'Recorded' }, value: 'needs-review' },
        { label: { zh: '资料整理中', en: 'Record Cleanup In Progress' }, value: 'in-review' },
        { label: { zh: '资料已确认', en: 'Record Confirmed' }, value: 'reviewed' },
        { label: { zh: '无需处理', en: 'Not Required' }, value: 'not-required' },
      ],
    },
    {
      name: 'parameterMatchStatus',
      type: 'select',
      defaultValue: 'unchecked',
      label: { zh: '资料参数匹配记录', en: 'Parameter Match Note' },
      options: [
        { label: { zh: '未记录', en: 'Not Recorded' }, value: 'unchecked' },
        { label: { zh: '记录为与产品参数一致', en: 'Recorded as Matching Product Specs' }, value: 'matches-product-specs' },
        { label: { zh: '记录到参数差异', en: 'Spec Difference Recorded' }, value: 'spec-mismatch' },
        { label: { zh: '图片不清晰 / 暂无法记录', en: 'Unreadable Image' }, value: 'image-unreadable' },
      ],
    },
    {
      name: 'extractedText',
      type: 'textarea',
      admin: {
        description: {
          zh: '从图纸或参数图中摘录的关键文字、型号、起重量、工作半径等。',
          en: 'Key text recorded from the drawing or parameter image.',
        },
      },
      label: { zh: '图纸资料摘录', en: 'Drawing Evidence Notes' },
    },
    {
      name: 'reviewNotes',
      type: 'textarea',
      admin: {
        description: {
          zh: '记录这张资料图的来源、用途，以及是否需要回到产品规格里补充修改。',
          en: 'Record the source, usage, and any product-spec follow-up for this image.',
        },
      },
      label: { zh: '图纸资料备注', en: 'Drawing Evidence Notes' },
    },
    {
      name: 'reviewMethod',
      type: 'select',
      defaultValue: 'manual',
      label: { zh: '记录方式', en: 'Record Method' },
      options: [
        { label: { zh: '人工录入', en: 'Manual Entry' }, value: 'manual' },
        { label: { zh: '图片识别辅助记录', en: 'Image-assisted Record' }, value: 'image2-assisted' },
        { label: { zh: '供应商资料记录', en: 'Supplier Document Record' }, value: 'supplier-document' },
      ],
    },
    { name: 'reviewer', type: 'text', label: { zh: '记录人', en: 'Record Owner' } },
    { name: 'reviewedAt', type: 'date', label: { zh: '记录时间', en: 'Recorded At' } },
  ],
  label: { zh: '图纸与参数图', en: 'Drawings & Parameter Images' },
};

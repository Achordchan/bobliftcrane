import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { publishedOrAdmin } from '../access/publishedOrAdmin';
import { seoFields } from '../fields/seo';
import { slugField } from '../fields/slug';
import { statusField } from '../fields/status';

export const Products: CollectionConfig = {
  slug: 'products',
  labels: {
    singular: { zh: '产品', en: 'Product' },
    plural: { zh: '产品', en: 'Products' },
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: publishedOrAdmin,
    update: isAdmin,
  },
  admin: {
    defaultColumns: ['title', 'model', 'category', 'status', 'updatedAt'],
    group: { zh: '产品中心', en: 'Catalog' },
    useAsTitle: 'title',
  },
  defaultSort: 'category',
  fields: [
    { name: 'title', type: 'text', label: { zh: '产品名称', en: 'Product Name' }, required: true },
    slugField('title'),
    {
      name: 'legacyPath',
      type: 'text',
      admin: {
        description: {
          zh: '旧站兼容 URL，例如：/10-ton-telescopic-boom-truck-mounted-crane.html',
          en: 'Old-site compatible URL path, for example: /10-ton-telescopic-boom-truck-mounted-crane.html',
        },
        position: 'sidebar',
      },
      label: { zh: '旧站 .html 路径', en: 'Legacy .html Path' },
    },
    { name: 'model', type: 'text', label: { zh: '型号', en: 'Model' } },
    {
      name: 'category',
      type: 'select',
      label: { zh: '产品分类', en: 'Product Category' },
      options: [
        {
          label: { zh: '伸缩臂随车起重机', en: 'Telescopic Boom Truck Mounted Cranes' },
          value: 'telescopic-boom-truck-mounted-cranes',
        },
        {
          label: { zh: '折臂随车起重机', en: 'Knuckle Boom Truck Mounted Cranes' },
          value: 'knuckle-boom-truck-mounted-cranes',
        },
        {
          label: { zh: '折臂船用起重机', en: 'Knuckle Boom Marine Cranes' },
          value: 'knuckle-boom-marine-cranes',
        },
        {
          label: { zh: '伸缩臂船用起重机', en: 'Telescopic Boom Marine Cranes' },
          value: 'telescopic-boom-marine-cranes',
        },
        { label: { zh: '皮卡起重机', en: 'Pickup Cranes' }, value: 'pickup-cranes' },
        { label: { zh: '叉车飞臂吊', en: 'Forklift Fly Jib Cranes' }, value: 'forklift-fly-jib-cranes' },
        { label: { zh: '迷你自卸车', en: 'Mini Dumpers' }, value: 'mini-dumpers' },
        { label: { zh: '其他起重机', en: 'Other Cranes' }, value: 'other-cranes' },
      ],
      required: true,
    },
    { name: 'capacity', type: 'text', label: { zh: '起重能力', en: 'Capacity' } },
    { name: 'productType', type: 'text', label: { zh: '产品类型', en: 'Type' } },
    { name: 'intro', type: 'textarea', label: { zh: '简短介绍', en: 'Short Introduction' }, maxLength: 320 },
    {
      name: 'coverImage',
      type: 'upload',
      label: { zh: '封面图', en: 'Cover Image' },
      relationTo: 'media',
    },
    {
      name: 'gallery',
      type: 'array',
      fields: [
        {
          name: 'image',
          type: 'upload',
          label: { zh: '图片', en: 'Image' },
          relationTo: 'media',
          required: true,
        },
      ],
      label: { zh: '产品图库', en: 'Gallery' },
    },
    {
      name: 'specs',
      type: 'array',
      fields: [
        { name: 'label', type: 'text', label: { zh: '参数名', en: 'Label' }, required: true },
        { name: 'value', type: 'text', label: { zh: '参数值', en: 'Value' }, required: true },
      ],
      label: { zh: '规格参数', en: 'Specifications' },
    },
    seoFields,
    statusField,
    {
      name: 'publishedAt',
      type: 'date',
      admin: { position: 'sidebar' },
      label: { zh: '发布时间', en: 'Published At' },
    },
  ],
  timestamps: true,
};

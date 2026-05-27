import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';
import { publishedOrAdmin } from '../access/publishedOrAdmin';
import { seoFields } from '../fields/seo';
import { slugField } from '../fields/slug';
import { statusField } from '../fields/status';

export const Cases: CollectionConfig = {
  slug: 'cases',
  labels: {
    singular: { zh: '客户案例', en: 'Case' },
    plural: { zh: '客户案例', en: 'Cases' },
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: publishedOrAdmin,
    update: isAdmin,
  },
  admin: {
    defaultColumns: ['title', 'country', 'status', 'updatedAt'],
    group: { zh: '内容管理', en: 'Content' },
    useAsTitle: 'title',
  },
  fields: [
    { name: 'title', type: 'text', required: true },
    slugField('title'),
    { name: 'legacyPath', type: 'text', admin: { position: 'sidebar' }, label: 'Legacy .html Path' },
    { name: 'country', type: 'text', label: 'Country / Region' },
    { name: 'industry', type: 'text', label: 'Industry' },
    { name: 'summary', type: 'textarea', maxLength: 320 },
    { name: 'coverImage', type: 'upload', relationTo: 'media' },
    { name: 'content', type: 'richText' },
    seoFields,
    statusField,
    { name: 'publishedAt', type: 'date', admin: { position: 'sidebar' } },
  ],
  timestamps: true,
};

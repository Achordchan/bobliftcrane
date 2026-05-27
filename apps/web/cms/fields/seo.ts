import type { Field } from 'payload';

export const seoFields: Field = {
  name: 'seo',
  type: 'group',
  admin: {
    description: 'Search result metadata for Google and legacy URL migration.',
  },
  fields: [
    {
      name: 'metaTitle',
      type: 'text',
      label: 'Meta Title',
      maxLength: 70,
    },
    {
      name: 'metaDescription',
      type: 'textarea',
      label: 'Meta Description',
      maxLength: 180,
    },
    {
      name: 'canonicalPath',
      type: 'text',
      admin: {
        description: 'Optional canonical path, for example: /10-ton-telescopic-boom-truck-mounted-crane.html',
      },
      label: 'Canonical Path',
    },
  ],
};

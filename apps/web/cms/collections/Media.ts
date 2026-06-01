import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';

export const Media: CollectionConfig = {
  slug: 'media',
  labels: {
    singular: '图片素材',
    plural: '图片素材',
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: () => true,
    update: isAdmin,
  },
  admin: {
    hideAPIURL: true,
    defaultColumns: ['filename', 'alt', 'updatedAt'],
    group: { zh: '05 图片素材', en: '05 Image Assets' },
    useAsTitle: 'filename',
  },
  fields: [
    {
      name: 'alt',
      type: 'text',
      localized: true,
      label: { zh: '图片说明', en: 'Alt Text' },
      required: true,
    },
    {
      name: 'caption',
      type: 'text',
      localized: true,
      label: { zh: '图片标题/说明', en: 'Caption' },
    },
  ],
  upload: {
    adminThumbnail: 'thumbnail',
    focalPoint: true,
    imageSizes: [
      {
        name: 'thumbnail',
        width: 480,
        height: 320,
        position: 'centre',
      },
      {
        name: 'card',
        width: 900,
        height: 700,
        position: 'centre',
      },
      {
        name: 'hero',
        width: 1920,
        height: 1080,
        position: 'centre',
      },
    ],
    mimeTypes: ['image/avif', 'image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'application/pdf'],
  },
};

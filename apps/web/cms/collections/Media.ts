import type { CollectionConfig } from 'payload';

import { isAdmin } from '../access/isAdmin';

export const Media: CollectionConfig = {
  slug: 'media',
  labels: {
    singular: { zh: '媒体素材', en: 'Media' },
    plural: { zh: '媒体素材', en: 'Media' },
  },
  access: {
    create: isAdmin,
    delete: isAdmin,
    read: () => true,
    update: isAdmin,
  },
  admin: {
    defaultColumns: ['filename', 'alt', 'updatedAt'],
    group: { zh: '资源管理', en: 'Assets' },
    useAsTitle: 'filename',
  },
  fields: [
    {
      name: 'alt',
      type: 'text',
      label: 'Alt Text',
      required: true,
    },
    {
      name: 'caption',
      type: 'text',
      label: 'Caption',
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
    mimeTypes: ['image/avif', 'image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
  },
};

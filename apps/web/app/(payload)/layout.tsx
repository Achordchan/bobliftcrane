import '@payloadcms/next/css';

import configPromise from '@payload-config';
import { handleServerFunctions, RootLayout } from '@payloadcms/next/layouts';
import type { ServerFunctionClient } from 'payload';
import type { ReactNode } from 'react';

import { importMap } from './admin/importMap.js';

export const metadata = {
  title: 'BOB-LIFT Content Admin',
  description: 'BOB-LIFT content management system',
};

const serverFunction: ServerFunctionClient = async (args) => {
  'use server';

  return handleServerFunctions({
    ...args,
    config: configPromise,
    importMap,
  });
};

export default function PayloadLayout({ children }: { children: ReactNode }) {
  return (
    <RootLayout config={configPromise} importMap={importMap} serverFunction={serverFunction}>
      {children}
    </RootLayout>
  );
}

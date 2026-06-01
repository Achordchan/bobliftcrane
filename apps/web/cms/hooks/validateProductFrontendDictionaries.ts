import type { CollectionBeforeValidateHook } from 'payload';

import {
  defaultApplicationOptions,
  defaultAvailabilityOptions,
  defaultBoomTypeOptions,
} from '../../lib/product-ui';

type ProductDraftData = Record<string, unknown>;

type DictionaryItem = {
  key?: string | null;
  value?: string | null;
};

export const validateProductFrontendDictionaries: CollectionBeforeValidateHook = async ({ data, originalDoc, req }) => {
  if (!data) return data;

  const draft = { ...(originalDoc as ProductDraftData | undefined), ...data };
  const dictionaries = await productDictionaries(req.payload);
  const errors: string[] = [];

  const boomType = textValue(draft.boomType);
  if (boomType && !dictionaries.boomTypes.has(boomType)) {
    errors.push(`臂架类型“${boomType}”不在“产品列表页设置 → 臂架类型筛选项”中`);
  }

  const availability = textValue(draft.availability);
  if (availability && !dictionaries.availability.has(availability)) {
    errors.push(`列表状态“${availability}”不在“产品列表页设置 → 可售状态筛选项”中`);
  }

  const unknownApplications = arrayValues(draft.applications).filter((item) => !dictionaries.applications.has(item));
  if (unknownApplications.length > 0) {
    errors.push(`应用场景“${unknownApplications.join('、')}”不在“产品列表页设置 → 应用场景选项”中`);
  }

  if (errors.length > 0) {
    throw new Error(`产品选项不匹配：${errors.join('；')}。请先在对应后台选项里维护取值，再保存产品。`);
  }

  return data;
};

async function productDictionaries(payload: Parameters<CollectionBeforeValidateHook>[0]['req']['payload']) {
  try {
    const settings = await payload.findGlobal({
      slug: 'product-listing-ui' as never,
      depth: 0,
      fallbackLocale: 'en',
      locale: 'en',
    }) as { productListingUi?: Record<string, unknown> } | null;
    const listingUi = settings?.productListingUi ?? {};

    return {
      boomTypes: dictionaryKeys(listingUi.boomTypes, defaultBoomTypeOptions),
      availability: dictionaryKeys(listingUi.availability, defaultAvailabilityOptions),
      applications: dictionaryKeys(listingUi.applications, defaultApplicationOptions),
    };
  } catch {
    return {
      boomTypes: new Set(defaultBoomTypeOptions.map((item) => item.value)),
      availability: new Set(defaultAvailabilityOptions.map((item) => item.value)),
      applications: new Set(defaultApplicationOptions.map((item) => item.value)),
    };
  }
}

function dictionaryKeys(value: unknown, defaults: { value: string }[]) {
  const keys = Array.isArray(value)
    ? value.flatMap((item: DictionaryItem | null) => {
        const key = textValue(item?.key ?? item?.value);
        return key ? [key] : [];
      })
    : [];

  return new Set(keys.length > 0 ? keys : defaults.map((item) => item.value));
}

function textValue(value: unknown) {
  return typeof value === 'string' ? value.trim() : '';
}

function arrayValues(value: unknown) {
  return Array.isArray(value) ? value.flatMap((item) => (textValue(item) ? [textValue(item)] : [])) : [];
}

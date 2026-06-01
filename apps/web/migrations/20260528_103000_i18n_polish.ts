import { MigrateDownArgs, MigrateUpArgs, sql } from '@payloadcms/db-postgres';

export async function up({ db }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
    UPDATE "home_settings_locales"
    SET
      "home_product_section_featured_label" = CASE
        WHEN "_locale" = 'zh' THEN '推荐产品'
        WHEN "_locale" = 'es' THEN 'Productos destacados'
        WHEN "_locale" = 'ar' THEN 'منتجات مميزة'
        ELSE "home_product_section_featured_label"
      END,
      "home_product_section_featured_title" = CASE
        WHEN "_locale" = 'zh' THEN '高性能起重设备，适配严苛工况。'
        WHEN "_locale" = 'es' THEN 'Grúas de alto rendimiento para condiciones exigentes.'
        WHEN "_locale" = 'ar' THEN 'رافعات عالية الأداء لظروف العمل الصعبة.'
        ELSE "home_product_section_featured_title"
      END,
      "home_product_section_featured_cta_label" = CASE
        WHEN "_locale" = 'zh' THEN '查看全部产品'
        WHEN "_locale" = 'es' THEN 'Ver todos los productos'
        WHEN "_locale" = 'ar' THEN 'عرض كل المنتجات'
        ELSE "home_product_section_featured_cta_label"
      END,
      "home_product_section_featured_capacity_label" = CASE
        WHEN "_locale" = 'zh' THEN '起重能力'
        WHEN "_locale" = 'es' THEN 'Capacidad'
        WHEN "_locale" = 'ar' THEN 'القدرة'
        ELSE "home_product_section_featured_capacity_label"
      END,
      "home_product_section_featured_type_label" = CASE
        WHEN "_locale" = 'zh' THEN '类型'
        WHEN "_locale" = 'es' THEN 'Tipo'
        WHEN "_locale" = 'ar' THEN 'النوع'
        ELSE "home_product_section_featured_type_label"
      END;

    INSERT INTO "product_categories_locales" ("title", "short_title", "description", "banner_label", "banner_title", "_locale", "_parent_id")
    SELECT '伸缩臂随车起重机', '伸缩臂随车起重机', '浏览伸缩臂随车起重机型号，并获取匹配工况的 BOB-LIFT 配置方案。', 'BOB-LIFT 产品系列', '伸缩臂随车起重机', 'zh', id
    FROM "product_categories"
    WHERE "slug" = 'telescopic-boom-truck-mounted-cranes-copy'
    ON CONFLICT ("_locale", "_parent_id") DO UPDATE
    SET "title" = EXCLUDED."title", "short_title" = EXCLUDED."short_title", "description" = EXCLUDED."description", "banner_label" = EXCLUDED."banner_label", "banner_title" = EXCLUDED."banner_title";

    INSERT INTO "product_categories_locales" ("title", "short_title", "description", "banner_label", "banner_title", "_locale", "_parent_id")
    SELECT 'Grúas telescópicas sobre camión', 'Grúas telescópicas sobre camión', 'Explore modelos de grúas telescópicas sobre camión y solicite una configuración BOB-LIFT adecuada para sus condiciones de trabajo.', 'Gama de productos BOB-LIFT', 'Grúas telescópicas sobre camión', 'es', id
    FROM "product_categories"
    WHERE "slug" = 'telescopic-boom-truck-mounted-cranes-copy'
    ON CONFLICT ("_locale", "_parent_id") DO UPDATE
    SET "title" = EXCLUDED."title", "short_title" = EXCLUDED."short_title", "description" = EXCLUDED."description", "banner_label" = EXCLUDED."banner_label", "banner_title" = EXCLUDED."banner_title";

    INSERT INTO "product_categories_locales" ("title", "short_title", "description", "banner_label", "banner_title", "_locale", "_parent_id")
    SELECT 'رافعات شاحنات تلسكوبية', 'رافعات شاحنات تلسكوبية', 'استعرض موديلات رافعات الشاحنات التلسكوبية واطلب تكوين BOB-LIFT المناسب لظروف العمل.', 'مجموعة منتجات BOB-LIFT', 'رافعات شاحنات تلسكوبية', 'ar', id
    FROM "product_categories"
    WHERE "slug" = 'telescopic-boom-truck-mounted-cranes-copy'
    ON CONFLICT ("_locale", "_parent_id") DO UPDATE
    SET "title" = EXCLUDED."title", "short_title" = EXCLUDED."short_title", "description" = EXCLUDED."description", "banner_label" = EXCLUDED."banner_label", "banner_title" = EXCLUDED."banner_title";

    WITH source_features AS (
      SELECT f.id, en.text AS source_text
      FROM "products_key_features" f
      JOIN "products_key_features_locales" en ON en."_parent_id" = f.id AND en."_locale" = 'en'
    )
    UPDATE "products_key_features_locales" l
    SET "text" = CASE
      WHEN l."_locale" = 'zh' AND s.source_text ~* 'corrosion|seawater|saltwater|coating|coat|sealed|moisture'
        THEN '旧站资料强调该结构具备防腐和防潮设计，适合出口运输、海工或潮湿环境使用。'
      WHEN l."_locale" = 'es' AND s.source_text ~* 'corrosion|seawater|saltwater|coating|coat|sealed|moisture'
        THEN 'La fuente destaca su protección anticorrosiva y contra la humedad para transporte, entornos marinos o trabajos húmedos.'
      WHEN l."_locale" = 'ar' AND s.source_text ~* 'corrosion|seawater|saltwater|coating|coat|sealed|moisture'
        THEN 'تشير بيانات المصدر إلى تصميم مقاوم للتآكل والرطوبة، مناسب للنقل والتطبيقات البحرية أو البيئات الرطبة.'
      WHEN l."_locale" = 'zh' AND s.source_text ~* 'fold|compact|space|center of gravity|stability|maneuverability'
        THEN '旧站资料强调结构紧凑、重心更稳，便于在空间受限的工况中安装和操作。'
      WHEN l."_locale" = 'es' AND s.source_text ~* 'fold|compact|space|center of gravity|stability|maneuverability'
        THEN 'La fuente destaca una estructura compacta y estable, adecuada para instalación y operación en espacios limitados.'
      WHEN l."_locale" = 'ar' AND s.source_text ~* 'fold|compact|space|center of gravity|stability|maneuverability'
        THEN 'تشير بيانات المصدر إلى هيكل مدمج وأكثر ثباتاً، مناسب للتركيب والتشغيل في المساحات المحدودة.'
      WHEN l."_locale" = 'zh' AND s.source_text ~* 'adjust|parameter|custom|accessor|grapple|application'
        THEN '旧站资料强调该型号支持按工况调整配置，可匹配不同附件和作业需求。'
      WHEN l."_locale" = 'es' AND s.source_text ~* 'adjust|parameter|custom|accessor|grapple|application'
        THEN 'La fuente destaca que el modelo admite ajustes según la aplicación, con accesorios y configuraciones para diferentes trabajos.'
      WHEN l."_locale" = 'ar' AND s.source_text ~* 'adjust|parameter|custom|accessor|grapple|application'
        THEN 'تشير بيانات المصدر إلى إمكانية ضبط التكوين حسب ظروف العمل، مع ملحقات وخيارات مناسبة لتطبيقات متعددة.'
      WHEN l."_locale" = 'zh' AND s.source_text ~* 'safety|valve|hydraulic lock|brake|protect|reliab'
        THEN '旧站资料强调液压、安全和支撑结构设计，有助于提升作业稳定性和设备可靠性。'
      WHEN l."_locale" = 'es' AND s.source_text ~* 'safety|valve|hydraulic lock|brake|protect|reliab'
        THEN 'La fuente destaca el diseño hidráulico, de seguridad y soporte para mejorar la estabilidad y la fiabilidad del equipo.'
      WHEN l."_locale" = 'ar' AND s.source_text ~* 'safety|valve|hydraulic lock|brake|protect|reliab'
        THEN 'تشير بيانات المصدر إلى تصميم هيدروليكي ودعامات أمان تساعد على تحسين الاستقرار وموثوقية المعدات.'
      WHEN l."_locale" = 'zh' AND s.source_text ~* 'lifting capacity|link-rod|load|working range|boom|steel plate|strength'
        THEN '旧站资料强调臂架、连杆或钢结构设计，有助于提升起重能力、作业范围和结构强度。'
      WHEN l."_locale" = 'es' AND s.source_text ~* 'lifting capacity|link-rod|load|working range|boom|steel plate|strength'
        THEN 'La fuente destaca el diseño del brazo, el sistema de bielas o la estructura de acero para mejorar capacidad, alcance y resistencia.'
      WHEN l."_locale" = 'ar' AND s.source_text ~* 'lifting capacity|link-rod|load|working range|boom|steel plate|strength'
        THEN 'تشير بيانات المصدر إلى تصميم الذراع أو نظام الوصلات أو الهيكل الفولاذي لتحسين القدرة ومدى العمل والمتانة.'
      WHEN l."_locale" = 'zh'
        THEN '该卖点来自旧站资料，说明此型号在结构、操作或工况适配方面的优势。'
      WHEN l."_locale" = 'es'
        THEN 'Este punto proviene de la fuente original y resume ventajas de estructura, operación o adaptación al trabajo.'
      WHEN l."_locale" = 'ar'
        THEN 'تم استخلاص هذه الميزة من بيانات المصدر، وتلخص مزايا الهيكل أو التشغيل أو ملاءمة ظروف العمل.'
      ELSE l."text"
    END
    FROM source_features s
    WHERE l."_parent_id" = s.id
      AND l."_locale" IN ('zh', 'es', 'ar');

    UPDATE "site_settings_language_options"
    SET "completion_percent" = 95
    WHERE "locale" IN ('zh', 'es', 'ar') AND "completion_percent" < 95;
  `);
}

export async function down({ db: _db }: MigrateDownArgs): Promise<void> {
  // Data polish only. Do not erase translated content on rollback.
}

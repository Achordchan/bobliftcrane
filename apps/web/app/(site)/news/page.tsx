import Image from "next/image";
import { ArrowRight, CalendarDays } from "lucide-react";

import { PageHero } from "@/components/site/PageHero";
import { SiteChrome } from "@/components/site/SiteChrome";
import { articlePublicPath, getNewsIndexContent } from "@/lib/cms-pages";
import { getPageUiContent } from "@/lib/cms-content";
import { localizedPath } from "@/lib/i18n";
import { localizedSeoMetadata } from "@/lib/seo-metadata";
import { getRequestLocale } from "@/lib/server-locale";

export const dynamic = "force-dynamic";

export async function generateMetadata() {
  const ui = (await getPageUiContent()).newsPage;
  return localizedSeoMetadata({ title: ui.metaTitle, description: ui.metaDescription, canonicalPath: ui.canonicalPath });
}

export default async function NewsPage() {
  const [{ articles, categories }, pageUi, locale] = await Promise.all([getNewsIndexContent(), getPageUiContent(), getRequestLocale()]);
  const ui = pageUi.newsPage;
  const [featured, ...rest] = articles;

  return (
    <SiteChrome>
      <PageHero label={ui.heroLabel} title={ui.heroTitle} description={ui.heroDescription} image={ui.heroImage}>
        <a className="btn-primary rounded-full" href={localizedPath(articlePublicPath(featured), locale)}>{ui.readFeaturedLabel} <ArrowRight className="h-4 w-4" /></a>
        <a className="btn-white rounded-full" href={localizedPath("/quote", locale)}>{ui.requestQuoteLabel}</a>
      </PageHero>

      <section className="page-shell py-10 md:py-16">
        <div className="mb-8 flex flex-wrap gap-2">
          {categories.map((item) => (
            <span className="rounded-full border border-line bg-white px-4 py-2 text-xs font-black text-slate-500 shadow-[0_10px_28px_rgba(8,35,74,0.045)]" key={item}>{item}</span>
          ))}
        </div>

        <article className="grid grid-cols-1 gap-5 overflow-hidden rounded-[32px] border border-line bg-[linear-gradient(135deg,#f8fbff_0%,#ffffff_100%)] p-3 shadow-[0_34px_100px_rgba(8,35,74,0.08)] md:grid-cols-[1.08fr_0.92fr] md:p-5 lg:p-7">
          <a className="relative min-h-[260px] overflow-hidden rounded-[26px] md:min-h-[420px]" href={localizedPath(articlePublicPath(featured), locale)}>
            <Image className="object-cover transition duration-500 hover:scale-[1.03]" src={featured.image} alt={featured.title} fill priority sizes="(min-width: 768px) 52vw, 100vw" />
          </a>
          <div className="flex flex-col justify-center p-3 md:p-5">
            <p className="section-label mb-4">{ui.featuredLabel}</p>
            <div className="mb-4 flex flex-wrap items-center gap-3 text-xs font-black text-slate-500">
              <span className="rounded-full bg-gold/12 px-3 py-1.5 text-gold">{featured.category}</span>
              <span className="inline-flex items-center gap-1"><CalendarDays className="h-4 w-4" />{featured.date}</span>
            </div>
            <h2 className="text-[32px] font-black leading-tight tracking-[-0.045em] text-ink md:text-[46px]">{featured.title}</h2>
            <p className="mt-5 text-base font-semibold leading-8 text-slate-600">{featured.excerpt}</p>
            <a className="mt-7 inline-flex w-fit items-center gap-2 rounded-full bg-navy px-6 py-3 text-sm font-black text-white shadow-card transition hover:-translate-y-0.5" href={localizedPath(articlePublicPath(featured), locale)}>{ui.readArticleLabel} <ArrowRight className="h-4 w-4" /></a>
          </div>
        </article>

        <div className="mt-8 grid grid-cols-1 gap-5 md:grid-cols-3">
          {rest.map((article) => (
            <a className="card-hover overflow-hidden rounded-[26px] border border-line bg-white p-3 shadow-card" href={localizedPath(articlePublicPath(article), locale)} key={article.slug}>
              <div className="relative h-56 overflow-hidden rounded-[20px]"><Image className="object-cover" src={article.image} alt={article.title} fill sizes="(min-width: 768px) 30vw, 100vw" /></div>
              <div className="p-3">
                <div className="mb-3 flex flex-wrap items-center gap-2 text-[11px] font-black text-slate-500"><span className="text-gold">{article.category}</span><span>{article.date}</span></div>
                <h3 className="line-clamp-2 text-xl font-black leading-tight tracking-[-0.03em] text-ink">{article.title}</h3>
                <p className="mt-3 line-clamp-3 text-sm font-semibold leading-6 text-slate-500">{article.excerpt}</p>
                <span className="mt-4 inline-flex items-center gap-2 text-sm font-black text-blue-600">{ui.readMoreLabel} <ArrowRight className="h-4 w-4" /></span>
              </div>
            </a>
          ))}
        </div>
      </section>
    </SiteChrome>
  );
}

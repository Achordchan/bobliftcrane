import Image from "next/image";
import { ArrowRight, CalendarDays, ChevronLeft } from "lucide-react";

import { articlePublicPath, type NewsArticle } from "@/lib/cms-pages";
import { localizedPath } from "@/lib/i18n";
import type { PageUiContent } from "@/lib/site-ui";

export function ArticleDetailContent({
  article,
  related,
  ui,
  locale,
}: {
  article: NewsArticle;
  related: NewsArticle[];
  ui: PageUiContent["newsDetail"];
  locale: string;
}) {
  return (
    <article>
      <header className="relative overflow-hidden bg-[linear-gradient(135deg,#071f42_0%,#0b3a78_58%,#f8fbff_58%,#ffffff_100%)] py-8 md:py-14">
        <div className="page-shell grid grid-cols-1 gap-7 md:grid-cols-[0.92fr_1.08fr] md:items-center">
          <div className="text-white">
            <a className="mb-6 inline-flex items-center gap-2 text-sm font-black text-white/80 hover:text-white" href={localizedPath("/news", locale)}>
              <ChevronLeft className="h-4 w-4" /> {ui.backLabel}
            </a>
            <div className="mb-4 flex flex-wrap items-center gap-3 text-xs font-black uppercase tracking-[0.12em] text-white/70">
              <span className="text-gold">{article.category}</span>
              <span className="inline-flex items-center gap-1 normal-case tracking-normal">
                <CalendarDays className="h-4 w-4" />
                {article.date}
              </span>
            </div>
            <h1 className="text-[38px] font-black leading-[1.02] tracking-[-0.055em] md:text-[62px]">{article.title}</h1>
            <p className="mt-5 max-w-[620px] text-base font-semibold leading-8 text-white/74">{article.excerpt}</p>
          </div>
          <div className="relative min-h-[260px] overflow-hidden rounded-[30px] border border-white/30 shadow-[0_34px_100px_rgba(8,35,74,0.22)] md:min-h-[440px]">
            <Image className="object-cover" src={article.image} alt={article.title} fill priority sizes="(min-width: 768px) 50vw, 100vw" />
          </div>
        </div>
      </header>

      <div className="page-shell grid grid-cols-1 gap-8 py-10 md:py-16 lg:grid-cols-[minmax(0,760px)_320px] lg:justify-between">
        <ArticleBody article={article} />

        <aside className="h-fit rounded-[28px] border border-line bg-[linear-gradient(180deg,#f8fbff_0%,#ffffff_100%)] p-5 shadow-card lg:sticky lg:top-8">
          <p className="section-label mb-3">{ui.relatedLabel}</p>
          <div className="grid gap-4">
            {related.map((item) => (
              <a className="group grid grid-cols-[86px_1fr] gap-3 rounded-[18px] bg-white p-2 shadow-[0_10px_28px_rgba(8,35,74,0.05)]" href={localizedPath(articlePublicPath(item), locale)} key={item.slug}>
                <span className="relative h-20 overflow-hidden rounded-[14px]">
                  <Image className="object-cover" src={item.image} alt={item.title} fill sizes="86px" />
                </span>
                <span className="min-w-0 py-1">
                  <span className="text-[10px] font-black uppercase tracking-[0.12em] text-gold">{item.category}</span>
                  <span className="mt-1 line-clamp-2 block text-sm font-black leading-5 text-ink group-hover:text-blue-700">{item.title}</span>
                </span>
              </a>
            ))}
          </div>
          <a className="mt-5 inline-flex items-center gap-2 text-sm font-black text-blue-600" href={localizedPath("/quote", locale)}>
            {ui.quoteCtaLabel} <ArrowRight className="h-4 w-4" />
          </a>
        </aside>
      </div>
    </article>
  );
}

function ArticleBody({ article }: { article: NewsArticle }) {
  if (article.contentHtml) {
    return <div className="cms-richtext rounded-[30px] border border-line bg-white p-5 shadow-card md:p-9" dangerouslySetInnerHTML={{ __html: article.contentHtml }} />;
  }

  return (
    <div className="rounded-[30px] border border-line bg-white p-5 shadow-card md:p-9">
      {article.sections.map((section) => (
        <section className="border-b border-line py-7 first:pt-0 last:border-b-0 last:pb-0" key={section.heading}>
          <h2 className="text-2xl font-black tracking-[-0.035em] text-ink md:text-3xl">{section.heading}</h2>
          <p className="mt-4 text-base font-semibold leading-8 text-slate-600">{section.body}</p>
        </section>
      ))}
    </div>
  );
}

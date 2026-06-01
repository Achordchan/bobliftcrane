import Image from "next/image";
import { ArrowRight } from "lucide-react";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { articlePublicPath } from "@/lib/cms-pages";
import { defaultNewsSectionContent, type NewsSectionContent } from "@/lib/home-defaults";
import { localizedPath } from "@/lib/i18n";
import { articles as fallbackArticles, type Article } from "@/lib/site-content";

function NewsCenter({ articles, content, locale }: { articles: Article[]; content: NewsSectionContent; locale?: string }) {
  const sourceArticles = articles.length > 0 ? articles : fallbackArticles;
  const [featured, ...secondary] = sourceArticles.slice(0, Math.max(1, content.maxArticles));
  const rightItems = secondary.slice(0, 2);

  if (!featured) return null;

  return (
    <section id="news" className="relative overflow-hidden bg-[linear-gradient(135deg,#f8fbff_0%,#eef5ff_52%,#ffffff_100%)] py-10 md:py-16">
      <div className="page-shell relative">
        <span className="pointer-events-none absolute right-0 top-3 select-none font-serif text-[68px] font-black italic leading-none tracking-[-0.14em] text-ink/[0.035] md:top-0 md:text-[150px]">
          {content.backgroundWord}
        </span>
        <div className="relative z-10 grid grid-cols-1 gap-4 lg:flex lg:items-end lg:justify-between lg:gap-8">
          <div className="min-w-0">
            <p className="section-label mb-4 md:mb-5">
              {content.label}
            </p>
            <h2 className="max-w-none text-[clamp(31px,7.2vw,38px)] font-black leading-[0.98] tracking-[-0.06em] text-ink sm:text-[48px] lg:text-[52px] 2xl:whitespace-nowrap 2xl:text-[64px]">
              {content.title}
            </h2>
          </div>
          {content.showCta ? (
          <div className="flex lg:justify-self-end">
            <Button nativeButton={false} render={<a href={content.ctaHref} />} className="h-auto w-fit rounded-full bg-navy px-5 py-2.5 text-xs font-black text-white shadow-card hover:-translate-y-0.5 hover:bg-[#0b2d5b] md:px-6 md:py-3 md:text-sm">
              {content.ctaLabel} <ArrowRight data-icon="inline-end" />
            </Button>
          </div>
          ) : null}
        </div>

        <div className="relative z-10 mt-7 grid grid-cols-1 items-stretch gap-5 md:mt-9 2xl:grid-cols-[minmax(0,1.08fr)_minmax(430px,0.92fr)] 2xl:gap-7">
          <div className="grid h-full content-stretch">
            <a className="group grid h-full overflow-hidden rounded-[26px] border border-line bg-white p-2 shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-soft md:rounded-[30px] md:p-3 lg:grid-cols-[minmax(0,0.96fr)_minmax(300px,0.74fr)] 2xl:grid-cols-[1fr_0.88fr]" href={localizedPath(articlePublicPath(featured), locale)}>
              <div className="relative min-h-[220px] overflow-hidden rounded-[20px] sm:min-h-[260px] lg:min-h-[300px] md:rounded-[24px] 2xl:min-h-[330px]">
                <Image className="object-cover transition duration-500 group-hover:scale-[1.035]" src={featured.image} alt={featured.title} fill sizes="(min-width: 1536px) 40vw, (min-width: 1024px) 46vw, 92vw" />
              </div>
              <div className="flex min-w-0 flex-col justify-center bg-white p-4 sm:p-5 lg:p-6 2xl:p-8">
                {content.showCategory || content.showDate ? (
                  <div className="mb-3 flex flex-wrap items-center gap-2 md:mb-5 md:gap-3">
                    {content.showCategory ? (
                      <Badge variant="outline" className="h-7 rounded-full border-line bg-[#f7fbff] px-3 text-[10px] font-black uppercase tracking-[0.16em] text-blue-700">
                        {featured.category}
                      </Badge>
                    ) : null}
                    {content.showDate ? <span className="text-xs font-bold text-slate-500">{featured.date}</span> : null}
                  </div>
                ) : null}
                <h3 className="line-clamp-3 text-2xl font-black leading-tight tracking-[-0.04em] text-ink sm:text-3xl lg:text-[32px] 2xl:line-clamp-4 2xl:text-4xl">
                  {featured.title}
                </h3>
                {content.showExcerpt ? (
                  <p className="mt-4 line-clamp-3 text-sm font-medium leading-6 text-slate-600 md:text-base md:leading-7 2xl:line-clamp-4">
                    {featured.excerpt}
                  </p>
                ) : null}
                <span className="mt-6 inline-flex w-fit items-center gap-2 text-sm font-black text-ink">
                  {content.readMoreLabel} <ArrowRight className="h-4 w-4 transition duration-300 group-hover:translate-x-1" />
                </span>
              </div>
            </a>
          </div>

          <div className="grid h-full content-stretch gap-4 sm:grid-cols-2 2xl:grid-cols-1 2xl:grid-rows-2">
            {rightItems.map((item) => (
              <a className="group grid h-full min-w-0 grid-cols-[112px_1fr] gap-3 overflow-hidden rounded-[22px] border border-line bg-white/90 p-2 shadow-card transition duration-300 hover:-translate-y-1 hover:bg-white hover:shadow-soft sm:grid-cols-1 2xl:grid-cols-[178px_1fr] 2xl:rounded-[26px]" href={localizedPath(articlePublicPath(item), locale)} key={item.slug}>
                <div className="relative min-h-[122px] overflow-hidden rounded-[18px] sm:min-h-[190px] 2xl:min-h-[164px] 2xl:rounded-[20px]">
                  <Image className="object-cover transition duration-500 group-hover:scale-[1.04]" src={item.image} alt={item.title} fill sizes="(min-width: 1536px) 16vw, (min-width: 640px) 42vw, 112px" />
                </div>
                <div className="flex min-w-0 flex-col justify-center px-1 py-1.5 sm:px-2 sm:py-3 2xl:px-2 2xl:py-4">
                  {content.showCategory || content.showDate ? (
                    <div className="mb-2 flex flex-wrap items-center gap-1.5 md:gap-2">
                      {content.showCategory ? (
                        <Badge variant="outline" className="h-5 rounded-full border-line bg-white px-2 text-[8px] font-black uppercase tracking-[0.08em] text-gold md:h-6 md:px-2.5 md:text-[10px] md:tracking-[0.12em]">
                          {item.category}
                        </Badge>
                      ) : null}
                      {content.showDate ? <span className="text-[10px] font-bold text-slate-500 md:text-xs">{item.date}</span> : null}
                    </div>
                  ) : null}
                  <h3 className="line-clamp-2 text-sm font-black leading-snug text-ink md:text-lg">
                    {item.title}
                  </h3>
                  {content.showExcerpt ? (
                    <p className="mt-2 line-clamp-2 text-xs font-medium leading-5 text-slate-500 md:text-sm md:leading-6">
                      {item.excerpt}
                    </p>
                  ) : null}
                  <span className="mt-3 inline-flex items-center gap-2 text-xs font-black text-ink">
                    {content.readMoreLabel} <ArrowRight className="h-3.5 w-3.5 transition duration-300 group-hover:translate-x-1" />
                  </span>
                </div>
              </a>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

export function StoriesNews({
  articles = fallbackArticles,
  content = defaultNewsSectionContent,
  locale,
}: {
  articles?: Article[];
  content?: NewsSectionContent;
  locale?: string;
}) {
  return (
    <NewsCenter articles={articles} content={content} locale={locale} />
  );
}

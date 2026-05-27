import Image from "next/image";
import { ArrowRight } from "lucide-react";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { articles, caseStudies } from "@/lib/site-content";

type StoryCard = {
  title: string;
  type: string;
  image: string;
  slug: string;
};

function ProjectsSection() {
  return (
    <section id="projects" className="page-shell py-8 md:py-10">
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-[260px_1fr] lg:gap-10">
        <div>
          <p className="section-label mb-3 md:mb-4">Success Stories</p>
          <h2 className="max-w-none text-[clamp(27px,7vw,34px)] font-black leading-[1.02] tracking-[-0.055em] text-ink sm:text-3xl sm:leading-tight sm:tracking-[-0.03em]">
            Projects That Define Our Commitment.
          </h2>
          <a className="mt-4 inline-flex items-center gap-2 text-sm font-black text-ink lg:mt-9" href="/cases">
          View All Projects <ArrowRight className="h-4 w-4" />
        </a>
        </div>
        <div className="-mx-4 overflow-x-auto px-4 pb-1 [-ms-overflow-style:none] [scrollbar-width:none] md:mx-0 md:overflow-visible md:px-0 [&::-webkit-scrollbar]:hidden">
          <div className="grid auto-cols-[82%] grid-flow-col gap-4 md:grid-flow-row md:grid-cols-3 md:gap-5 lg:gap-6">
            {caseStudies.map((item) => (
              <StoryTile item={item} key={item.title} />
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

function StoryTile({ item }: { item: StoryCard }) {
  return (
    <a className="card-hover block overflow-hidden rounded-[24px] border border-line bg-white p-2 shadow-card md:rounded-2xl md:p-3" href={`/cases/${item.slug}`}>
      <div className="relative h-40 overflow-hidden rounded-[18px] md:rounded-xl">
        <Image className="object-cover" src={item.image} alt={item.title} fill sizes="(min-width: 768px) 25vw, 82vw" />
      </div>
      <div className="flex items-end justify-between gap-3 p-3 md:p-4">
        <div className="min-w-0">
          <h3 className="line-clamp-2 text-sm font-black leading-5 text-ink">{item.title}</h3>
          <p className="mt-1 line-clamp-1 text-xs font-semibold text-slate-500">{item.type}</p>
        </div>
        <ArrowRight className="h-4 w-4 shrink-0" />
      </div>
    </a>
  );
}

function NewsCenter() {
  const [featured, ...secondary] = articles;

  return (
    <section id="news" className="page-shell py-8 md:py-14">
      <div className="relative overflow-hidden rounded-[28px] border border-line bg-[linear-gradient(135deg,#f8fbff_0%,#eef5ff_56%,#ffffff_100%)] p-4 shadow-[0_34px_100px_rgba(8,35,74,0.08)] md:rounded-[34px] md:p-8 lg:p-10">
        <span className="pointer-events-none absolute right-5 top-8 select-none font-serif text-[68px] font-black italic leading-none tracking-[-0.14em] text-ink/[0.035] md:right-8 md:top-6 md:text-[150px]">
          NEWS
        </span>
        <div className="relative z-10 grid grid-cols-1 gap-4 lg:grid-cols-[0.82fr_1fr] lg:items-end lg:gap-6">
          <div>
            <Badge className="mb-4 h-7 rounded-full bg-gold/12 px-3 text-[10px] font-black uppercase tracking-[0.22em] text-gold md:mb-5">
              News Center
            </Badge>
            <h2 className="max-w-none text-[clamp(31px,7.2vw,38px)] font-black leading-[0.98] tracking-[-0.06em] text-ink sm:max-w-[560px] sm:text-[52px] lg:text-[64px]">
              Latest Insights from BOB-LIFT.
            </h2>
          </div>
          <div className="flex flex-col gap-4 lg:max-w-[560px] lg:justify-self-end lg:gap-5">
            <p className="text-sm font-medium leading-6 text-slate-600 md:text-lg md:leading-7">
              Follow product updates, lifting technology trends and global service news from our engineering and export teams.
            </p>
            <Button nativeButton={false} render={<a href="/news" />} className="h-auto w-fit rounded-full bg-navy px-5 py-2.5 text-xs font-black text-white shadow-card hover:-translate-y-0.5 hover:bg-[#0b2d5b] md:px-6 md:py-3 md:text-sm">
              View All News <ArrowRight data-icon="inline-end" />
            </Button>
          </div>
        </div>

        <div className="relative z-10 mt-6 grid grid-cols-1 gap-4 md:mt-8 lg:grid-cols-[1.15fr_0.85fr] lg:gap-6">
          <Card className="card-hover rounded-[24px] border-line bg-white/92 p-2 shadow-card ring-0 md:rounded-[28px]">
            <CardContent className="grid gap-5 p-0 md:grid-cols-[1fr_0.9fr] md:items-stretch">
              <div className="relative min-h-[210px] overflow-hidden rounded-[20px] md:min-h-[360px] md:rounded-[22px]">
                <Image className="object-cover" src={featured.image} alt={featured.title} fill sizes="48vw" />
              </div>
              <div className="flex flex-col justify-center px-3 pb-4 md:px-3 md:py-6 lg:px-5">
                <div className="mb-3 flex flex-wrap items-center gap-2 md:mb-5 md:gap-3">
                  <Badge variant="outline" className="h-7 rounded-full border-line bg-[#f7fbff] px-3 text-[10px] font-black uppercase tracking-[0.16em] text-blue-700">
                    {featured.category}
                  </Badge>
                  <span className="text-xs font-bold text-slate-500">{featured.date}</span>
                </div>
                <CardTitle className="text-xl font-black leading-tight tracking-[-0.03em] text-ink md:text-3xl">
                  {featured.title}
                </CardTitle>
                <CardDescription className="mt-3 text-sm font-medium leading-6 text-slate-600 md:mt-4 md:text-base md:leading-7">
                  {featured.excerpt}
                </CardDescription>
                <a className="mt-5 inline-flex items-center gap-2 text-sm font-black text-ink md:mt-7" href={`/news/${featured.slug}`}>
                  Read Article <ArrowRight className="h-4 w-4" />
                </a>
              </div>
            </CardContent>
          </Card>

          <div className="grid gap-4 md:gap-5">
            {secondary.map((item) => (
              <Card className="card-hover rounded-[22px] border-line bg-white/88 p-2 shadow-card ring-0 md:rounded-[26px]" key={item.title}>
                <CardContent className="grid grid-cols-[104px_1fr] gap-3 p-0 sm:grid-cols-[180px_1fr] lg:grid-cols-[150px_1fr] xl:grid-cols-[190px_1fr]">
                  <div className="relative min-h-[118px] overflow-hidden rounded-[18px] md:min-h-[150px] md:rounded-[20px]">
                    <Image className="object-cover" src={item.image} alt={item.title} fill sizes="18vw" />
                  </div>
                  <CardHeader className="justify-center px-0 py-2 md:px-1 md:py-3">
                    <div className="mb-1.5 flex flex-wrap items-center gap-1.5 md:mb-2 md:gap-2">
                      <Badge variant="outline" className="h-5 rounded-full border-line bg-white px-2 text-[8px] font-black uppercase tracking-[0.08em] text-gold md:h-6 md:px-2.5 md:text-[10px] md:tracking-[0.12em]">
                        {item.category}
                      </Badge>
                      <span className="text-[10px] font-bold text-slate-500 md:text-xs">{item.date}</span>
                    </div>
                    <CardTitle className="line-clamp-2 text-sm font-black leading-snug text-ink md:text-lg">{item.title}</CardTitle>
                    <CardDescription className="line-clamp-1 text-xs font-medium leading-5 text-slate-500 md:line-clamp-2 md:text-sm">
                      {item.excerpt}
                    </CardDescription>
                    <a className="mt-1 inline-flex items-center gap-2 text-xs font-black text-ink" href={`/news/${item.slug}`}>
                      Read More <ArrowRight className="h-3.5 w-3.5" />
                    </a>
                  </CardHeader>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

export function StoriesNews() {
  return (
    <>
      <ProjectsSection />
      <NewsCenter />
    </>
  );
}

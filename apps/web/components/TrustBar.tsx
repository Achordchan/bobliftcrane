import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { defaultTrustBarContent, type TrustBarContent } from "@/lib/home-defaults";

export function TrustBar({ content = defaultTrustBarContent }: { content?: TrustBarContent }) {
  return (
    <section className="page-shell relative z-10 mt-0 max-w-[1500px] overflow-hidden border-y border-line/70 bg-transparent py-2 md:-mt-8 md:rounded-[28px] md:border md:border-white/80 md:bg-white/95 md:p-3 md:py-3 md:shadow-[0_24px_70px_rgba(8,35,74,0.12)] md:ring-1 md:ring-slate-900/5 lg:-mt-12 lg:rounded-[34px]">
      <div>
        <div className={`grid grid-cols-4 items-center gap-0 md:items-stretch md:gap-2 ${content.showIntroCard ? "md:grid-cols-5" : "md:grid-cols-4"}`}>
          {content.showIntroCard ? (
            <Card className="hidden justify-center py-0 text-white ring-white/10 md:flex md:min-h-[118px] md:rounded-[22px] md:bg-gradient-to-br md:from-navy md:to-[#0d356b] xl:min-h-[132px]">
              <CardHeader className="gap-1.5 px-5 py-5 xl:px-6">
                <Badge className="h-6 w-fit max-w-full rounded-full bg-white/10 px-3 text-[10px] font-black uppercase tracking-[0.22em] text-gold ring-1 ring-white/10">
                  {content.label}
                </Badge>
                <CardTitle className="text-xl font-black leading-tight text-white">{content.title}</CardTitle>
                <CardDescription className="max-w-[190px] text-xs font-semibold leading-5 text-white/62 xl:max-w-none xl:whitespace-nowrap">
                  {content.description}
                </CardDescription>
              </CardHeader>
            </Card>
          ) : null}
          {content.marks.map((item) => (
            <Card className="min-w-0 rounded-none border-0 bg-transparent py-0 text-center shadow-none ring-0 transition duration-300 hover:-translate-y-0.5 md:min-h-[118px] md:rounded-[22px] md:bg-gradient-to-br md:from-white md:to-[#f7faff] md:ring-line md:hover:-translate-y-1 md:hover:ring-gold/45 md:hover:shadow-[0_18px_44px_rgba(8,35,74,0.1)] xl:min-h-[132px]" key={item.name} size="sm">
              <CardContent className="flex h-full flex-col items-center justify-center border-l border-line/65 px-1 py-1 md:border-l-0 md:px-3 md:py-4">
                <div className="grid h-7 w-full place-items-center md:h-14">
                  <img alt={`${item.name} logo`} className={`${item.logoClass ?? "h-8 w-24 md:h-12 md:w-32"} max-w-full object-contain`} src={item.logo} />
                </div>
                {content.showMarkNames ? (
                  <CardDescription className="mt-2 hidden text-xs font-bold leading-5 text-slate-500 md:line-clamp-2">
                    {item.name}
                  </CardDescription>
                ) : null}
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </section>
  );
}

import Image from "next/image";
import type { ReactNode } from "react";

export function PageHero({
  title,
  description,
  image,
  label,
  children,
}: {
  title: string;
  description: string;
  image?: string;
  label: string;
  children?: ReactNode;
}) {
  return (
    <section className="relative overflow-hidden border-b border-line bg-[linear-gradient(135deg,#f8fbff_0%,#eef5ff_48%,#ffffff_100%)]">
      <div className="pointer-events-none absolute -right-24 top-8 h-72 w-72 rounded-full bg-blue-500/10 blur-3xl md:h-[420px] md:w-[420px]" />
      <div className="pointer-events-none absolute -left-24 bottom-0 h-60 w-60 rounded-full bg-gold/12 blur-3xl" />
      <div className="page-shell relative grid min-h-[340px] grid-cols-1 items-center gap-8 py-10 md:min-h-[430px] md:py-14 lg:grid-cols-[0.9fr_1.1fr] lg:gap-12">
        <div className="reveal max-w-[760px]">
          <p className="section-label mb-4">{label}</p>
          <h1 className="text-[42px] font-black leading-[0.96] tracking-[-0.06em] text-ink sm:text-[62px] lg:text-[78px]">
            {title}
          </h1>
          <p className="mt-5 max-w-[620px] text-base font-semibold leading-7 text-slate-600 md:text-lg md:leading-8">
            {description}
          </p>
          {children && <div className="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">{children}</div>}
        </div>
        <div className="relative min-h-[220px] overflow-hidden rounded-[28px] border border-white bg-white/70 shadow-[0_30px_90px_rgba(8,35,74,0.1)] md:min-h-[320px] lg:min-h-[360px]">
          {image ? (
            <Image className="object-cover" src={image} alt="" fill priority sizes="(min-width: 1024px) 46vw, 100vw" />
          ) : (
            <div className="absolute inset-0 bg-[radial-gradient(circle_at_62%_42%,rgba(37,99,235,0.2),transparent_28%),linear-gradient(135deg,#ffffff,#edf4ff)]" />
          )}
          <div className="absolute inset-0 bg-[linear-gradient(120deg,rgba(7,31,66,0.12)_0%,rgba(7,31,66,0)_55%)]" />
        </div>
      </div>
    </section>
  );
}

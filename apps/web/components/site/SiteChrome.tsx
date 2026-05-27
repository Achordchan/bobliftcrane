import type { ReactNode } from "react";

import { BackToTop } from "@/components/BackToTop";
import { FinalCta, Footer } from "@/components/Footer";
import { FloatingContact } from "@/components/FloatingContact";
import { Header } from "@/components/Header";

export function SiteChrome({ children, showFinalCta = true }: { children: ReactNode; showFinalCta?: boolean }) {
  return (
    <>
      <Header />
      <main>{children}</main>
      {showFinalCta && <FinalCta />}
      <Footer />
      <FloatingContact />
      <BackToTop />
    </>
  );
}

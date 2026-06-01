import type { ReactNode } from "react";

import { BackToTop } from "@/components/BackToTop";
import { FinalCta, Footer } from "@/components/Footer";
import { FloatingContact } from "@/components/FloatingContact";
import { Header } from "@/components/Header";
import { getChromeContent } from "@/lib/cms-content";

export async function SiteChrome({
  children,
  showFinalCta = true,
}: {
  children: ReactNode;
  showFinalCta?: boolean;
}) {
  const chrome = await getChromeContent();

  return (
    <>
      <Header
        branding={chrome.branding}
        chromeUi={chrome.chromeUi}
        contact={chrome.contact}
        languageOptions={chrome.languageOptions}
        navItems={chrome.navItems}
        productNavGroups={chrome.productNavGroups}
        topBar={chrome.topBar}
      />
      <main>{children}</main>
      {showFinalCta && <FinalCta contact={chrome.contact} content={chrome.finalCta} />}
      <Footer branding={chrome.branding} chromeUi={chrome.chromeUi} contact={chrome.contact} content={chrome.footer} />
      <FloatingContact chromeUi={chrome.chromeUi} contact={chrome.contact} />
      <BackToTop label={chrome.chromeUi.backToTopLabel} />
    </>
  );
}

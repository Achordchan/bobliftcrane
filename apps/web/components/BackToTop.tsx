"use client";

import { useEffect, useState } from "react";
import { ArrowUp } from "lucide-react";

import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

export function BackToTop({ label = "Back to top" }: { label?: string }) {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    function updateVisibility() {
      setVisible(window.scrollY > 520);
    }

    updateVisibility();
    window.addEventListener("scroll", updateVisibility, { passive: true });

    return () => window.removeEventListener("scroll", updateVisibility);
  }, []);

  function scrollToTop() {
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  return (
    <Button
      aria-label={label}
      className={cn(
        "fixed bottom-28 right-4 z-30 size-10 rounded-full bg-navy p-0 text-white shadow-[0_18px_44px_rgba(8,35,74,0.24)] transition duration-300 hover:-translate-y-1 hover:bg-[#0b2d5b] md:bottom-24 md:z-40 2xl:bottom-8 2xl:right-8 2xl:size-14",
        visible ? "translate-y-0 opacity-100" : "pointer-events-none translate-y-4 opacity-0",
      )}
      onClick={scrollToTop}
      type="button"
    >
      <ArrowUp data-icon="inline-start" />
    </Button>
  );
}

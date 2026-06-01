export function Logo({
  homeLabel = "BOB-LIFT Home",
  imageAlt = "BOB-LIFT Cranes",
  homeHref = "/",
  src = "/images/bob-lift-logo.png",
  width = 188,
  height = 91,
}: {
  homeLabel?: string;
  imageAlt?: string;
  homeHref?: string;
  src?: string;
  width?: number;
  height?: number;
}) {
  return (
    <a className="flex items-center" href={homeHref} aria-label={homeLabel}>
      <img
        alt={imageAlt}
        className="h-[52px] w-auto object-contain lg:h-[58px]"
        height={height}
        src={src}
        width={width}
      />
    </a>
  );
}

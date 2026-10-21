/**
 * Resolve a file from `public/` against Vite's deployment base.
 *
 * Locally BASE_URL is `/`; on GitHub Pages it is the repository
 * subdirectory. Keeping this in one place prevents root-relative asset
 * paths from breaking the static showcase.
 */
export function publicAssetUrl(path: string): string {
  return `${import.meta.env.BASE_URL}${path.replace(/^\/+/, '')}`;
}

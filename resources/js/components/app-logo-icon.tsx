import type { SVGAttributes } from 'react';

/** Pictogramme Réf. Plomberie — identique à celui de la vitrine. */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path
                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"
                fill="currentColor"
            />
            <path
                d="M9 6h2v2H9V6zm4 0h2v2h-2V6z"
                fill="currentColor"
                opacity="0.4"
            />
        </svg>
    );
}

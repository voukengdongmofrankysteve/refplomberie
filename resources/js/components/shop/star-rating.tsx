import { useState } from 'react';

type StarRatingProps = {
    rating: number;
    /** Rend les étoiles cliquables (formulaire d'avis). */
    interactive?: boolean;
    onChange?: (rating: number) => void;
    className?: string;
};

/** Étoiles de notation, en lecture seule ou saisissables. */
export default function StarRating({
    rating,
    interactive = false,
    onChange,
    className = 'w-5 h-5',
}: StarRatingProps) {
    const [hover, setHover] = useState(0);

    return (
        <div
            className="flex gap-0.5"
            aria-label={interactive ? undefined : `Note : ${rating} sur 5`}
        >
            {[1, 2, 3, 4, 5].map((s) => (
                <button
                    key={s}
                    type="button"
                    onClick={interactive ? () => onChange?.(s) : undefined}
                    onMouseEnter={interactive ? () => setHover(s) : undefined}
                    onMouseLeave={interactive ? () => setHover(0) : undefined}
                    className={
                        interactive ? 'cursor-pointer' : 'cursor-default'
                    }
                    tabIndex={interactive ? 0 : -1}
                    aria-label={
                        interactive
                            ? `${s} étoile${s > 1 ? 's' : ''}`
                            : undefined
                    }
                    aria-hidden={interactive ? undefined : true}
                >
                    <svg
                        className={`${className} transition-colors ${
                            s <= (hover || Math.round(rating))
                                ? 'text-[#25D366]'
                                : 'text-gray-200'
                        }`}
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        aria-hidden="true"
                    >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                </button>
            ))}
        </div>
    );
}

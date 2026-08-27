import { youtubeEmbedUrl } from '@/lib/video';

/**
 * Vidéo tutoriel de la fiche produit — entièrement facultative.
 *
 * N'affiche rien si le produit n'en a pas : la grande majorité des fiches
 * n'en auront jamais, et ça ne doit laisser aucun bloc vide derrière elles.
 */
export default function ProductVideo({
    videoUrl,
}: {
    videoUrl: string | null;
}) {
    if (!videoUrl) {
        return null;
    }

    const embedUrl = youtubeEmbedUrl(videoUrl);

    return (
        <div className="mb-8 overflow-hidden rounded-2xl border border-[#E9ECEF] bg-white shadow-sm">
            <div className="border-b border-[#E9ECEF] px-6 py-4">
                <h2 className="font-display text-lg font-bold text-[#1A1A2E]">
                    Vidéo tutoriel
                </h2>
            </div>

            {embedUrl ? (
                <div className="aspect-video w-full bg-black">
                    <iframe
                        src={embedUrl}
                        title="Vidéo tutoriel du produit"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowFullScreen
                        className="h-full w-full"
                    />
                </div>
            ) : (
                <div className="p-6">
                    <a
                        href={videoUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-2 font-semibold text-[#25D366] hover:underline"
                    >
                        Voir la vidéo
                        <svg
                            className="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={2}
                            aria-hidden="true"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"
                            />
                        </svg>
                    </a>
                </div>
            )}
        </div>
    );
}

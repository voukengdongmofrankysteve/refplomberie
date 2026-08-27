/**
 * Reconnaît une URL YouTube et la convertit en URL d'intégration.
 *
 * Couvre les formes usuelles (`watch?v=`, `youtu.be/`, `shorts/`, déjà
 * `embed/`). Une URL qui ne correspond à aucune d'elles renvoie `null` : mieux
 * vaut proposer un simple lien de sortie que tenter d'intégrer n'importe quoi
 * dans un cadre vidéo.
 */
export function youtubeEmbedUrl(url: string): string | null {
    let parsed: URL;

    try {
        parsed = new URL(url);
    } catch {
        return null;
    }

    const host = parsed.hostname.replace(/^www\./, '');
    let id: string | null = null;

    if (host === 'youtu.be') {
        id = parsed.pathname.slice(1);
    } else if (host === 'youtube.com' || host === 'm.youtube.com') {
        if (parsed.pathname === '/watch') {
            id = parsed.searchParams.get('v');
        } else if (parsed.pathname.startsWith('/embed/')) {
            id = parsed.pathname.replace('/embed/', '');
        } else if (parsed.pathname.startsWith('/shorts/')) {
            id = parsed.pathname.replace('/shorts/', '');
        }
    }

    id = id?.split(/[?&]/)[0] ?? null;

    return id ? `https://www.youtube.com/embed/${id}` : null;
}

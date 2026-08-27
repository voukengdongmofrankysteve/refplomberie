import { useEffect, useState } from 'react';

type Countdown = {
    days: number;
    hours: number;
    minutes: number;
    seconds: number;
    ended: boolean;
};

function remaining(target: number): Countdown {
    const diff = Math.max(0, target - Date.now());

    return {
        days: Math.floor(diff / 86_400_000),
        hours: Math.floor((diff / 3_600_000) % 24),
        minutes: Math.floor((diff / 60_000) % 60),
        seconds: Math.floor((diff / 1_000) % 60),
        ended: diff <= 0,
    };
}

/**
 * Décompte vivant jusqu'à une date, à la seconde.
 *
 * Ne recalcule qu'une horloge locale : aucun aller-retour serveur, la seule
 * chose qui compte est la différence avec l'instant présent du navigateur.
 */
export function useCountdown(target: string): Countdown {
    const ts = new Date(target).getTime();
    const [value, setValue] = useState<Countdown>(() => remaining(ts));

    useEffect(() => {
        const tick = () => setValue(remaining(ts));

        tick();
        const timer = window.setInterval(tick, 1_000);

        return () => window.clearInterval(timer);
    }, [ts]);

    return value;
}

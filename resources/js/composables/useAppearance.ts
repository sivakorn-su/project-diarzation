import { onMounted, ref } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

const FORCED_APPEARANCE: Appearance = 'light';

export function updateTheme(_value: Appearance) {
    if (typeof window === 'undefined') {
        return;
    }

    document.documentElement.classList.remove('dark');
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    localStorage.setItem('appearance', FORCED_APPEARANCE);
    setCookie('appearance', FORCED_APPEARANCE);
    updateTheme(FORCED_APPEARANCE);
}

export function useAppearance() {
    const appearance = ref<Appearance>(FORCED_APPEARANCE);

    onMounted(() => {
        appearance.value = FORCED_APPEARANCE;
        localStorage.setItem('appearance', FORCED_APPEARANCE);
    });

    function updateAppearance(_value: Appearance) {
        appearance.value = FORCED_APPEARANCE;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', FORCED_APPEARANCE);

        // Store in cookie for SSR...
        setCookie('appearance', FORCED_APPEARANCE);

        updateTheme(FORCED_APPEARANCE);
    }

    return {
        appearance,
        updateAppearance,
    };
}

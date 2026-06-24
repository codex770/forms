const cache: Record<string, string> = {};

export async function autoTranslate(
    text: string,
    targetLang: 'de' | 'en',
): Promise<string> {
    if (targetLang === 'en') return text;

    const cacheKey = `${targetLang}:${text}`;
    if (cache[cacheKey]) return cache[cacheKey];

    try {
        const res = await fetch(
            `https://api.mymemory.translated.net/get?q=${encodeURIComponent(text)}&langpair=en|${targetLang}`,
        );
        const data = await res.json();
        const translated = data?.responseData?.translatedText ?? text;
        cache[cacheKey] = translated;
        return translated;
    } catch {
        return text;
    }
}

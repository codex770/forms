<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage<{ locale?: string }>();
const locale = computed<string>(() => (page.props as any).locale ?? 'de');

const toggle = () => {
    const next = locale.value === 'de' ? 'en' : 'de';
    router.patch('/settings/locale', { locale: next }, { preserveScroll: true });
};
</script>

<template>
    <button
        type="button"
        @click="toggle"
        class="inline-flex h-8 items-center gap-1 rounded border border-input bg-background px-2 text-xs font-semibold text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-1 focus:ring-ring"
        :title="locale === 'de' ? 'Switch to English' : 'Zu Deutsch wechseln'"
    >
        <span>{{ locale === 'de' ? '🇩🇪 DE' : '🇬🇧 EN' }}</span>
        <span class="hidden sm:inline text-muted-foreground/60">·</span>
        <span class="hidden sm:inline">{{ locale === 'de' ? 'EN' : 'DE' }}</span>
    </button>
</template>

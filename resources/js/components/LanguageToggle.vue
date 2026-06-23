<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useI18n } from '@/utils/i18n';
import { router } from '@inertiajs/vue3';

const { t, locale } = useI18n();

const switchLocale = (newLocale: 'de' | 'en') => {
    if (newLocale === locale.value) return;
    router.patch('/settings/locale', { locale: newLocale }, { preserveScroll: true });
};
</script>

<template>
    <div
        class="flex items-center gap-0.5 rounded-md border bg-background p-0.5"
        :title="t('lang.toggle')"
    >
        <Button
            type="button"
            size="sm"
            :variant="locale === 'de' ? 'default' : 'ghost'"
            class="h-7 px-2 text-xs"
            @click="switchLocale('de')"
        >
            DE
        </Button>
        <Button
            type="button"
            size="sm"
            :variant="locale === 'en' ? 'default' : 'ghost'"
            class="h-7 px-2 text-xs"
            @click="switchLocale('en')"
        >
            EN
        </Button>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface SeoProps {
    title: string;
    description: string;
    canonical: string;
    image?: string | null;
    type?: string;
    robots?: string;
    siteName?: string;
    jsonLd?: Record<string, unknown>[];
}

const props = defineProps<{ seo?: SeoProps | null }>();

// Serialise once. "<" is escaped so a description containing a closing script tag cannot break out.
const LT = String.fromCharCode(60);
const jsonLdScripts = computed<string[]>(() =>
    (props.seo?.jsonLd ?? []).map((obj) => JSON.stringify(obj).split(LT).join('\\u003c')),
);
</script>

<template>
    <Head v-if="seo" :title="seo.title">
        <meta head-key="description" name="description" :content="seo.description" />
        <meta head-key="robots" name="robots" :content="seo.robots ?? 'index, follow'" />
        <link head-key="canonical" rel="canonical" :href="seo.canonical" />

        <meta head-key="og:type" property="og:type" :content="seo.type ?? 'website'" />
        <meta head-key="og:url" property="og:url" :content="seo.canonical" />
        <meta head-key="og:title" property="og:title" :content="seo.title" />
        <meta head-key="og:description" property="og:description" :content="seo.description" />
        <meta v-if="seo.image" head-key="og:image" property="og:image" :content="seo.image" />
        <meta v-if="seo.siteName" head-key="og:site_name" property="og:site_name" :content="seo.siteName" />

        <meta head-key="twitter:card" name="twitter:card" :content="seo.image ? 'summary_large_image' : 'summary'" />
        <meta head-key="twitter:title" name="twitter:title" :content="seo.title" />
        <meta head-key="twitter:description" name="twitter:description" :content="seo.description" />
        <meta v-if="seo.image" head-key="twitter:image" name="twitter:image" :content="seo.image" />

        <!-- Text child (not v-html): Inertia's Head writes string children verbatim into the tag. -->
        <component :is="'script'" v-for="(json, i) in jsonLdScripts" :key="i" type="application/ld+json">{{ json }}</component>
    </Head>
</template>

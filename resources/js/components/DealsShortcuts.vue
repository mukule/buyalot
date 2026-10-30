<script setup lang="ts">
import { campaignHref, type CampaignSummary } from '@/lib/products';
import { Link } from '@inertiajs/vue3';
import { CalendarHeart, Flame, Percent, Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    campaigns: CampaignSummary[];
}>();

const shortcuts = [
    { label: "Today's Deals", hint: 'Ends at midnight', href: '/deals/today', icon: Flame, tone: 'bg-red-50 text-red-600 ring-red-100' },
    { label: 'Offers', hint: 'Price drops', href: '/deals?type=offer', icon: Percent, tone: 'bg-amber-50 text-amber-600 ring-amber-100' },
    { label: 'Seasonal Sales', hint: 'Holiday picks', href: '/deals?type=seasonal', icon: CalendarHeart, tone: 'bg-emerald-50 text-emerald-600 ring-emerald-100' },
    { label: 'All Deals', hint: 'Every promotion', href: '/deals', icon: Sparkles, tone: 'bg-sky-50 text-sky-600 ring-sky-100' },
];

// Named seasonal/offer campaigns get their own quick chips (e.g. "Black Friday").
const campaignChips = computed(() => props.campaigns.filter((c) => c.type !== 'daily_deal'));
</script>

<template>
    <div class="rounded-lg bg-white p-4 shadow">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <Link
                v-for="s in shortcuts"
                :key="s.href"
                :href="s.href"
                class="group flex items-center gap-3 rounded-lg p-3 ring-1 transition hover:shadow-md"
                :class="s.tone"
            >
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white shadow-sm">
                    <component :is="s.icon" class="h-5 w-5" />
                </span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-gray-800 group-hover:underline">{{ s.label }}</span>
                    <span class="block truncate text-xs text-gray-500">{{ s.hint }}</span>
                </span>
            </Link>
        </div>

        <div v-if="campaignChips.length" class="mt-3 flex flex-wrap gap-2">
            <Link
                v-for="c in campaignChips"
                :key="c.slug"
                :href="campaignHref(c)"
                class="rounded-full border px-3 py-1 text-xs font-medium text-gray-700 transition hover:border-primary hover:text-primary"
                :style="c.theme_color ? { borderColor: c.theme_color, color: c.theme_color } : undefined"
            >
                {{ c.title }}
            </Link>
        </div>
    </div>
</template>

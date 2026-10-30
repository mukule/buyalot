<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarHeart, Flame, Percent, PlusIcon, XIcon } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

// ─── Types ────────────────────────────────────────────────────────────────────
type CampaignType = 'daily_deal' | 'offer' | 'seasonal';

export interface AdminCampaign {
    id: number;
    title: string;
    slug: string;
    type: CampaignType;
    subtitle: string | null;
    description: string | null;
    banner_url: string | null;
    theme_color: string | null;
    discount_percent: number | null;
    starts_at: string | null;
    ends_at: string | null;
    is_active: boolean;
    show_on_home: boolean;
    priority: number;
    status: 'live' | 'scheduled' | 'ended' | 'inactive';
    products_count: number;
}

interface PaginationLink { url: string | null; label: string; active: boolean; }

const props = defineProps<{
    campaigns: { data: AdminCampaign[]; links: PaginationLink[]; current_page: number; last_page: number };
    types: Record<CampaignType, string>;
    filters: { type?: string; search?: string };
}>();

const typeIcons = { daily_deal: Flame, offer: Percent, seasonal: CalendarHeart };
const statusClasses: Record<AdminCampaign['status'], string> = {
    live:      'bg-green-100 text-green-700',
    scheduled: 'bg-blue-100 text-blue-700',
    ended:     'bg-gray-100 text-gray-500',
    inactive:  'bg-yellow-100 text-yellow-700',
};

// ─── Filters ──────────────────────────────────────────────────────────────────
const filters = reactive({ type: props.filters.type ?? '', search: props.filters.search ?? '' });
let filterTimer: ReturnType<typeof setTimeout> | null = null;
function applyFilters() {
    if (filterTimer) clearTimeout(filterTimer);
    filterTimer = setTimeout(() => {
        router.get(route('admin.campaigns.index'), filters, { preserveState: true, replace: true });
    }, 350);
}

// ─── Modal & form ─────────────────────────────────────────────────────────────
const showModal = ref(false);
const isSaving = ref(false);
const errors = ref<Record<string, string>>({});
const editing = ref<AdminCampaign | null>(null);
const bannerPreview = ref<string | null>(null);

const emptyForm = () => ({
    title: '',
    slug: '',
    type: 'offer' as CampaignType,
    subtitle: '',
    description: '',
    banner_image: null as File | null,
    remove_banner: false,
    theme_color: '',
    discount_percent: '' as number | string,
    starts_at: '',
    ends_at: '',
    is_active: true,
    show_on_home: true,
    priority: 0,
});
const form = reactive(emptyForm());

function openModal(campaign?: AdminCampaign) {
    errors.value = {};
    editing.value = campaign ?? null;
    Object.assign(form, emptyForm());
    bannerPreview.value = null;

    if (campaign) {
        Object.assign(form, {
            title: campaign.title,
            slug: campaign.slug,
            type: campaign.type,
            subtitle: campaign.subtitle ?? '',
            description: campaign.description ?? '',
            theme_color: campaign.theme_color ?? '',
            discount_percent: campaign.discount_percent ?? '',
            starts_at: campaign.starts_at ?? '',
            ends_at: campaign.ends_at ?? '',
            is_active: campaign.is_active,
            show_on_home: campaign.show_on_home,
            priority: campaign.priority,
        });
        bannerPreview.value = campaign.banner_url;
    }
    showModal.value = true;
}
const closeModal = () => (showModal.value = false);

function onBannerSelected(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (file) {
        form.banner_image = file;
        form.remove_banner = false;
        bannerPreview.value = URL.createObjectURL(file);
    }
}

function removeBanner() {
    form.banner_image = null;
    form.remove_banner = true;
    bannerPreview.value = null;
}

function save() {
    isSaving.value = true;
    errors.value = {};

    const payload = new FormData();
    payload.append('title', form.title);
    payload.append('slug', form.slug);
    payload.append('type', form.type);
    payload.append('subtitle', form.subtitle);
    payload.append('description', form.description);
    payload.append('theme_color', form.theme_color);
    payload.append('discount_percent', String(form.discount_percent ?? ''));
    payload.append('starts_at', form.starts_at);
    payload.append('ends_at', form.ends_at);
    payload.append('priority', String(form.priority ?? 0));
    payload.append('is_active', form.is_active ? '1' : '0');
    payload.append('show_on_home', form.show_on_home ? '1' : '0');
    if (form.remove_banner) payload.append('remove_banner', '1');
    if (form.banner_image) payload.append('banner_image', form.banner_image);

    const url = editing.value ? route('admin.campaigns.update', { campaign: editing.value.id }) : route('admin.campaigns.store');

    router.post(url, payload, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: (e) => (errors.value = e as Record<string, string>),
        onFinish: () => (isSaving.value = false),
    });
}

function destroy(campaign: AdminCampaign) {
    if (!confirm(`Delete "${campaign.title}"? Its products return to their normal prices.`)) return;
    router.delete(route('admin.campaigns.destroy', { campaign: campaign.id }), { preserveScroll: true });
}

function toggleActive(campaign: AdminCampaign) {
    openModal(campaign);
    form.is_active = !campaign.is_active;
    save();
}

const productsHref = (campaign: AdminCampaign) => route('admin.campaigns.show', { campaign: campaign.id });

const formatDate = (value: string | null) => (value ? new Date(value).toLocaleString() : '—');

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Deals & Offers', href: route('admin.campaigns.index') },
];
</script>

<template>
    <Head title="Deals & Offers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Deals & Offers</h1>
                        <p class="text-sm text-gray-500">
                            Run Today's Deals, Offers and Seasonal promotions. Create a campaign, then add products to it.
                        </p>
                    </div>
                    <button @click="openModal()" class="rounded-xl bg-primary px-4 py-2 text-white hover:opacity-90">+ New Campaign</button>
                </div>

                <!-- Filters -->
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:gap-4">
                    <input
                        v-model="filters.search"
                        @input="applyFilters"
                        type="text"
                        placeholder="Search campaigns"
                        class="w-full rounded border px-3 py-2 md:w-64"
                    />
                    <select v-model="filters.type" @change="applyFilters" class="rounded border px-3 py-2">
                        <option value="">All types</option>
                        <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>

                <!-- Table -->
                <div v-if="campaigns.data.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Products</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Runs</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="campaign in campaigns.data" :key="campaign.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img v-if="campaign.banner_url" :src="campaign.banner_url" class="h-10 w-16 rounded border object-cover" />
                                        <span
                                            v-else
                                            class="h-10 w-16 rounded border"
                                            :style="{ background: campaign.theme_color || '#f3f4f6' }"
                                        />
                                        <div>
                                            <div class="text-sm font-medium text-gray-800">{{ campaign.title }}</div>
                                            <div class="text-xs text-gray-500">/deals/{{ campaign.slug }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <span class="inline-flex items-center gap-1">
                                        <component :is="typeIcons[campaign.type]" class="h-4 w-4" />
                                        {{ types[campaign.type] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ campaign.discount_percent ? `${campaign.discount_percent}% off` : 'Featured only' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ campaign.products_count }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    <div>From: {{ formatDate(campaign.starts_at) }}</div>
                                    <div>To: {{ formatDate(campaign.ends_at) }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusClasses[campaign.status]">
                                        {{ campaign.status }}
                                    </span>
                                    <span v-if="campaign.show_on_home" class="ml-1 rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-700">
                                        Home
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    <Link :href="productsHref(campaign)" class="mr-3 font-medium text-primary hover:underline">
                                        Products
                                    </Link>
                                    <button @click="openModal(campaign)" class="mr-3 text-primary hover:underline">Edit</button>
                                    <button @click="toggleActive(campaign)" class="mr-3 text-gray-600 hover:underline">
                                        {{ campaign.is_active ? 'Pause' : 'Activate' }}
                                    </button>
                                    <button @click="destroy(campaign)" class="text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="campaigns.last_page > 1" class="mt-4 flex flex-wrap justify-end gap-1">
                        <Link
                            v-for="link in campaigns.links"
                            :key="link.label"
                            :href="link.url ?? ''"
                            preserve-scroll
                            class="rounded border border-gray-300 px-3 py-1 text-sm hover:bg-gray-100"
                            :class="{ 'bg-gray-200 font-semibold': link.active, 'pointer-events-none opacity-50': !link.url }"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>

                <div v-else class="p-8 text-center">
                    <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No campaigns yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Create a Today's Deal, Offer or Seasonal promotion to get started.</p>
                </div>
            </div>
        </div>

        <!-- ── Modal ──────────────────────────────────────────────────────────── -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/30 py-10" @click.self="closeModal">
            <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-lg">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">{{ editing ? 'Edit Campaign' : 'New Campaign' }}</h2>
                    <button @click="closeModal"><XIcon class="h-5 w-5" /></button>
                </div>

                <form @submit.prevent="save" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs text-gray-500">Campaign type</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label
                                v-for="(label, key) in types"
                                :key="key"
                                class="flex cursor-pointer items-center gap-2 rounded border px-3 py-2 text-sm"
                                :class="form.type === key ? 'border-primary bg-primary/5 text-primary' : 'border-gray-200'"
                            >
                                <input type="radio" v-model="form.type" :value="key" class="sr-only" />
                                <component :is="typeIcons[key]" class="h-4 w-4" /> {{ label }}
                            </label>
                        </div>
                        <p v-if="form.type === 'daily_deal'" class="mt-1 text-xs text-gray-500">
                            Today's Deals without an end time automatically end at midnight.
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs text-gray-500">Title *</label>
                        <input v-model="form.title" type="text" placeholder="e.g. Black Friday Sale" class="w-full rounded border px-3 py-2" />
                        <p v-if="errors.title" class="mt-1 text-xs text-red-600">{{ errors.title }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs text-gray-500">URL slug (optional)</label>
                        <input v-model="form.slug" type="text" placeholder="auto from title" class="w-full rounded border px-3 py-2" />
                        <p v-if="errors.slug" class="mt-1 text-xs text-red-600">{{ errors.slug }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs text-gray-500">Default discount % (optional)</label>
                        <input
                            v-model="form.discount_percent"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            placeholder="Blank = feature only, no price change"
                            class="w-full rounded border px-3 py-2"
                        />
                        <p v-if="errors.discount_percent" class="mt-1 text-xs text-red-600">{{ errors.discount_percent }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs text-gray-500">Subtitle</label>
                        <input v-model="form.subtitle" type="text" placeholder="Short tagline shown next to the title" class="w-full rounded border px-3 py-2" />
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs text-gray-500">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full rounded border px-3 py-2" />
                    </div>

                    <div>
                        <label class="mb-1 block text-xs text-gray-500">Starts at</label>
                        <input v-model="form.starts_at" type="datetime-local" class="w-full rounded border px-3 py-2" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-gray-500">Ends at</label>
                        <input v-model="form.ends_at" type="datetime-local" class="w-full rounded border px-3 py-2" />
                        <p v-if="errors.ends_at" class="mt-1 text-xs text-red-600">{{ errors.ends_at }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs text-gray-500">Banner image (optional, JPG/PNG/WebP up to 5MB)</label>
                        <input type="file" accept="image/png,image/jpeg,image/webp" @change="onBannerSelected" class="w-full rounded border px-3 py-2" />
                        <div v-if="bannerPreview" class="mt-2 flex items-start gap-2">
                            <img :src="bannerPreview" class="max-h-32 rounded border object-cover" />
                            <button type="button" @click="removeBanner" class="text-xs text-red-600 hover:underline">Remove</button>
                        </div>
                        <p v-if="errors.banner_image" class="mt-1 text-xs text-red-600">{{ errors.banner_image }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs text-gray-500">Accent colour</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="color"
                                :value="form.theme_color || '#dc2626'"
                                @input="form.theme_color = ($event.target as HTMLInputElement).value"
                                class="h-10 w-12 rounded border"
                            />
                            <input v-model="form.theme_color" type="text" placeholder="#dc2626" class="w-full rounded border px-3 py-2" />
                        </div>
                        <p v-if="errors.theme_color" class="mt-1 text-xs text-red-600">{{ errors.theme_color }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs text-gray-500">Priority (lower shows first)</label>
                        <input v-model.number="form.priority" type="number" min="0" class="w-full rounded border px-3 py-2" />
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_active" type="checkbox" /> Active
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.show_on_home" type="checkbox" /> Show on home page
                    </label>

                    <div class="flex justify-end gap-2 md:col-span-2">
                        <button type="button" @click="closeModal" class="rounded border px-4 py-2">Cancel</button>
                        <button type="submit" :disabled="isSaving" class="rounded bg-primary px-4 py-2 text-white disabled:opacity-50">
                            {{ isSaving ? 'Saving…' : editing ? 'Save changes' : 'Create & add products' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

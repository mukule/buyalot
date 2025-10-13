<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const title = 'Create Warehouse';

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Warehouses', href: '/admin/warehouses' },
    { title, href: '' },
];

// Props passed from controller
const regions = page.props.regions || [];
const types = page.props.types || [];
const parentWarehouses = page.props.parentWarehouses || [];

const form = useForm({
    name: '',
    type: '',
    location: '',
    latitude: null as number | null,
    longitude: null as number | null,
    region_id: '',
    parent_warehouse_id: '',
    active: true,
});

const mapUrl = computed(() => {
    if (form.latitude != null && form.longitude != null) {
        const lat = form.latitude;
        const lon = form.longitude;
        return `https://staticmap.openstreetmap.de/staticmap.php?center=${lat},${lon}&zoom=15&size=600x300&markers=${lat},${lon},red-pushpin`;
    }
    return '';
});

function useCurrentLocation() {
    try {
        if (!('geolocation' in navigator)) {
            alert('Geolocation is not supported by this browser.');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const { latitude, longitude } = pos.coords;
                form.latitude = Number(latitude.toFixed(7));
                form.longitude = Number(longitude.toFixed(7));
            },
            (err) => {
                console.error('Geolocation error', err);
                alert('Unable to retrieve current location. Please allow location access or enter coordinates manually.');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    } catch (e) {
        console.error('Geolocation not available', e);
    }
}

const submitForm = () => {
    form.post(route('admin.warehouses.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex h-full flex-1 flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(--card-foreground)]">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h4 class="text-2xl font-bold">{{ title }}</h4>
                    <Link href="/admin/warehouses" class="text-sm text-[color:var(--primary)] hover:underline">
                        ← Back
                    </Link>
                </div>

                <hr class="my-1 border-[color:var(--border)]" />

                <!-- Form -->
                <form @submit.prevent="submitForm" class="mt-2 space-y-4 px-4">
                    <!-- Warehouse Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Warehouse Name</label>
                        <input
                            v-model="form.name"
                            id="name"
                            type="text"
                            required
                            placeholder="Enter warehouse name"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Type Selector -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                        <select
                            v-model="form.type"
                            id="type"
                            required
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        >
                            <option value="">Select Type</option>
                            <option v-for="t in types" :key="t" :value="t">{{ t.replace('_', ' ').toUpperCase() }}</option>
                        </select>
                        <div v-if="form.errors.type" class="mt-1 text-sm text-red-600">
                            {{ form.errors.type }}
                        </div>
                    </div>

                    <!-- Region Selector -->
                    <div v-if="regions.length > 0">
                        <label for="region_id" class="block text-sm font-medium text-gray-700">Region</label>
                        <select
                            v-model="form.region_id"
                            id="region_id"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        >
                            <option value="">Select Region</option>
                            <option v-for="region in regions" :key="region.id" :value="region.id">
                                {{ region.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.region_id" class="mt-1 text-sm text-red-600">
                            {{ form.errors.region_id }}
                        </div>
                    </div>

                    <!-- Parent Warehouse Selector -->
                    <div v-if="parentWarehouses.length > 0">
                        <label for="parent_warehouse_id" class="block text-sm font-medium text-gray-700">Parent Warehouse</label>
                        <select
                            v-model="form.parent_warehouse_id"
                            id="parent_warehouse_id"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        >
                            <option value="">Select Parent Warehouse</option>
                            <option v-for="wh in parentWarehouses" :key="wh.id" :value="wh.id">
                                {{ wh.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.parent_warehouse_id" class="mt-1 text-sm text-red-600">
                            {{ form.errors.parent_warehouse_id }}
                        </div>
                    </div>

                    <!-- Location -->
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                        <input
                            v-model="form.location"
                            id="location"
                            type="text"
                            placeholder="Enter warehouse location (optional)"
                            class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                        />
                        <div v-if="form.errors.location" class="mt-1 text-sm text-red-600">
                            {{ form.errors.location }}
                        </div>
                    </div>

                    <!-- Geolocation (Latitude/Longitude) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Geolocation</label>
                        <div class="mt-1 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <input
                                    v-model.number="form.latitude"
                                    type="number"
                                    step="0.0000001"
                                    min="-90"
                                    max="90"
                                    placeholder="Latitude"
                                    class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                                />
                                <div v-if="form.errors.latitude" class="mt-1 text-sm text-red-600">{{ form.errors.latitude }}</div>
                            </div>
                            <div>
                                <input
                                    v-model.number="form.longitude"
                                    type="number"
                                    step="0.0000001"
                                    min="-180"
                                    max="180"
                                    placeholder="Longitude"
                                    class="w-full rounded border border-[color:var(--border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(--primary)] focus:outline-none"
                                />
                                <div v-if="form.errors.longitude" class="mt-1 text-sm text-red-600">{{ form.errors.longitude }}</div>
                            </div>
                            <div class="flex items-center">
                                <button type="button" @click="useCurrentLocation" class="w-full rounded bg-gray-200 px-3 py-2 text-gray-700 hover:bg-gray-300">
                                    Use Current Location
                                </button>
                            </div>
                        </div>
                        <div v-if="mapUrl" class="mt-3">
                            <img :src="mapUrl" alt="Map preview" class="w-full rounded border" />
                            <p class="mt-1 text-xs text-gray-500">Preview from OpenStreetMap based on the coordinates above.</p>
                        </div>
                    </div>

                    <!-- Active Checkbox -->
                    <div class="flex items-center space-x-2">
                        <input
                            id="active"
                            type="checkbox"
                            v-model="form.active"
                            class="h-4 w-4 rounded border border-gray-300 text-primary focus:ring-primary"
                        />
                        <label for="active" class="select-none text-sm font-medium text-gray-700">Active</label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded bg-[color:var(--primary)] px-4 py-2 text-white transition-colors duration-200 hover:bg-[color:var(--secondary)] hover:text-[color:var(--secondary-foreground)] disabled:opacity-50"
                        >
                            {{ form.processing ? 'Submitting...' : 'Create Warehouse' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>


























<!--<script setup lang="ts">-->
<!--import AppLayout from '@/layouts/AppLayout.vue';-->
<!--import { type BreadcrumbItem } from '@/types';-->
<!--import { Head, Link, useForm, usePage } from '@inertiajs/vue3';-->

<!--const page = usePage();-->
<!--const title = 'Create Warehouse';-->

<!--const breadcrumbs: BreadcrumbItem[] = [-->
<!--    { title: 'Dashboard', href: '/admin/dashboard' },-->
<!--    { title: 'Warehouses', href: '/admin/warehouses' },-->
<!--    { title, href: '' },-->
<!--];-->

<!--const form = useForm({-->
<!--    name: '',-->
<!--    location: '',-->
<!--    active: true,-->
<!--});-->
<!--</script>-->

<!--<template>-->
<!--    <Head :title="title" />-->

<!--    <AppLayout :breadcrumbs="breadcrumbs">-->
<!--        <div class="p-4">-->
<!--            <div class="flex h-full flex-1 flex-col space-y-4 rounded-xl bg-white p-4 text-[color:var(&#45;&#45;card-foreground)]">-->
<!--                <div class="flex items-center justify-between">-->
<!--                    <h4 class="text-2xl font-bold">{{ title }}</h4>-->
<!--                    <Link href="/admin/warehouses" class="text-sm text-[color:var(&#45;&#45;primary)] hover:underline">← Back</Link>-->
<!--                </div>-->

<!--                <hr class="my-1 border-[color:var(&#45;&#45;border)]" />-->

<!--                <form @submit.prevent="form.post('/admin/warehouses')" class="mt-2 space-y-4 px-4">-->
<!--                    &lt;!&ndash; Warehouse Name &ndash;&gt;-->
<!--                    <div>-->
<!--                        <input-->
<!--                            v-model="form.name"-->
<!--                            id="name"-->
<!--                            type="text"-->
<!--                            required-->
<!--                            placeholder="Enter warehouse name"-->
<!--                            class="w-full rounded border border-[color:var(&#45;&#45;border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(&#45;&#45;primary)] focus:outline-none"-->
<!--                        />-->
<!--                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">-->
<!--                            {{ form.errors.name }}-->
<!--                        </div>-->
<!--                    </div>-->

<!--                    &lt;!&ndash; Warehouse Location &ndash;&gt;-->
<!--                    <div>-->
<!--                        <input-->
<!--                            v-model="form.location"-->
<!--                            id="location"-->
<!--                            type="text"-->
<!--                            placeholder="Enter warehouse location (optional)"-->
<!--                            class="w-full rounded border border-[color:var(&#45;&#45;border)] px-3 py-2 focus:ring-2 focus:ring-[color:var(&#45;&#45;primary)] focus:outline-none"-->
<!--                        />-->
<!--                        <div v-if="form.errors.location" class="mt-1 text-sm text-red-600">-->
<!--                            {{ form.errors.location }}-->
<!--                        </div>-->
<!--                    </div>-->

<!--                    &lt;!&ndash; Active Checkbox &ndash;&gt;-->
<!--                    <div class="flex items-center space-x-2">-->
<!--                        <input-->
<!--                            id="active"-->
<!--                            type="checkbox"-->
<!--                            v-model="form.active"-->
<!--                            class="h-4 w-4 rounded border border-gray-300 text-primary focus:ring-primary"-->
<!--                        />-->
<!--                        <label for="active" class="select-none">Active</label>-->
<!--                    </div>-->

<!--                    <button-->
<!--                        type="submit"-->
<!--                        :disabled="form.processing"-->
<!--                        class="rounded bg-[color:var(&#45;&#45;primary)] px-4 py-2 text-white transition-colors duration-200 hover:bg-[color:var(&#45;&#45;secondary)] hover:text-[color:var(&#45;&#45;secondary-foreground)]"-->
<!--                    >-->
<!--                        {{ form.processing ? 'Submitting...' : 'Submit' }}-->
<!--                    </button>-->
<!--                </form>-->
<!--            </div>-->
<!--        </div>-->
<!--    </AppLayout>-->
<!--</template>-->

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Car, Check, Pencil, Plus, Search, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Make { id: number; name: string; active: boolean; models_count: number; listings_count: number; }
interface Model { id: number; name: string; active: boolean; listings_count: number; }

const props = defineProps<{
    makes: Make[];
    selected: { id: number; name: string; active: boolean; models: Model[] } | null;
    filters: { search?: string };
}>();

const indexUrl = route('admin.car-makes.index');
const opts = { preserveScroll: true, preserveState: true };

// ─── Makes ────────────────────────────────────────────────────────────────────
const search = ref(props.filters.search ?? '');
let searchTimer: ReturnType<typeof setTimeout> | null = null;
function onSearch() {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(indexUrl, { search: search.value || undefined, make: props.selected?.id }, { ...opts, replace: true });
    }, 300);
}

function selectMake(make: Make) {
    router.get(indexUrl, { search: search.value || undefined, make: make.id }, { preserveScroll: true, preserveState: true, only: ['selected'] });
}

const showNewMake = ref(false);
const newMake = useForm({ name: '', models: '' });
function createMake() {
    newMake.post(route('admin.car-makes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            newMake.reset();
            showNewMake.value = false;
        },
    });
}

const makeErrors = ref<Record<string, string>>({});
function updateMake(make: { id: number }, data: Record<string, any>, onSuccess?: () => void) {
    router.patch(route('admin.car-makes.update', { carMake: make.id }), data, {
        ...opts,
        onSuccess: () => {
            makeErrors.value = {};
            onSuccess?.();
        },
        onError: (e) => (makeErrors.value = e as Record<string, string>),
    });
}

function deleteMake(make: { id: number; name: string }, listings: number) {
    const warn = listings ? `\n\n${listings} listing(s) use this make; they keep their make/model text.` : '';
    if (!confirm(`Delete "${make.name}" and all its models?${warn}`)) return;
    router.delete(route('admin.car-makes.destroy', { carMake: make.id }), { preserveScroll: true });
}

const selectedListings = computed(() => props.makes.find((m) => m.id === props.selected?.id)?.listings_count ?? 0);

// Rename the selected make
const renamingMake = ref(false);
const makeName = ref('');
function startRenameMake() {
    makeName.value = props.selected?.name ?? '';
    renamingMake.value = true;
}
function saveMakeName() {
    if (!props.selected || !makeName.value.trim()) return;
    updateMake(props.selected, { name: makeName.value }, () => (renamingMake.value = false));
}

// ─── Models ───────────────────────────────────────────────────────────────────
const modelSearch = ref('');
const visibleModels = computed(() => {
    const q = modelSearch.value.trim().toLowerCase();
    const list = props.selected?.models ?? [];
    return q ? list.filter((m) => m.name.toLowerCase().includes(q)) : list;
});

const newModels = useForm({ names: '' });
function addModels() {
    if (!props.selected) return;
    newModels.post(route('admin.car-makes.models.store', { carMake: props.selected.id }), {
        ...opts,
        onSuccess: () => newModels.reset(),
    });
}

const editingModelId = ref<number | null>(null);
const modelName = ref('');
const modelError = ref<string | null>(null);
function startRenameModel(model: Model) {
    editingModelId.value = model.id;
    modelName.value = model.name;
    modelError.value = null;
}
function updateModel(model: Model, data: Record<string, any>, onSuccess?: () => void) {
    if (!props.selected) return;
    router.patch(route('admin.car-makes.models.update', { carMake: props.selected.id, carModel: model.id }), data, {
        ...opts,
        onSuccess: () => onSuccess?.(),
        onError: (e) => (modelError.value = Object.values(e)[0] as string),
    });
}
function saveModelName(model: Model) {
    if (!modelName.value.trim() || modelName.value.trim() === model.name) {
        editingModelId.value = null;
        return;
    }
    updateModel(model, { name: modelName.value }, () => (editingModelId.value = null));
}
function deleteModel(model: Model) {
    if (!props.selected) return;
    const warn = model.listings_count ? `\n\n${model.listings_count} listing(s) use it; they keep the text.` : '';
    if (!confirm(`Delete model "${model.name}"?${warn}`)) return;
    router.delete(route('admin.car-makes.models.destroy', { carMake: props.selected.id, carModel: model.id }), opts);
}

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Car Makes & Models', href: indexUrl },
];
</script>

<template>
    <Head title="Car Makes & Models" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <div class="flex flex-col gap-3 rounded-lg bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Car Makes & Models</h1>
                    <p class="text-sm text-gray-500">
                        These power the make/model pickers on Cars & Motors listings (vehicles and spare parts). Inactive ones are hidden from the
                        form. Renaming also updates existing listings.
                    </p>
                </div>
                <button class="inline-flex items-center gap-1 rounded-xl bg-primary px-4 py-2 text-white hover:opacity-90" @click="showNewMake = !showNewMake">
                    <Plus class="h-4 w-4" /> New make
                </button>
            </div>

            <!-- New make -->
            <form v-if="showNewMake" class="grid grid-cols-1 gap-3 rounded-lg bg-white p-4 shadow-sm md:grid-cols-3" @submit.prevent="createMake">
                <div>
                    <label class="mb-1 block text-xs text-gray-500">Make name *</label>
                    <input v-model="newMake.name" type="text" placeholder="e.g. Toyota" class="w-full rounded border px-3 py-2" autofocus />
                    <p v-if="newMake.errors.name" class="mt-1 text-xs text-red-600">{{ newMake.errors.name }}</p>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs text-gray-500">Models (optional — one per line or comma-separated)</label>
                    <textarea v-model="newMake.models" rows="2" placeholder="Corolla, Camry, Hilux, Land Cruiser" class="w-full rounded border px-3 py-2" />
                </div>
                <div class="flex justify-end gap-2 md:col-span-3">
                    <button type="button" class="rounded border px-4 py-2" @click="showNewMake = false">Cancel</button>
                    <button type="submit" :disabled="newMake.processing" class="rounded bg-primary px-4 py-2 text-white disabled:opacity-50">Add make</button>
                </div>
            </form>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <!-- Makes list -->
                <div class="rounded-lg bg-white p-4 shadow-sm lg:col-span-4">
                    <div class="relative mb-3">
                        <Search class="absolute top-2.5 left-3 h-4 w-4 text-gray-400" />
                        <input v-model="search" type="text" placeholder="Search makes" class="w-full rounded border py-2 pr-3 pl-9" @input="onSearch" />
                    </div>
                    <p class="mb-2 text-xs text-gray-500">{{ makes.length }} make(s)</p>
                    <ul class="max-h-[65vh] divide-y overflow-y-auto rounded border">
                        <li v-for="make in makes" :key="make.id">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm transition hover:bg-gray-50"
                                :class="selected?.id === make.id ? 'bg-primary/5 font-semibold text-primary' : 'text-gray-800'"
                                @click="selectMake(make)"
                            >
                                <span class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full" :class="make.active ? 'bg-green-500' : 'bg-gray-300'" />
                                    {{ make.name }}
                                </span>
                                <span class="text-xs font-normal text-gray-500">
                                    {{ make.models_count }} models<template v-if="make.listings_count"> · {{ make.listings_count }} listings</template>
                                </span>
                            </button>
                        </li>
                        <li v-if="!makes.length" class="px-3 py-6 text-center text-sm text-gray-500">No makes found.</li>
                    </ul>
                </div>

                <!-- Selected make + models -->
                <div class="rounded-lg bg-white p-4 shadow-sm lg:col-span-8">
                    <div v-if="!selected" class="flex h-full flex-col items-center justify-center gap-2 py-16 text-center text-gray-500">
                        <Car class="h-10 w-10 text-gray-300" />
                        <p>Select a make to manage its models.</p>
                    </div>

                    <template v-else>
                        <!-- Make header -->
                        <div class="mb-4 flex flex-col gap-2 border-b pb-4 md:flex-row md:items-center md:justify-between">
                            <div v-if="!renamingMake" class="flex items-center gap-2">
                                <h2 class="text-xl font-semibold text-gray-800">{{ selected.name }}</h2>
                                <button class="text-gray-400 hover:text-primary" title="Rename" @click="startRenameMake"><Pencil class="h-4 w-4" /></button>
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="selected.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ selected.active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <form v-else class="flex items-center gap-2" @submit.prevent="saveMakeName">
                                <input v-model="makeName" type="text" class="rounded border px-3 py-1.5" autofocus />
                                <button type="submit" class="text-green-600"><Check class="h-5 w-5" /></button>
                                <button type="button" class="text-gray-400" @click="renamingMake = false"><X class="h-5 w-5" /></button>
                                <span v-if="makeErrors.name" class="text-xs text-red-600">{{ makeErrors.name }}</span>
                            </form>

                            <div class="flex gap-2">
                                <button class="rounded border px-3 py-1.5 text-sm hover:bg-gray-50" @click="updateMake(selected, { active: !selected.active })">
                                    {{ selected.active ? 'Deactivate' : 'Activate' }}
                                </button>
                                <button
                                    class="rounded border border-red-300 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50"
                                    @click="deleteMake(selected, selectedListings)"
                                >
                                    Delete make
                                </button>
                            </div>
                        </div>

                        <!-- Add models -->
                        <form class="mb-4 flex flex-col gap-2 md:flex-row md:items-start" @submit.prevent="addModels">
                            <textarea
                                v-model="newModels.names"
                                rows="2"
                                :placeholder="`Add ${selected.name} models — one per line or comma-separated`"
                                class="flex-1 rounded border px-3 py-2 text-sm"
                            />
                            <button
                                type="submit"
                                :disabled="!newModels.names.trim() || newModels.processing"
                                class="inline-flex items-center justify-center gap-1 rounded bg-primary px-4 py-2 text-sm text-white disabled:opacity-50"
                            >
                                <Plus class="h-4 w-4" /> Add models
                            </button>
                        </form>

                        <div class="mb-2 flex items-center justify-between gap-2">
                            <p class="text-sm text-gray-600">{{ selected.models.length }} model(s)</p>
                            <input v-model="modelSearch" type="text" placeholder="Filter models" class="rounded border px-3 py-1.5 text-sm" />
                        </div>
                        <p v-if="modelError" class="mb-2 text-xs text-red-600">{{ modelError }}</p>

                        <div v-if="visibleModels.length" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Model</th>
                                        <th class="px-3 py-2 text-left">Listings</th>
                                        <th class="px-3 py-2 text-left">Status</th>
                                        <th class="px-3 py-2 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="model in visibleModels" :key="model.id" class="hover:bg-gray-50">
                                        <td class="px-3 py-2">
                                            <form v-if="editingModelId === model.id" class="flex items-center gap-2" @submit.prevent="saveModelName(model)">
                                                <input v-model="modelName" type="text" class="rounded border px-2 py-1" autofocus />
                                                <button type="submit" class="text-green-600"><Check class="h-4 w-4" /></button>
                                                <button type="button" class="text-gray-400" @click="editingModelId = null"><X class="h-4 w-4" /></button>
                                            </form>
                                            <span v-else class="font-medium text-gray-800">{{ model.name }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-gray-600">{{ model.listings_count }}</td>
                                        <td class="px-3 py-2">
                                            <button
                                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                                :class="model.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                                :title="model.active ? 'Click to deactivate' : 'Click to activate'"
                                                @click="updateModel(model, { active: !model.active })"
                                            >
                                                {{ model.active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap">
                                            <button class="mr-3 text-primary hover:underline" @click="startRenameModel(model)">Rename</button>
                                            <button class="text-red-600 hover:text-red-800" title="Delete" @click="deleteModel(model)">
                                                <Trash2 class="inline h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="py-6 text-center text-sm text-gray-500">
                            {{ selected.models.length ? 'No models match.' : 'No models yet — add some above.' }}
                        </p>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

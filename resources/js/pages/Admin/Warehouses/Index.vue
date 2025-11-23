<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, Warehouse } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, PlusIcon,MoreVerticalIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import axios from 'axios';
import ActionMenu from '@/components/ActionMenu.vue';

const showManagerModal = ref(false);
const selectedWarehouse = ref<WarehouseWithRelations | null>(null);
const formManagers = ref<WarehouseManager[]>([]);

const assignableUsers = ref<{ id: number; name: string; email: string }[]>([]);
const assignableRoles = ref<{ id: number; name: string }[]>([]);
interface WarehouseManager {
    name: string;
    role?: string;
    phone?: string;
}

interface WarehouseWithRelations extends Warehouse {
    hashid: string;
    code: string;
    type: string;
    region?: { id: number; name: string };
    capacity?: number;
    active: boolean;
    managers?: WarehouseManager[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
}

interface PaginatedResponse<T> {
    data: T[];
    links: PaginationLink[];
    meta: PaginationMeta;
}

const page = usePage<AppPageProps<{ warehouses: PaginatedResponse<WarehouseWithRelations> }>>();

const warehouses = computed(() => page.props.warehouses?.data || []);
const pagination = computed(() => {
    const { links, meta } = page.props.warehouses || {};
    return { links, meta };
});

const breadcrumbs = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Warehouses', href: route('admin.warehouses.index') },
];

// Action methods
function createWarehouse() {
    router.get(route('admin.warehouses.create'));
}

function showWarehouse(hashid: string) {
    if (!hashid) return console.error('Missing hashid for warehouse');
    router.get(route('admin.warehouses.show', { warehouse: hashid }));
}

function editWarehouse(hashid: string) {
    if (!hashid) return console.error('Missing hashid for warehouse');
    router.get(route('admin.warehouses.edit', { warehouse: hashid }));
}
function viewInventory(hashid: string) {
    if (!hashid) return console.error('Missing hashid for warehouse');
    router.get(route('admin.inventory', { warehouse: hashid }));
}

function toggleWarehouseStatus(hashid: string) {
    if (!hashid) return console.error('Missing hashid for warehouse');
    router.patch(route('admin.warehouses.toggle-status', { warehouse: hashid }), {}, {
        onSuccess: () => {
            router.get(route('admin.warehouses.index'), {}, { replace: true });
        }
    });
}

const statusClasses = (active: boolean) => ({
    'text-green-600 bg-green-50 px-2 py-1 rounded-md': active,
    'text-red-600 bg-red-50 px-2 py-1 rounded-md': !active,
});

// Build per-row actions for ActionMenu
const getActionItems = (warehouse: WarehouseWithRelations) => ([
    { label: 'View', onClick: () => showWarehouse(warehouse.hashid) },
    { label: 'Edit', onClick: () => editWarehouse(warehouse.hashid) },
    { label: 'Inventory', onClick: () => viewInventory(warehouse.hashid) },
    { label: (warehouse.managers?.length ? 'Manage Staff' : 'Assign Staff'), onClick: () => openManagerModal(warehouse) },
    { label: (warehouse.active ? 'Deactivate' : 'Activate'), onClick: () => toggleWarehouseStatus(warehouse.hashid), danger: !!warehouse.active },
]);

function openManagerModal(warehouse: WarehouseWithRelations) {
    selectedWarehouse.value = warehouse;
    showManagerModal.value = true;

    axios.get(route('admin.warehouses.assignable-users')).then((response) => {

        console.log("assignable-users");

        assignableUsers.value = response.data.users || [];
        formManagers.value =
            warehouse.managers?.map((m) => ({
                name: m.name,
                role: m.role || '',
                phone: m.phone || '',
            })) || [{ name: '', role: '' }];
    });

    axios.get(route('admin.warehouses.assignable-roles')).then((response) => {
        console.log("assignable-roles");
        assignableRoles.value = response.data.roles || [];
    });
}



// function openManagerModal(warehouse: WarehouseWithRelations) {
//     selectedWarehouse.value = warehouse;
//     showManagerModal.value = true;
//
//     // Fetch users with allowed roles
//     router.get(route('admin.warehouses.create'), {}, {
//         preserveScroll: true,
//         preserveState: true,
//         only: ['users'],
//         onSuccess: (page) => {
//             assignableUsers.value = page.props.users || [];
//             formManagers.value =
//                 warehouse.managers?.map((m) => ({
//                     name: m.name,
//                     role: m.role || '',
//                     phone: m.phone || '',
//                 })) || [{ name: '', role: '' }];
//         },
//     });
// }

function closeManagerModal() {
    showManagerModal.value = false;
    selectedWarehouse.value = null;
    formManagers.value = [];
}

function addManager() {
    // Default role to the first available assignable role (if loaded) to avoid null role submits
    const defaultRole = assignableRoles.value?.[0]?.name || '';
    formManagers.value.push({ name: '', role: defaultRole });
}

function removeManager(index: number) {
    formManagers.value.splice(index, 1);
}

function saveManagers() {
    if (!selectedWarehouse.value) return;

    // Client-side guard: block submit if any row misses name or role
    const invalid = formManagers.value.some(m => !m || !m.name || !m.role);
    if (invalid) {
        window.alert('Please ensure each manager row has both Name and Role selected before saving.');
        return;
    }

    router.post(
        route('admin.warehouses.assign-managers', { warehouse: selectedWarehouse.value.hashid }),
        { managers: formManagers.value },
        {
            onSuccess: () => closeManagerModal(),
        }
    );
}

</script>

<template>
    <Head title="Warehouses" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold text-gray-800">Warehouses</h1>
                    <button
                        @click="createWarehouse"
                        class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white"
                    >
                        + New Warehouse
                    </button>
                </div>

                <!-- Table -->
                <div v-if="warehouses.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase">Region</th>
<!--                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase">Staff</th>-->
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase">Receivables</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                        <tr
                            v-for="(warehouse, index) in warehouses"
                            :key="warehouse.hashid"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-4 py-3 text-gray-500">{{ index + 1 }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ warehouse.code }}</td>
                            <td
                                @click="showWarehouse(warehouse.hashid)"
                                class="cursor-pointer px-4 py-3 font-medium text-primary hover:underline"
                            >
                                {{ warehouse.name }}
                            </td>
                            <td class="px-4 py-3 capitalize text-gray-700">{{ warehouse.type }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ warehouse.region?.name ?? '—' }}
                            </td>
<!--                            <td class="px-4 py-3 text-gray-700">-->
<!--                                <div v-if="warehouse.managers?.length">-->
<!--                                    <div-->
<!--                                        v-for="(m, i) in warehouse.managers"-->
<!--                                        :key="i"-->
<!--                                        class="text-xs"-->
<!--                                    >-->
<!--                                        {{ m.name }}-->
<!--                                        <span v-if="m.role" class="text-gray-500">-->
<!--                                            ({{ m.role.replace('_', ' ') }})-->
<!--                                        </span>-->
<!--                                    </div>-->
<!--                                    <button-->
<!--                                        @click.stop="openManagerModal(warehouse)"-->
<!--                                        class="mt-1 text-blue-600 hover:underline text-xs"-->
<!--                                    >-->
<!--                                        Manage Staff-->
<!--                                    </button>-->
<!--                                </div>-->
<!--                                <div v-else>-->
<!--                                    <span class="text-gray-400">No Staff</span>-->
<!--                                    <button-->
<!--                                        @click.stop="openManagerModal(warehouse)"-->
<!--                                        class="ml-2 text-green-600 hover:underline text-xs"-->
<!--                                    >-->
<!--                                        Assign Staff-->
<!--                                    </button>-->
<!--                                </div>-->
<!--                            </td>-->
                            <td class="px-4 py-3 text-gray-700">
                                  <span
                                      v-if="warehouse.pending_receivables_count > 0"
                                      class="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700"
                                  >
                                    <span class="h-2 w-2 rounded-full bg-yellow-500"></span>
                                    {{ warehouse.pending_receivables_count }} pending
                                  </span>
                                <span v-else class="text-gray-400 text-xs">None</span>
                            </td>

                            <td class="px-4 py-3">
                                    <span :class="statusClasses(warehouse.active)">
                                        {{ warehouse.active ? 'Active' : 'Inactive' }}
                                    </span>
                            </td>


                            <td class="px-4 py-3 text-right">
                                <ActionMenu
                                    :items="getActionItems(warehouse)"
                                    align="right"
                                    :zIndex="60"
                                >
                                    <template #button>
                                        <MoreVerticalIcon class="w-5 h-5" />
                                    </template>
                                </ActionMenu>
                            </td>



                        </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="pagination.links?.length > 3" class="mt-4 flex items-center justify-between">
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium">{{ pagination.meta?.from }}</span>
                                to
                                <span class="font-medium">{{ pagination.meta?.to }}</span>
                                of
                                <span class="font-medium">{{ pagination.meta?.total }}</span>
                                results
                            </p>

                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                <template v-for="(link, index) in pagination.links" :key="index">
                                    <a
                                        :href="link.url ?? undefined"
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium"
                                        :class="{
                                            'z-10 bg-primary text-white': link.active,
                                            'text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50': !link.active,
                                            'rounded-l-md': index === 0,
                                            'rounded-r-md': index === pagination.links.length - 1,
                                            'pointer-events-none opacity-50': !link.url,
                                        }"
                                    >
                                        <component
                                            :is="index === 0 ? ChevronLeftIcon : index === pagination.links.length - 1 ? ChevronRightIcon : 'span'"
                                            class="h-5 w-5"
                                            v-if="index === 0 || index === pagination.links.length - 1"
                                        />
                                        <span v-else>{{ link.label }}</span>
                                    </a>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center">
                    <div class="p-8">
                        <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No warehouses</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new warehouse.</p>
                        <div class="mt-6">
                            <button
                                @click="createWarehouse"
                                class="hover:bg-primary-dark inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white"
                            >
                                <PlusIcon class="mr-1.5 h-5 w-5" />
                                New Warehouse
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Assign / Manage Managers Modal -->

            <!-- Manager Modal -->
            <div v-if="showManagerModal">
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40"
                    @click="closeManagerModal"
                ></div>

                <!-- Modal Content -->
                <div
                    class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto"
                >
                    <div
                        class="bg-white rounded-lg shadow-2xl w-full max-w-2xl p-6 relative"
                    >
                        <h2 class="text-lg font-semibold mb-4">
                            {{ selectedWarehouse?.managers?.length ? 'Manage Staff' : 'Assign Staff' }}
                        </h2>

                        <form @submit.prevent="saveManagers">
                            <div class="space-y-4 max-h-[60vh] overflow-y-auto">
                                <div
                                    v-for="(manager, index) in formManagers"
                                    :key="index"
                                    class="flex items-center gap-3 border-b pb-2"
                                >
                                    <!-- User Dropdown -->
                                    <select
                                        v-model="manager.name"
                                        class="border rounded-md px-3 py-2 flex-1"
                                    >
                                        <option value="" disabled>Select user</option>
                                        <option
                                            v-for="user in assignableUsers"
                                            :key="user.id"
                                            :value="user.name"
                                        >
                                            {{ user.name }}
                                        </option>
                                    </select>

                                    <!-- Role Dropdown -->
                                    <select
                                        v-model="manager.role"
                                        class="border rounded-md px-3 py-2 w-48 capitalize"
                                    >
                                        <option disabled value="">Select Role</option>
                                        <option v-for="r in assignableRoles" :key="r.id" :value="r.name">
                                            {{ r.name.replaceAll('_', ' ') }}
                                        </option>
                                    </select>

                                    <!-- Remove Button -->
                                    <button
                                        type="button"
                                        @click="removeManager(index)"
                                        class="text-red-600 hover:underline text-sm"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button
                                    type="button"
                                    @click="addManager"
                                    class="text-sm text-blue-600 hover:underline"
                                >
                                    + Add Another Manager
                                </button>
                            </div>

                            <div class="flex justify-end gap-2 mt-6">
                                <button
                                    type="button"
                                    @click="closeManagerModal"
                                    class="rounded-md border px-4 py-2 text-gray-700"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="rounded-md bg-primary px-4 py-2 text-white hover:bg-primary/90"
                                >
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


        </div>
    </AppLayout>
</template>



<!--<script setup lang="ts">-->
<!--import AppLayout from '@/layouts/AppLayout.vue';-->
<!--import type { AppPageProps, Warehouse } from '@/types';-->
<!--import { Head, router, usePage } from '@inertiajs/vue3';-->
<!--import { ChevronLeftIcon, ChevronRightIcon, PlusIcon } from 'lucide-vue-next';-->
<!--import { computed } from 'vue';-->

<!--interface WarehouseWithHashid extends Warehouse {-->
<!--    hashid: string;-->
<!--}-->

<!--interface PaginationLink {-->
<!--    url: string | null;-->
<!--    label: string;-->
<!--    active: boolean;-->
<!--}-->

<!--interface PaginationMeta {-->
<!--    current_page: number;-->
<!--    from: number;-->
<!--    last_page: number;-->
<!--    path: string;-->
<!--    per_page: number;-->
<!--    to: number;-->
<!--    total: number;-->
<!--}-->

<!--interface PaginatedResponse<T> {-->
<!--    data: T[];-->
<!--    links: PaginationLink[];-->
<!--    meta: PaginationMeta;-->
<!--}-->

<!--const page = usePage<AppPageProps<{ warehouses: PaginatedResponse<WarehouseWithHashid> }>>();-->

<!--const warehouses = computed(() => {-->
<!--    const data = page.props.warehouses?.data || [];-->
<!--    console.log('Warehouses data:', data);-->
<!--    data.forEach((w) => {-->
<!--        if (!w.hashid) {-->
<!--            console.warn('Missing hashid for warehouse:', w);-->
<!--        }-->
<!--    });-->
<!--    return data;-->
<!--});-->

<!--const pagination = computed(() => {-->
<!--    const { links, meta } = page.props.warehouses || {};-->
<!--    return { links, meta };-->
<!--});-->

<!--const breadcrumbs = [-->
<!--    { title: 'Dashboard', href: route('admin.dashboard') },-->
<!--    { title: 'Warehouses', href: route('admin.warehouses.index') },-->
<!--];-->

<!--// Action methods-->
<!--function createWarehouse() {-->
<!--    router.get(route('admin.warehouses.create'));-->
<!--}-->

<!--function showWarehouse(hashid: string) {-->
<!--    console.log('showWarehouse called with hashid:', hashid);-->
<!--    if (!hashid) {-->
<!--        console.error('showWarehouse called without hashid');-->
<!--        return;-->
<!--    }-->
<!--    router.get(route('admin.warehouses.show', { warehouse: hashid }));-->
<!--}-->

<!--function editWarehouse(hashid: string) {-->
<!--    console.log('editWarehouse called with hashid:', hashid);-->
<!--    if (!hashid) {-->
<!--        console.error('editWarehouse called without hashid');-->
<!--        return;-->
<!--    }-->
<!--    router.get(route('admin.warehouses.edit', { warehouse: hashid }));-->
<!--}-->

<!--function deleteWarehouse(hashid: string) {-->
<!--    console.log('deleteWarehouse called with hashid:', hashid);-->
<!--    if (!hashid) {-->
<!--        console.error('deleteWarehouse called without hashid');-->
<!--        return;-->
<!--    }-->
<!--    if (confirm('Are you sure you want to delete this warehouse?')) {-->
<!--        router.delete(route('admin.warehouses.destroy', { warehouse: hashid }));-->
<!--    }-->
<!--}-->

<!--const statusClasses = (active: boolean) => ({-->
<!--    'text-green-600': active,-->
<!--    'text-red-600': !active,-->
<!--});-->
<!--</script>-->

<!--<template>-->
<!--    <Head title="Warehouses" />-->

<!--    <AppLayout :breadcrumbs="breadcrumbs">-->
<!--        <div class="p-4">-->
<!--            <div class="card flex flex-col gap-6 rounded-lg bg-white p-4 shadow-sm">-->
<!--                &lt;!&ndash; Header &ndash;&gt;-->
<!--                <div class="flex items-center justify-between">-->
<!--                    <h1 class="text-2xl font-semibold text-gray-800">Warehouses</h1>-->
<!--                    <button @click="createWarehouse" class="hover:bg-primary-dark rounded-xl bg-primary px-4 py-2 text-white">+ New Warehouse</button>-->
<!--                </div>-->

<!--                &lt;!&ndash; Table &ndash;&gt;-->
<!--                <div v-if="warehouses.length" class="overflow-x-auto">-->
<!--                    <table class="min-w-full divide-y divide-gray-200">-->
<!--                        <thead class="bg-gray-50">-->
<!--                            <tr>-->
<!--                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>-->
<!--                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>-->
<!--                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>-->
<!--                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>-->
<!--                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>-->
<!--                            </tr>-->
<!--                        </thead>-->
<!--                        <tbody class="divide-y divide-gray-200 bg-white">-->
<!--                            <tr v-for="(warehouse, index) in warehouses" :key="warehouse.hashid" class="hover:bg-gray-50">-->
<!--                                <td class="px-4 py-4 text-sm text-gray-500">{{ index + 1 }}</td>-->
<!--                                <td-->
<!--                                    @click="showWarehouse(warehouse.hashid)"-->
<!--                                    class="cursor-pointer px-4 py-4 text-sm font-medium text-primary hover:underline"-->
<!--                                >-->
<!--                                    {{ warehouse.name }}-->
<!--                                </td>-->
<!--                                <td class="px-4 py-4 text-sm text-gray-500">{{ warehouse.location || 'N/A' }}</td>-->
<!--                                <td class="px-4 py-4 text-sm">-->
<!--                                    <span :class="statusClasses(warehouse.active)">-->
<!--                                        {{ warehouse.active ? 'Active' : 'Inactive' }}-->
<!--                                    </span>-->
<!--                                </td>-->
<!--                                <td class="px-4 py-4 text-right text-sm">-->
<!--                                    <button @click.stop="editWarehouse(warehouse.hashid)" class="mr-3 text-blue-600 hover:underline">Edit</button>-->
<!--                                    <button @click.stop="deleteWarehouse(warehouse.hashid)" class="text-red-600 hover:underline">Delete</button>-->
<!--                                </td>-->
<!--                            </tr>-->
<!--                        </tbody>-->
<!--                    </table>-->

<!--                    &lt;!&ndash; Pagination &ndash;&gt;-->
<!--                    <div v-if="pagination.links?.length > 3" class="mt-4 flex items-center justify-between">-->
<!--                        <div class="flex flex-1 justify-between sm:hidden">-->
<!--                            <a-->
<!--                                v-if="pagination.links[0].url"-->
<!--                                :href="pagination.links[0].url ?? undefined"-->
<!--                                class="inline-flex items-center rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"-->
<!--                            >-->
<!--                                Previous-->
<!--                            </a>-->
<!--                            <a-->
<!--                                v-if="pagination.links[pagination.links.length - 1].url"-->
<!--                                :href="pagination.links[pagination.links.length - 1].url ?? undefined"-->
<!--                                class="ml-3 inline-flex items-center rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"-->
<!--                            >-->
<!--                                Next-->
<!--                            </a>-->
<!--                        </div>-->

<!--                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">-->
<!--                            <p class="text-sm text-gray-700">-->
<!--                                Showing-->
<!--                                <span class="font-medium">{{ pagination.meta?.from }}</span>-->
<!--                                to-->
<!--                                <span class="font-medium">{{ pagination.meta?.to }}</span>-->
<!--                                of-->
<!--                                <span class="font-medium">{{ pagination.meta?.total }}</span>-->
<!--                                results-->
<!--                            </p>-->

<!--                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">-->
<!--                                <template v-for="(link, index) in pagination.links" :key="index">-->
<!--                                    <a-->
<!--                                        :href="link.url ?? undefined"-->
<!--                                        class="inline-flex items-center px-4 py-2 text-sm font-medium"-->
<!--                                        :class="{-->
<!--                                            'z-10 bg-primary text-white': link.active,-->
<!--                                            'text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50': !link.active,-->
<!--                                            'rounded-l-md': index === 0,-->
<!--                                            'rounded-r-md': index === pagination.links.length - 1,-->
<!--                                            'pointer-events-none opacity-50': !link.url,-->
<!--                                        }"-->
<!--                                    >-->
<!--                                        <component-->
<!--                                            :is="index === 0 ? ChevronLeftIcon : index === pagination.links.length - 1 ? ChevronRightIcon : 'span'"-->
<!--                                            class="h-5 w-5"-->
<!--                                            v-if="index === 0 || index === pagination.links.length - 1"-->
<!--                                        />-->
<!--                                        <span v-else>{{ link.label }}</span>-->
<!--                                    </a>-->
<!--                                </template>-->
<!--                            </nav>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->

<!--                &lt;!&ndash; Empty State &ndash;&gt;-->
<!--                <div v-else class="text-center">-->
<!--                    <div class="p-8">-->
<!--                        <PlusIcon class="mx-auto h-12 w-12 text-gray-400" />-->
<!--                        <h3 class="mt-2 text-sm font-medium text-gray-900">No warehouses</h3>-->
<!--                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new warehouse.</p>-->
<!--                        <div class="mt-6">-->
<!--                            <button-->
<!--                                @click="createWarehouse"-->
<!--                                class="hover:bg-primary-dark inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-semibold text-white"-->
<!--                            >-->
<!--                                <PlusIcon class="mr-1.5 h-5 w-5" />-->
<!--                                New Warehouse-->
<!--                            </button>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
<!--    </AppLayout>-->
<!--</template>-->

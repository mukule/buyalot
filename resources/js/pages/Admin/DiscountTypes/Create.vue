<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    code: '',
    description: '',
});

const submit = () => {
    form.post(route('admin.discount-types.store'));
};
</script>

<template>
    <AppLayout>
        <div class="p-6 max-w-2xl mx-auto bg-white rounded shadow">
            <h2 class="text-xl font-semibold mb-4">Create Discount Type</h2>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium">Name</label>
                    <input v-model="form.name" class="w-full rounded border px-3 py-2" type="text" />
                    <div v-if="form.errors.name" class="text-red-500 text-sm">{{ form.errors.name }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium">Code</label>
                    <input v-model="form.code" class="w-full rounded border px-3 py-2" type="text" />
                    <div v-if="form.errors.code" class="text-red-500 text-sm">{{ form.errors.code }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium">Description</label>
                    <textarea v-model="form.description" class="w-full rounded border px-3 py-2"></textarea>
                </div>

                <button type="submit" class="rounded bg-primary px-4 py-2 text-white hover:bg-primary/90" :disabled="form.processing">
                    Save
                </button>
            </form>
        </div>
    </AppLayout>
</template>

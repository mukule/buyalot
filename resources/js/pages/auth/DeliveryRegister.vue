<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AuthLayout from '@/layouts/AuthLayout.vue';

const page = usePage();
const appName = computed(() => page.props.appName || 'Bianlina');

const props = defineProps<{
    transportTypes: string[];
}>();

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    id_number: '',
    id_copy: null as File | null,
    kra_pin: '',
    kra_copy: null as File | null,
    address: '',
    transport_type: '',
    transport_registration_number: '',
    transport_details: '',
});

const idCopyInput = ref<HTMLInputElement | null>(null);
const kraCopyInput = ref<HTMLInputElement | null>(null);

const idCopyName = computed(() => form.id_copy?.name ?? 'Choose file (PDF or image)');
const kraCopyName = computed(() => form.kra_copy?.name ?? 'Choose file (PDF or image)');

function onIdCopyChange(e: Event) {
    const target = e.target as HTMLInputElement;
    form.id_copy = target.files?.[0] ?? null;
}

function onKraCopyChange(e: Event) {
    const target = e.target as HTMLInputElement;
    form.kra_copy = target.files?.[0] ?? null;
}

function submit() {
    form.post(route('delivery.register.store'), {
        forceFormData: true,
        onFinish: () => {
            form.reset('password', 'password_confirmation');
            if (idCopyInput.value) idCopyInput.value.value = '';
            if (kraCopyInput.value) kraCopyInput.value.value = '';
        },
    });
}
</script>

<template>
    <AuthLayout
        title="Register as delivery partner"
        description="Submit your details and KYC. You can log in after admin approval."
    >
        <Head :title="`${appName} – Delivery Partner Registration`" />

        <div class="flex flex-col gap-6">
            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="name">Full name</Label>
                        <Input id="name" v-model="form.name" required maxlength="255" class="h-11" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            class="h-11"
                            placeholder="you@example.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input id="phone" v-model="form.phone" required class="h-11" />
                        <InputError :message="form.errors.phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            v-model="form.password"
                            required
                            class="h-11"
                            placeholder="••••••••"
                        />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirm password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            v-model="form.password_confirmation"
                            required
                            class="h-11"
                        />
                    </div>
                </div>

                <hr class="my-2 border-border" />

                <h3 class="text-sm font-semibold text-foreground">KYC</h3>
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="id_number">ID number</Label>
                        <Input id="id_number" v-model="form.id_number" required class="h-11" />
                        <InputError :message="form.errors.id_number" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Copy of ID (PDF or image, max 5MB)</Label>
                        <div class="flex items-center gap-2">
                            <input
                                ref="idCopyInput"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="text-sm file:mr-2 file:rounded file:border-0 file:bg-primary file:px-4 file:py-2 file:text-primary-foreground"
                                @change="onIdCopyChange"
                            />
                        </div>
                        <span class="text-xs text-muted-foreground">{{ idCopyName }}</span>
                        <InputError :message="form.errors.id_copy" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="kra_pin">KRA PIN</Label>
                        <Input id="kra_pin" v-model="form.kra_pin" required class="h-11" />
                        <InputError :message="form.errors.kra_pin" />
                    </div>
                    <div class="grid gap-2">
                        <Label>KRA copy (PDF or image, max 5MB)</Label>
                        <div class="flex items-center gap-2">
                            <input
                                ref="kraCopyInput"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="text-sm file:mr-2 file:rounded file:border-0 file:bg-primary file:px-4 file:py-2 file:text-primary-foreground"
                                @change="onKraCopyChange"
                            />
                        </div>
                        <span class="text-xs text-muted-foreground">{{ kraCopyName }}</span>
                        <InputError :message="form.errors.kra_copy" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="address">Where you stay / Address</Label>
                    <textarea
                        id="address"
                        v-model="form.address"
                        required
                        rows="3"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <InputError :message="form.errors.address" />
                </div>

                <hr class="my-2 border-border" />

                <h3 class="text-sm font-semibold text-foreground">Transport</h3>
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="transport_type">Type of transport</Label>
                        <select
                            id="transport_type"
                            v-model="form.transport_type"
                            required
                            class="flex h-11 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="">Select...</option>
                            <option v-for="t in transportTypes" :key="t" :value="t">
                                {{ t.replace(/_/g, ' ') }}
                            </option>
                        </select>
                        <InputError :message="form.errors.transport_type" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="transport_registration_number">Registration number of transport</Label>
                        <Input
                            id="transport_registration_number"
                            v-model="form.transport_registration_number"
                            class="h-11"
                            placeholder="e.g. plate or vehicle reg"
                        />
                        <InputError :message="form.errors.transport_registration_number" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="transport_details">Other transport details (optional)</Label>
                        <Input
                            id="transport_details"
                            v-model="form.transport_details"
                            class="h-11"
                            placeholder="Make, model, etc."
                        />
                        <InputError :message="form.errors.transport_details" />
                    </div>
                </div>

                <Button type="submit" class="h-11 w-full text-white" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Submit registration
                </Button>
            </form>

            <p class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('delivery.login')">Sign in</TextLink>
            </p>
        </div>
    </AuthLayout>
</template>

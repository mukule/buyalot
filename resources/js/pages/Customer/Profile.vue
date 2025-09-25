<template>
    <AppLayout title="My Profile">
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-4xl mx-auto">
                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
                    <p class="text-gray-600 mt-2">Manage your account information and preferences</p>
                </div>

                <!-- Success/Error Messages -->
                <div v-if="$page.props.flash?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ $page.props.flash.success }}
                </div>

                <div v-if="$page.props.flash?.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ $page.props.flash.error }}
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Profile Information Card -->
                    <div class="lg:col-span-2">
                        <div class="bg-white shadow rounded-lg p-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Profile Information</h2>

                            <form @submit.prevent="updateProfile" enctype="multipart/form-data">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- First Name -->
                                    <div>
                                        <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                        <input
                                            v-model="form.first_name"
                                            type="text"
                                            id="first_name"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-300': $page.props.errors?.first_name }"
                                        >
                                        <p v-if="$page.props.errors?.first_name" class="mt-1 text-sm text-red-600">
                                            {{ $page.props.errors.first_name }}
                                        </p>
                                    </div>

                                    <!-- Last Name -->
                                    <div>
                                        <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                        <input
                                            v-model="form.last_name"
                                            type="text"
                                            id="last_name"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-300': $page.props.errors?.last_name }"
                                        >
                                        <p v-if="$page.props.errors?.last_name" class="mt-1 text-sm text-red-600">
                                            {{ $page.props.errors.last_name }}
                                        </p>
                                    </div>

                                    <!-- Email -->
                                    <div class="md:col-span-2">
                                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            id="email"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-300': $page.props.errors?.email }"
                                        >
                                        <p v-if="$page.props.errors?.email" class="mt-1 text-sm text-red-600">
                                            {{ $page.props.errors.email }}
                                        </p>
                                    </div>

                                    <!-- Phone -->
                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                        <input
                                            v-model="form.phone"
                                            type="text"
                                            id="phone"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-300': $page.props.errors?.phone }"
                                        >
                                        <p v-if="$page.props.errors?.phone" class="mt-1 text-sm text-red-600">
                                            {{ $page.props.errors.phone }}
                                        </p>
                                    </div>

                                    <!-- Date of Birth -->
                                    <div>
                                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                                        <input
                                            v-model="form.date_of_birth"
                                            type="date"
                                            id="date_of_birth"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-300': $page.props.errors?.date_of_birth }"
                                        >
                                        <p v-if="$page.props.errors?.date_of_birth" class="mt-1 text-sm text-red-600">
                                            {{ $page.props.errors.date_of_birth }}
                                        </p>
                                    </div>

                                    <!-- Gender -->
                                    <div class="md:col-span-2">
                                        <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                                        <select
                                            v-model="form.gender"
                                            id="gender"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-300': $page.props.errors?.gender }"
                                        >
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                            <option value="prefer_not_to_say">Prefer not to say</option>
                                        </select>
                                        <p v-if="$page.props.errors?.gender" class="mt-1 text-sm text-red-600">
                                            {{ $page.props.errors.gender }}
                                        </p>
                                    </div>

                                    <!-- Avatar -->
                                    <div class="md:col-span-2">
                                        <label for="avatar" class="block text-sm font-medium text-gray-700">Profile Picture</label>
                                        <input
                                            @change="handleAvatarChange"
                                            type="file"
                                            id="avatar"
                                            accept="image/*"
                                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        >
                                        <p v-if="$page.props.errors?.avatar" class="mt-1 text-sm text-red-600">
                                            {{ $page.props.errors.avatar }}
                                        </p>

                                        <!-- Avatar Preview -->
                                        <div v-if="avatarPreview" class="mt-2">
                                            <img :src="avatarPreview" alt="Avatar preview" class="w-20 h-20 rounded-full object-cover">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <button
                                        type="submit"
                                        :disabled="processing"
                                        class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    >
                                        <span v-if="processing">Updating...</span>
                                        <span v-else>Update Profile</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Account Overview -->
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Overview</h3>

                            <!-- Avatar -->
                            <div class="text-center mb-4">
                                <img
                                    :src="avatarUrl"
                                    :alt="customer.first_name"
                                    class="w-20 h-20 rounded-full mx-auto object-cover"
                                >
                                <h4 class="mt-2 text-lg font-medium text-gray-900">
                                    {{ customer.first_name }} {{ customer.last_name }}
                                </h4>
                                <p class="text-sm text-gray-500">{{ customer.email }}</p>
                            </div>

                            <!-- Account Stats -->
                            <div class="border-t pt-4">
                                <div class="text-sm text-gray-600 space-y-2">
                                    <p><strong>Member since:</strong> {{ customer.created_at }}</p>
                                    <p><strong>Account Type:</strong> {{ customer.customer_type ? customer.customer_type.charAt(0).toUpperCase() + customer.customer_type.slice(1) : 'Individual' }}</p>
                                    <p><strong>Status:</strong>
                                        <span :class="statusClass">
                      {{ customer.status ? customer.status.charAt(0).toUpperCase() + customer.status.slice(1) : 'Active' }}
                    </span>
                                    </p>
                                    <p v-if="customer.provider"><strong>Connected via:</strong> {{ customer.provider.charAt(0).toUpperCase() + customer.provider.slice(1) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Security -->
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Security</h3>

                            <button
                                @click="showPasswordForm = !showPasswordForm"
                                class="w-full font-medium py-2 px-4 rounded-md mb-3"
                                :class="customer.has_password ? 'bg-gray-600 hover:bg-gray-700 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white'"
                            >
                                {{ customer.has_password ? 'Change Password' : 'Set Password' }}
                            </button>

                            <!-- Password Form -->
                            <div v-show="showPasswordForm" class="mt-4">
                                <form @submit.prevent="updatePassword">
                                    <div v-if="customer.has_password" class="mb-4">
                                        <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                        <input
                                            v-model="passwordForm.current_password"
                                            type="password"
                                            id="current_password"
                                            required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-300': $page.props.errors?.current_password }"
                                        >
                                        <p v-if="$page.props.errors?.current_password" class="mt-1 text-sm text-red-600">
                                            {{ $page.props.errors.current_password }}
                                        </p>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                                        <input
                                            v-model="passwordForm.password"
                                            type="password"
                                            id="password"
                                            required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-300': $page.props.errors?.password }"
                                        >
                                        <p v-if="$page.props.errors?.password" class="mt-1 text-sm text-red-600">
                                            {{ $page.props.errors.password }}
                                        </p>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                        <input
                                            v-model="passwordForm.password_confirmation"
                                            type="password"
                                            id="password_confirmation"
                                            required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="passwordProcessing"
                                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium py-2 px-4 rounded-md"
                                    >
                                        <span v-if="passwordProcessing">Updating...</span>
                                        <span v-else>{{ customer.has_password ? 'Update Password' : 'Set Password' }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Danger Zone -->
                        <div class="bg-red-50 border border-red-200 shadow rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-red-900 mb-4">Danger Zone</h3>
                            <p class="text-sm text-red-700 mb-4">
                                Once you delete your account, all of your data will be permanently removed. This action cannot be undone.
                            </p>

                            <button
                                @click="showDeleteForm = !showDeleteForm"
                                class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md text-sm"
                            >
                                Delete Account
                            </button>

                            <!-- Delete Form -->
                            <div v-show="showDeleteForm" class="mt-4">
                                <form @submit.prevent="deleteAccount">
                                    <div class="mb-4">
                                        <label for="confirmation" class="block text-sm font-medium text-gray-700">
                                            Type "DELETE" to confirm
                                        </label>
                                        <input
                                            v-model="deleteForm.confirmation"
                                            type="text"
                                            id="confirmation"
                                            required
                                            placeholder="DELETE"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                            :class="{ 'border-red-300': $page.props.errors?.confirmation }"
                                        >
                                        <p v-if="$page.props.errors?.confirmation" class="mt-1 text-sm text-red-600">
                                            {{ $page.props.errors.confirmation }}
                                        </p>
                                    </div>

                                    <div v-if="customer.has_password" class="mb-4">
                                        <label for="delete_password" class="block text-sm font-medium text-gray-700">Confirm your password</label>
                                        <input
                                            v-model="deleteForm.password"
                                            type="password"
                                            id="delete_password"
                                            required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                            :class="{ 'border-red-300': $page.props.errors?.password }"
                                        >
                                        <p v-if="$page.props.errors?.password" class="mt-1 text-sm text-red-600">
                                            {{ $page.props.errors.password }}
                                        </p>
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="deleteProcessing"
                                        @click="confirmDelete"
                                        class="w-full bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-medium py-2 px-4 rounded-md"
                                    >
                                        <span v-if="deleteProcessing">Deleting...</span>
                                        <span v-else>I understand, delete my account</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/MainLayout.vue';

const props = defineProps({
    customer: Object
})

// Form data
const form = useForm({
    first_name: props.customer.first_name,
    last_name: props.customer.last_name,
    email: props.customer.email,
    phone: props.customer.phone,
    date_of_birth: props.customer.date_of_birth,
    gender: props.customer.gender,
    avatar: null
})

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
    has_password: props.customer.has_password
})

const deleteForm = useForm({
    confirmation: '',
    password: '',
    has_password: props.customer.has_password
})

// State
const showPasswordForm = ref(false)
const showDeleteForm = ref(false)
const processing = ref(false)
const passwordProcessing = ref(false)
const deleteProcessing = ref(false)
const avatarPreview = ref(null)

// Computed properties
const avatarUrl = computed(() => {
    if (avatarPreview.value) return avatarPreview.value
    if (props.customer.avatar) return props.customer.avatar
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(props.customer.first_name + ' ' + props.customer.last_name)}&size=128&background=3B82F6&color=ffffff`
})

const statusClass = computed(() => {
    const status = props.customer.status || 'active'
    return status === 'active'
        ? 'inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800'
        : 'inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800'
})

// Methods
const handleAvatarChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        form.avatar = file
        const reader = new FileReader()
        reader.onload = (e) => {
            avatarPreview.value = e.target.result
        }
        reader.readAsDataURL(file)
    }
}

const updateProfile = () => {
    processing.value = true

    form.post(route('customer.profile.update'), {
        onFinish: () => processing.value = false,
        preserveScroll: true
    })
}

const updatePassword = () => {
    passwordProcessing.value = true

    passwordForm.put(route('customer.password.update'), {
        onSuccess: () => {
            passwordForm.reset('current_password', 'password', 'password_confirmation')
            showPasswordForm.value = false
        },
        onFinish: () => passwordProcessing.value = false,
        preserveScroll: true
    })
}

const confirmDelete = (event) => {
    if (!confirm('Are you absolutely sure? This action cannot be undone.')) {
        event.preventDefault()
        return false
    }
}

const deleteAccount = () => {
    deleteProcessing.value = true

    deleteForm.delete(route('customer.account.delete'), {
        onFinish: () => deleteProcessing.value = false
    })
}
</script>

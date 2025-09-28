
<template>
    <div class="vue-permissions-manager">
        <!-- Navigation Tabs -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
            'py-2 px-1 border-b-2 font-medium text-sm',
            activeTab === tab.id
              ? 'border-indigo-500 text-indigo-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
                >
                    {{ tab.name }}
                </button>
            </nav>
        </div>

        <!-- Permissions Tab -->
        <div v-show="activeTab === 'permissions'" class="space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">Permissions Management</h2>
                <button
                    @click="showCreatePermissionModal = true"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
                >
                    Add Permission
                </button>
            </div>

            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <li v-for="permission in permissions" :key="permission.id" class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                        <svg class="h-4 w-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ permission.name }}</div>
                                    <div class="text-sm text-gray-500">Guard: {{ permission.guard_name }}</div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button
                                    @click="editPermission(permission)"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="deletePermission(permission.id)"
                                    class="text-red-600 hover:text-red-900 text-sm font-medium"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Roles Tab -->
        <div v-show="activeTab === 'roles'" class="space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">Roles Management</h2>
                <button
                    @click="showCreateRoleModal = true"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
                >
                    Add Role
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="role in roles" :key="role.id" class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                {{ isEditingRole ? 'Edit Role' : 'Create Role' }}
                            </h3>
                            <form @submit.prevent="submitRoleForm">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Role Name</label>
                                    <input
                                        v-model="roleForm.name"
                                        type="text"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="e.g., admin"
                                    >
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Guard Name</label>
                                    <input
                                        v-model="roleForm.guard_name"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="web"
                                    >
                                </div>
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                                    <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-md p-3">
                                        <div v-for="permission in allPermissions" :key="permission.id" class="flex items-center mb-2">
                                            <input
                                                :id="`permission-${permission.id}`"
                                                type="checkbox"
                                                :value="permission.id"
                                                v-model="roleForm.permissions"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                            >
                                            <label :for="`permission-${permission.id}`" class="ml-2 block text-sm text-gray-900">
                                                {{ permission.name }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        @click="closeRoleModal"
                                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                                    >
                                        {{ isEditingRole ? 'Update' : 'Create' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Role Permissions Modal -->
                <div v-if="showRolePermissionsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div class="relative top-10 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
                        <div class="mt-3">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Manage Permissions for {{ currentRole?.name }}
                            </h3>
                            <form @submit.prevent="updateRolePermissions">
                                <div class="mb-6">
                                    <div class="max-h-64 overflow-y-auto border border-gray-300 rounded-md p-3">
                                        <div v-for="permission in allPermissions" :key="permission.id" class="flex items-center mb-2">
                                            <input
                                                :id="`role-permission-${permission.id}`"
                                                type="checkbox"
                                                :value="permission.id"
                                                v-model="selectedRolePermissions"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                            >
                                            <label :for="`role-permission-${permission.id}`" class="ml-2 block text-sm text-gray-900">
                                                {{ permission.name }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        @click="closeRolePermissionsModal"
                                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                                    >
                                        Update Permissions
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- User Roles Modal -->
                <div v-if="showUserRolesModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div class="mt-3">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Manage Roles for {{ currentUser?.name }}
                            </h3>
                            <form @submit.prevent="updateUserRoles">
                                <div class="mb-6">
                                    <div class="space-y-2">
                                        <div v-for="role in allRoles" :key="role.id" class="flex items-center">
                                            <input
                                                :id="`user-role-${role.id}`"
                                                type="checkbox"
                                                :value="role.id"
                                                v-model="selectedUserRoles"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                            >
                                            <label :for="`user-role-${role.id}`" class="ml-2 block text-sm text-gray-900">
                                                {{ role.name }}
                                                <span class="text-gray-500 text-xs">
                      ({{ role.permissions ? role.permissions.length : 0 }} permissions)
                    </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        @click="closeUserRolesModal"
                                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                                    >
                                        Update Roles
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Loading Overlay -->
                <div v-if="loading" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white p-6 rounded-lg shadow-lg">
                        <div class="flex items-center">
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
                            <span class="ml-3 text-gray-700">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

interface Permission {
    id: number
    name: string
    guard_name: string
}

interface Role {
    id: number
    name: string
    guard_name: string
    permissions: Permission[]
}

interface User {
    id: number
    name: string
    email: string
    roles: Role[]
}

// Reactive data
const activeTab = ref('permissions')
const loading = ref(false)

// Data arrays
const permissions = ref<Permission[]>([])
const roles = ref<Role[]>([])
const users = ref<User[]>([])
const allPermissions = ref<Permission[]>([])
const allRoles = ref<Role[]>([])

// Modal states
const showCreatePermissionModal = ref(false)
const showEditPermissionModal = ref(false)
const showCreateRoleModal = ref(false)
const showEditRoleModal = ref(false)
const showRolePermissionsModal = ref(false)
const showUserRolesModal = ref(false)

// Form states
const isEditingPermission = ref(false)
const isEditingRole = ref(false)
const editingPermissionId = ref<number | null>(null)
const editingRoleId = ref<number | null>(null)
const currentRole = ref<Role | null>(null)
const currentUser = ref<User | null>(null)

// Form data
const permissionForm = ref({
    name: '',
    guard_name: 'web'
})

const roleForm = ref({
    name: '',
    guard_name: 'web',
    permissions: [] as number[]
})

const selectedRolePermissions = ref<number[]>([])
const selectedUserRoles = ref<number[]>([])

// Tab configuration
const tabs = [
    { id: 'permissions', name: 'Permissions' },
    { id: 'roles', name: 'Roles' },
    { id: 'users', name: 'User Roles' }
]

// Initialize data
onMounted(() => {
    initializeData()
})

// Methods
async function initializeData() {
    loading.value = true
    try {
        await Promise.all([
            fetchPermissions(),
            fetchRoles(),
            fetchUsers()
        ])
    } catch (error) {
        console.error('Error initializing data:', error)
    } finally {
        loading.value = false
    }
}

async function fetchPermissions() {
    try {
        const response = await axios.get('/api/permissions')
        permissions.value = response.data
        allPermissions.value = response.data
    } catch (error) {
        console.error('Error fetching permissions:', error)
    }
}

async function fetchRoles() {
    try {
        const response = await axios.get('/api/roles')
        roles.value = response.data
        allRoles.value = response.data
    } catch (error) {
        console.error('Error fetching roles:', error)
    }
}

async function fetchUsers() {
    try {
        const response = await axios.get('/api/users')
        users.value = response.data
    } catch (error) {
        console.error('Error fetching users:', error)
    }
}

// Permission methods
async function submitPermissionForm() {
    loading.value = true
    try {
        if (isEditingPermission.value && editingPermissionId.value) {
            await axios.put(`/api/permissions/${editingPermissionId.value}`, permissionForm.value)
        } else {
            await axios.post('/api/permissions', permissionForm.value)
        }
        await fetchPermissions()
        closePermissionModal()
    } catch (error) {
        console.error('Error saving permission:', error)
    } finally {
        loading.value = false
    }
}

function editPermission(permission: Permission) {
    permissionForm.value = { ...permission }
    editingPermissionId.value = permission.id
    isEditingPermission.value = true
    showEditPermissionModal.value = true
}

async function deletePermission(id: number) {
    if (confirm('Are you sure you want to delete this permission?')) {
        loading.value = true
        try {
            await axios.delete(`/api/permissions/${id}`)
            await fetchPermissions()
        } catch (error) {
            console.error('Error deleting permission:', error)
        } finally {
            loading.value = false
        }
    }
}

function closePermissionModal() {
    showCreatePermissionModal.value = false
    showEditPermissionModal.value = false
    isEditingPermission.value = false
    editingPermissionId.value = null
    permissionForm.value = { name: '', guard_name: 'web' }
}

// Role methods
async function submitRoleForm() {
    loading.value = true
    try {
        if (isEditingRole.value && editingRoleId.value) {
            await axios.put(`/api/roles/${editingRoleId.value}`, roleForm.value)
        } else {
            await axios.post('/api/roles', roleForm.value)
        }
        await fetchRoles()
        closeRoleModal()
    } catch (error) {
        console.error('Error saving role:', error)
    } finally {
        loading.value = false
    }
}

function editRole(role: Role) {
    roleForm.value = {
        name: role.name,
        guard_name: role.guard_name,
        permissions: role.permissions ? role.permissions.map(p => p.id) : []
    }
    editingRoleId.value = role.id
    isEditingRole.value = true
    showEditRoleModal.value = true
}

async function deleteRole(id: number) {
    if (confirm('Are you sure you want to delete this role?')) {
        loading.value = true
        try {
            await axios.delete(`/api/roles/${id}`)
            await fetchRoles()
        } catch (error) {
            console.error('Error deleting role:', error)
        } finally {
            loading.value = false
        }
    }
}

function manageRolePermissions(role: Role) {
    currentRole.value = role
    selectedRolePermissions.value = role.permissions ? role.permissions.map(p => p.id) : []
    showRolePermissionsModal.value = true
}

async function updateRolePermissions() {
    if (!currentRole.value) return

    loading.value = true
    try {
        await axios.post(`/api/roles/${currentRole.value.id}/assign-permissions`, {
            permissions: selectedRolePermissions.value
        })
        await fetchRoles()
        closeRolePermissionsModal()
    } catch (error) {
        console.error('Error updating role permissions:', error)
    } finally {
        loading.value = false
    }
}

function closeRoleModal() {
    showCreateRoleModal.value = false
    showEditRoleModal.value = false
    isEditingRole.value = false
    editingRoleId.value = null
    roleForm.value = { name: '', guard_name: 'web', permissions: [] }
}

function closeRolePermissionsModal() {
    showRolePermissionsModal.value = false
    currentRole.value = null
    selectedRolePermissions.value = []
}

// User methods
function manageUserRoles(user: User) {
    currentUser.value = user
    selectedUserRoles.value = user.roles ? user.roles.map(r => r.id) : []
    showUserRolesModal.value = true
}

async function updateUserRoles() {
    if (!currentUser.value) return

    loading.value = true
    try {
        await axios.post(`/api/users/${currentUser.value.id}/assign-roles`, {
            roles: selectedUserRoles.value
        })
        await fetchUsers()
        closeUserRolesModal()
    } catch (error) {
        console.error('Error updating user roles:', error)
    } finally {
        loading.value = false
    }
}

async function removeUserRole(user: User, role: Role) {
    if (confirm(`Remove role "${role.name}" from ${user.name}?`)) {
        loading.value = true
        try {
            await axios.delete(`/api/users/${user.id}/remove-role`, {
                data: { role_id: role.id }
            })
            await fetchUsers()
        } catch (error) {
            console.error('Error removing user role:', error)
        } finally {
            loading.value = false
        }
    }
}

function closeUserRolesModal() {
    showUserRolesModal.value = false
    currentUser.value = null
    selectedUserRoles.value = []
}
</script>-gray-900">{{ role.name }}</h3>
<div class="flex space-x-2">
<button
    @click="editRole(role)"
    class="text-indigo-600 hover:text-indigo-900 text-sm"
>
    Edit
</button>
<button
    @click="deleteRole(role.id)"
    class="text-red-600 hover:text-red-900 text-sm"
>
    Delete
</button>
</div>
</div>
<div class="mb-4">
<p class="text-sm text-gray-500 mb-2">
    {{ role.permissions ? role.permissions.length : 0 }} permissions
</p>
<div class="flex flex-wrap gap-1">
                <span
                    v-for="permission in (role.permissions || []).slice(0, 3)"
                    :key="permission.id"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
                >
                  {{ permission.name }}
                </span>
    <span
        v-if="role.permissions && role.permissions.length > 3"
        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
    >
                  +{{ role.permissions.length - 3 }} more
                </span>
</div>
</div>
<button
    @click="manageRolePermissions(role)"
    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-indigo-700 bg-indigo-100 hover:bg-indigo-200"
>
Manage Permissions
</button>
</div>
</div>
</div>
</div>

<!-- Users Tab -->
<div v-show="activeTab === 'users'" class="space-y-6">
<div class="flex items-center justify-between">
    <h2 class="text-xl font-semibold text-gray-900">User Roles Management</h2>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-md">
    <ul class="divide-y divide-gray-200">
        <li v-for="user in users" :key="user.id" class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                    <span class="text-sm font-medium text-gray-700">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex space-x-1">
                  <span
                      v-for="role in user.roles"
                      :key="role.id"
                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                  >
                    {{ role.name }}
                    <button
                        @click="removeUserRole(user, role)"
                        class="ml-1 text-green-600 hover:text-green-800"
                    >
                      ×
                    </button>
                  </span>
                    </div>
                    <button
                        @click="manageUserRoles(user)"
                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                    >
                        Manage Roles
                    </button>
                </div>
            </div>
        </li>
    </ul>
</div>
</div>

<!-- Permission Modal -->
<div v-if="showCreatePermissionModal || showEditPermissionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
<div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
    <div class="mt-3">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ isEditingPermission ? 'Edit Permission' : 'Create Permission' }}
        </h3>
        <form @submit.prevent="submitPermissionForm">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Permission Name</label>
                <input
                    v-model="permissionForm.name"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="e.g., manage users"
                >
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Guard Name</label>
                <input
                    v-model="permissionForm.guard_name"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="web"
                >
            </div>
            <div class="flex justify-end space-x-3">
                <button
                    type="button"
                    @click="closePermissionModal"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                >
                    {{ isEditingPermission ? 'Update' : 'Create' }}
                </button>
            </div>
        </form>
    </div>
</div>
</div>

<!-- Role Modal -->
<div v-if="showCreateRoleModal || showEditRoleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
<div class="relative top-10 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
    <div class="mt-3">
        <h3 class="text-lg font-medium text

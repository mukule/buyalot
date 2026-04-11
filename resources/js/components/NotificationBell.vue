<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Bell } from 'lucide-vue-next'

interface AppNotification {
    id: string
    type: string | null
    title: string
    body: string
    action_url: string | null
    read_at: string | null
    created_at: string
}

const page = usePage()

// Seed unread count from the Inertia shared prop so the badge shows on first render
const unreadCount = ref<number>((page.props.unread_notifications_count as number) ?? 0)
const notifications = ref<AppNotification[]>([])
const open = ref(false)
const loading = ref(false)

let pollInterval: ReturnType<typeof setInterval> | null = null

async function fetchNotifications() {
    try {
        const res = await fetch(route('notifications.index'), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        if (!res.ok) return
        const data = await res.json()
        notifications.value = data.notifications ?? []
        unreadCount.value = data.unread_count ?? 0
    } catch {
        // Silently ignore network errors — non-critical feature
    }
}

async function markRead(id: string) {
    try {
        await fetch(route('notifications.read', id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        const n = notifications.value.find((x) => x.id === id)
        if (n) n.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch {
        // ignore
    }
}

async function markAllRead() {
    try {
        await fetch(route('notifications.readAll'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        notifications.value.forEach((n) => {
            if (!n.read_at) n.read_at = new Date().toISOString()
        })
        unreadCount.value = 0
    } catch {
        // ignore
    }
}

function handleNotificationClick(n: AppNotification) {
    if (!n.read_at) markRead(n.id)
    open.value = false
    if (n.action_url) router.visit(n.action_url)
}

function toggleOpen() {
    open.value = !open.value
    if (open.value && notifications.value.length === 0) fetchNotifications()
}

function closeOnOutsideClick(e: MouseEvent) {
    const el = document.getElementById('notification-bell-root')
    if (el && !el.contains(e.target as Node)) open.value = false
}

function timeAgo(iso: string): string {
    const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 1000)
    if (diff < 60) return 'just now'
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
    return `${Math.floor(diff / 86400)}d ago`
}

const hasUnread = computed(() => unreadCount.value > 0)
const badgeLabel = computed(() => (unreadCount.value > 99 ? '99+' : String(unreadCount.value)))

onMounted(() => {
    fetchNotifications()
    // Poll every 30 seconds for new notifications
    pollInterval = setInterval(fetchNotifications, 30_000)
    document.addEventListener('click', closeOnOutsideClick)
})

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval)
    document.removeEventListener('click', closeOnOutsideClick)
})
</script>

<template>
    <div id="notification-bell-root" class="relative">
        <!-- Bell button -->
        <button
            type="button"
            @click.stop="toggleOpen"
            class="relative flex h-9 w-9 items-center justify-center rounded-md text-gray-500 hover:bg-accent hover:text-gray-700 focus:outline-none"
            aria-label="Notifications"
        >
            <Bell class="h-5 w-5" />
            <span
                v-if="hasUnread"
                class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-0.5 text-[10px] font-bold leading-none text-white"
            >
                {{ badgeLabel }}
            </span>
        </button>

        <!-- Dropdown -->
        <transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="open"
                class="absolute right-0 z-50 mt-2 w-80 origin-top-right rounded-lg border border-gray-200 bg-white shadow-lg"
                @click.stop
            >
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <span class="text-sm font-semibold text-gray-800">Notifications</span>
                    <button
                        v-if="hasUnread"
                        type="button"
                        @click="markAllRead"
                        class="text-xs text-blue-600 hover:underline"
                    >
                        Mark all read
                    </button>
                </div>

                <!-- List -->
                <ul class="max-h-96 overflow-y-auto divide-y divide-gray-50">
                    <li v-if="notifications.length === 0" class="px-4 py-8 text-center text-sm text-gray-500">
                        No notifications yet
                    </li>
                    <li
                        v-for="n in notifications"
                        :key="n.id"
                        class="flex cursor-pointer gap-3 px-4 py-3 transition-colors hover:bg-gray-50"
                        :class="{ 'bg-blue-50/60': !n.read_at }"
                        @click="handleNotificationClick(n)"
                    >
                        <!-- Unread dot -->
                        <span class="mt-1.5 flex-shrink-0">
                            <span
                                class="block h-2 w-2 rounded-full"
                                :class="n.read_at ? 'bg-transparent' : 'bg-blue-500'"
                            ></span>
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-gray-800 leading-snug">{{ n.title }}</p>
                            <p class="mt-0.5 text-xs text-gray-600 leading-snug line-clamp-2">{{ n.body }}</p>
                            <p class="mt-1 text-[11px] text-gray-400">{{ timeAgo(n.created_at) }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </transition>
    </div>
</template>

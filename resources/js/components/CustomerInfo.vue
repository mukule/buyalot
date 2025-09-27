<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { Customer } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    user?: Customer;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();
const showAvatar = computed(() => props.user?.avatar && props.user.avatar !== '');
const page = usePage();
const roles = computed(() => page.props.auth?.roles ?? []);
const userRole = computed(() => roles.value[0] ?? 'Guest');
</script>

<template>
    <div class="flex items-center space-x-3">
        <!-- Avatar -->
        <Avatar class="bg-light h-8 w-8 overflow-hidden rounded-lg">
            <AvatarImage
                v-if="props.user && showAvatar"
                :src="props.user.avatar!"
                :alt="props.user.name"
            />
            <AvatarFallback class="rounded-lg bg-primary text-white">
                {{ props.user ? getInitials(props.user.name) : '?' }}
            </AvatarFallback>
        </Avatar>

        <!-- User info -->
        <div class="grid flex-1 text-left text-sm leading-tight">
      <span v-if="props.user" class="truncate font-medium">
        {{ props.user.name }} – {{ userRole }}
      </span>
            <span v-else class="truncate font-medium text-gray-500">
        Guest – {{ userRole }}
      </span>

            <span
                v-if="props.showEmail && props.user"
                class="truncate text-xs text-muted-foreground"
            >
        {{ props.user.email }}
      </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '../api/pos';
import { clearSettingsCache } from '../api/settingsCache';
import { LoaderCircle, Delete } from 'lucide-vue-next';
import logo from '@/assets/images/logo.png';

const router = useRouter();
const username = ref('');
const pin = ref('');
const loading = ref(false);
const error = ref('');

const onUsernameInput = (e: Event) => {
  const target = e.target as HTMLInputElement | null;
  if (target) username.value = target.value.toLowerCase();
};

const appendPin = (digit: number) => {
  if (pin.value.length < 4) pin.value += digit.toString();
};

const clearPin = () => {
  pin.value = '';
};

const deleteLastPin = () => {
  pin.value = pin.value.slice(0, -1);
};

const submit = async () => {
  error.value = '';
  if (!username.value.trim()) {
    error.value = 'Please enter your email or phone.';
    return;
  }
  if (pin.value.length !== 4) {
    error.value = 'Please enter your 4-digit PIN.';
    return;
  }

  loading.value = true;
  clearSettingsCache();
  try {
    const res = await login({ username: username.value.trim(), pin: pin.value });

    if (res.terminal) {
      router.push({ name: 'terminal' });
    } else {
      router.push({ name: 'registers' });
    }
  } catch (err: any) {
    const msg = err.response?.data?.message || err.response?.data?.errors;
    error.value = typeof msg === 'string' ? msg : msg?.username?.[0] || msg?.pin?.[0] || 'Login failed.';
    pin.value = '';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  const token = localStorage.getItem('pos_token');
  if (token) {
    router.replace({ name: 'registers' });
  }
});
</script>

<template>
  <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-primary p-6 md:p-10">
    <div class="w-full max-w-md rounded-xl bg-background px-6 py-8 shadow-md sm:px-8 sm:py-10">
      <div class="flex flex-col gap-8">
        <div class="flex flex-col items-center gap-4">
          <a href="/" class="flex flex-col items-center gap-2 font-medium">
            <img :src="logo" alt="POS" class="mb-1 h-10 w-auto" />
            <span class="sr-only">POS Login</span>
          </a>
          <div class="space-y-2 text-center">
            <h1 class="text-xl font-semibold text-foreground">POS Terminal Login</h1>
            <p class="text-center text-sm text-muted-foreground">
              Enter your email or phone and 4-digit PIN to access the terminal
            </p>
          </div>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-4">
          <div class="grid gap-2">
            <Label for="username">Email or Phone</Label>
            <Input
              id="username"
              type="text"
              required
              autofocus
              v-model="username"
              @input="onUsernameInput"
              placeholder="email@example.com or 254712345678"
            />
          </div>

          <div class="grid gap-2">
            <Label for="pin">4-Digit PIN</Label>
            <div class="flex justify-between gap-2 mb-2">
              <div
                v-for="i in 4"
                :key="i"
                class="w-12 h-14 border-2 rounded-lg flex items-center justify-center text-2xl font-bold"
                :class="pin.length >= i ? 'border-primary bg-primary/10' : 'border-gray-200'"
              >
                {{ pin[i - 1] ? '●' : '' }}
              </div>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <Button v-for="i in 9" :key="i" type="button" variant="outline" class="h-14 text-xl font-bold" @click="appendPin(i)">
              {{ i }}
            </Button>
            <Button type="button" variant="outline" class="h-14 text-xl font-bold" @click="clearPin">C</Button>
            <Button type="button" variant="outline" class="h-14 text-xl font-bold" @click="appendPin(0)">0</Button>
            <Button type="button" variant="outline" class="h-14 text-xl font-bold" @click="deleteLastPin">
              <Delete class="h-6 w-6" />
            </Button>
          </div>

          <p v-if="error" class="text-sm font-medium text-red-600">{{ error }}</p>

          <Button type="submit" class="mt-4 w-full" :disabled="loading || pin.length !== 4">
            <LoaderCircle v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
            Access POS
          </Button>
        </form>
      </div>
    </div>
  </div>
</template>

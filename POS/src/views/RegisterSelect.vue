<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getSession, getRegisters, openSession } from '../api/pos';

const router = useRouter();
const registers = ref<any[]>([]);
const loading = ref(true);
const openingBalance = ref(0);
const selectedRegister = ref<any>(null);
const showOpenModal = ref(false);
const submitting = ref(false);
const error = ref('');

const loadData = async () => {
  loading.value = true;
  error.value = '';
  try {
    const [sessionRes, registersRes] = await Promise.all([
      getSession(),
      getRegisters(),
    ]);

    if (sessionRes.session) {
      router.replace({ name: 'terminal' });
      return;
    }

    registers.value = registersRes.registers || [];
  } catch (err: any) {
    if (err.response?.status === 401) {
      router.replace({ name: 'login' });
      return;
    }
    error.value = err.response?.data?.message || 'Failed to load data.';
  } finally {
    loading.value = false;
  }
};

const openModal = (reg: any) => {
  selectedRegister.value = reg;
  openingBalance.value = 0;
  showOpenModal.value = true;
};

const submitOpenSession = async () => {
  if (!selectedRegister.value) return;
  submitting.value = true;
  error.value = '';
  try {
    await openSession(selectedRegister.value.id, openingBalance.value);
    showOpenModal.value = false;
    selectedRegister.value = null;
    router.push({ name: 'terminal' });
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to open session.';
  } finally {
    submitting.value = false;
  }
};

const logout = async () => {
  const { logout } = await import('../api/pos');
  await logout();
  router.replace({ name: 'login' });
};

onMounted(loadData);
</script>

<template>
  <div class="flex min-h-svh flex-col items-center justify-center bg-gray-100 p-6">
    <div class="w-full max-w-2xl">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Select Terminal</h1>
        <Button variant="outline" @click="logout">Logout</Button>
      </div>

      <p v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</p>

      <div v-if="loading" class="py-20 text-center text-muted-foreground">Loading terminals...</div>

      <div v-else class="grid gap-4 md:grid-cols-2">
        <Card
          v-for="reg in registers"
          :key="reg.id"
          class="flex flex-col transition-shadow hover:shadow-md"
        >
          <CardHeader>
            <CardTitle>{{ reg.name }}</CardTitle>
            <CardDescription>
              {{ reg.warehouse?.name || 'No Warehouse' }}
            </CardDescription>
          </CardHeader>
          <CardContent class="flex-grow">
            <div v-if="reg.active_session" class="space-y-2">
              <p class="text-sm font-medium text-green-600">In Use</p>
              <p class="text-xs text-muted-foreground">By: {{ reg.active_session?.user?.name }}</p>
            </div>
            <div v-else>
              <p class="text-sm text-muted-foreground">Available</p>
            </div>
          </CardContent>
          <CardFooter>
            <Button
              v-if="!reg.active_session"
              class="w-full"
              @click="openModal(reg)"
            >
              Open Terminal
            </Button>
            <Button v-else disabled variant="outline" class="w-full">
              In Use
            </Button>
          </CardFooter>
        </Card>
      </div>

      <div v-if="!loading && registers.length === 0" class="rounded-lg border bg-white p-10 text-center">
        <p class="text-muted-foreground">No terminals configured. Contact your administrator.</p>
      </div>
    </div>

    <!-- Open Session Modal -->
    <div
      v-if="showOpenModal && selectedRegister"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <Card class="w-full max-w-md bg-white">
        <CardHeader>
          <CardTitle>Open Terminal: {{ selectedRegister.name }}</CardTitle>
          <CardDescription>Enter the opening cash balance for this shift.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <Label for="opening_balance">Opening Balance</Label>
            <Input
              id="opening_balance"
              v-model="openingBalance"
              type="number"
              step="0.01"
              min="0"
            />
          </div>
        </CardContent>
        <CardFooter class="flex justify-end gap-2">
          <Button variant="outline" @click="showOpenModal = false">Cancel</Button>
          <Button @click="submitOpenSession" :disabled="submitting">
            {{ submitting ? 'Opening...' : 'Open Session' }}
          </Button>
        </CardFooter>
      </Card>
    </div>
  </div>
</template>

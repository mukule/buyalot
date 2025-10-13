<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface ResultItem {
  id: number;
  hashid: string;
  product_slug: string;
  name: string;
  brand?: string | null;
  primary_image_url?: string | null;
  regular_price?: number | null;
  selling_price?: number | null;
  discount?: number | null;
}

interface PageProps {
  q: string;
  results: {
    data: ResultItem[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  }
}

const page = usePage<PageProps>();
const q = computed(() => (page.props as any).q || '');
const results = computed(() => (page.props as any).results || { data: [], total: 0, per_page: 20, current_page: 1, last_page: 1 });

function goToPage(p: number) {
  const search = new URLSearchParams({ q: q.value, page: String(p) }).toString();
  router.get(`/search?${search}`, {}, { preserveScroll: true, preserveState: true });
}
</script>

<template>
  <MainLayout>
    <section class="container mx-auto px-4 py-6">
      <h1 class="mb-4 text-2xl font-semibold">Search results for "{{ q }}"</h1>
      <p v-if="results.total === 0" class="text-gray-600">No products found. Try different keywords.</p>

      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5" v-if="results.total > 0">
        <Link v-for="item in results.data" :key="item.hashid" :href="`/products/${item.product_slug}`" class="rounded border bg-white p-3 hover:shadow">
          <img :src="item.primary_image_url || '/fallback-image.png'" class="h-36 w-full object-contain" :alt="item.name" />
          <div class="mt-2 line-clamp-2 text-sm text-gray-800">{{ item.name }}</div>
          <div class="mt-1 text-xs text-gray-500" v-if="item.brand">{{ item.brand }}</div>
          <div class="mt-2 text-sm font-semibold text-primary">
            <span v-if="item.selling_price != null">R {{ Number(item.selling_price).toFixed(2) }}</span>
            <span v-else-if="item.regular_price != null">R {{ Number(item.regular_price).toFixed(2) }}</span>
          </div>
        </Link>
      </div>

      <!-- Pagination -->
      <div v-if="results.last_page > 1" class="mt-6 flex items-center justify-center gap-2">
        <button class="rounded border px-3 py-1 text-sm" :disabled="results.current_page <= 1" @click="goToPage(results.current_page - 1)">Prev</button>
        <span class="text-sm text-gray-700">Page {{ results.current_page }} of {{ results.last_page }}</span>
        <button class="rounded border px-3 py-1 text-sm" :disabled="results.current_page >= results.last_page" @click="goToPage(results.current_page + 1)">Next</button>
      </div>
    </section>
  </MainLayout>
</template>

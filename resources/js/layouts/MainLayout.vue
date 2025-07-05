<template>
  <div class="flex min-h-screen flex-col">
    <Navbar />

    <main class="flex-grow pt-14 text-primary-foreground">
      <FlashMessage />

      
      <slot name="full" />

      
      <div class="container mx-auto px-4 sm:px-6">
        <slot />
      </div>
    </main>

    <Footer />
  </div>
</template>

<script setup lang="ts">
import FlashMessage from '../components/FlashMessage.vue';
import Footer from '../components/Footer.vue';
import Navbar from '../components/Navbar.vue';
</script>

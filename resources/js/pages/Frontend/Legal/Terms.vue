<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
</script>

<template>
  <MainLayout>
    <section class="mx-auto p-4" style="max-width: 1000px">
      <div class="rounded-lg bg-white p-6 shadow">
        <div class="mb-4 flex items-center justify-between">
          <h1 class="text-2xl font-bold text-gray-900">Terms &amp; Conditions</h1>
          <div class="flex gap-3 text-sm">
            <a :href="route('checkout.summary')" class="text-primary hover:text-primary/80 underline">&larr; Back to Checkout</a>
            <a :href="route('home')" class="text-primary hover:text-primary/80 underline">&larr; Back to Home</a>
          </div>
        </div>
        <p class="mb-6 text-sm text-gray-600">Last updated: {{ new Date().toLocaleDateString() }}</p>

        <div class="prose max-w-none prose-headings:font-semibold prose-p:text-gray-700">
          <h2>1. Introduction</h2>
          <p>
            Welcome to Bianlina. These Terms &amp; Conditions ("Terms") govern your access to and use of our website,
            products, and services. By using our platform, you agree to be bound by these Terms.
          </p>

          <h2>2. Accounts</h2>
          <p>
            You are responsible for maintaining the confidentiality of your account credentials and for all activities
            that occur under your account. You must notify us immediately of any unauthorized use of your account.
          </p>

          <h2>3. Orders and Payments</h2>
          <p>
            All orders are subject to availability and acceptance. Prices and availability of products are subject to
            change without notice. Payments may be processed via supported providers such as M-Pesa.
          </p>

          <h2>4. Shipping and Delivery</h2>
          <p>
            Shipping fees and delivery estimates may vary based on distance, item characteristics, and promotions. Free
            shipping may apply when valid coupons or discounts are used.
          </p>

          <h2>5. Returns and Refunds</h2>
          <p>
            Return and refund eligibility depends on product type and condition. Please contact support for assistance.
          </p>

          <h2>6. Coupons and Discounts</h2>
          <p>
            Coupons are subject to validation, usage limits, and expiration. We reserve the right to modify or cancel
            promotions at any time.
          </p>

          <h2>7. Prohibited Activities</h2>
          <p>
            You agree not to misuse the platform, including but not limited to attempting unauthorized access, engaging
            in fraudulent activity, or violating any applicable laws.
          </p>

          <h2>8. Limitation of Liability</h2>
          <p>
            To the fullest extent permitted by law, Bianlina is not liable for any indirect, incidental, or consequential
            damages arising from your use of the platform.
          </p>

          <h2>9. Changes to These Terms</h2>
          <p>
            We may update these Terms from time to time. Continued use of the platform after changes constitutes
            acceptance of the updated Terms.
          </p>

          <h2>10. Contact Us</h2>
          <p>
            If you have any questions about these Terms, please contact our support team.
          </p>
        </div>
      </div>
    </section>
  </MainLayout>
</template>

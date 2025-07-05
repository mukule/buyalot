<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import sell_on_buyalot from '@/assets/images/banners/sell-on-buyalot.png';
import start_selling_bg from '@/assets/images/bg/start-selling.jpg';
import application from '@/assets/images/icons/application.svg';
import growth from '@/assets/images/icons/growth.svg';
import approval from '@/assets/images/icons/approval.svg';
import sales from '@/assets/images/icons/sales.svg';
import registration from '@/assets/images/icons/registration.svg';
import onboarding from '@/assets/images/icons/onboarding.svg';



const cards = [
  {
    title: 'Optimise Your Growth',
    description:
      'Easily boost your sales by leveraging our active customer base of over 3 million happy online shoppers.',
    image:
      'https://www.takealot.com/36c5e68ec7d3ea31fd9999e79398490f69e476ec/static/media/src/images/sell-on-takealot/cards/optimise-your-growth.jpg-758bca1bd6b74237d09e.jpg',
  },
  {
    title: 'End–to–End Solutions',
    description:
      "We'll provide the tools you need to set up and sell – manage your stock, pricing, product selection and more from the Takealot Seller Portal.",
    image:
      'https://www.takealot.com/36c5e68ec7d3ea31fd9999e79398490f69e476ec/static/media/src/images/sell-on-takealot/cards/end-to-end-solutions.jpg-73b9874edfdf9aeaa3b5.jpg',
  },
  {
    title: 'Hassle–Free Logistics',
    description:
      "From handling warehousing to delivery and returns, we've made online retail easier than ever.",
    image:
      'https://www.takealot.com/36c5e68ec7d3ea31fd9999e79398490f69e476ec/static/media/src/images/sell-on-takealot/cards/hassle-free-logistics.jpg-dfeefa2f54d08b8ca3ce.jpg',
  },
  {
    title: 'Safe & Secure Payments',
    description:
      'Payments are made directly to you four times per month.',
    image:
      'https://www.takealot.com/36c5e68ec7d3ea31fd9999e79398490f69e476ec/static/media/src/images/sell-on-takealot/cards/safe-and-secure-payments.jpg-ed8aa4f3e9d11eefe1dc.jpg',
  },
];


const steps = [
  {
    title: 'Application',
    description: 'Apply now and tell us about your business and products.',
    icon: application,
  },
  {
    title: 'Approval',
    description: "We'll review your application and get in touch within 10 business days.",
    icon: approval,
  },
  {
    title: 'Registration',
    description: 'Complete your Takealot Seller account by supplying all the required information and paperwork.',
    icon: registration,
  },
  {
    title: 'Onboarding',
    description: 'Learn all about our processes and choose your stock model.',
    icon: onboarding,
  },
  {
    title: 'Sales',
    description: 'Get your products live and start selling.',
    icon: sales,
  },
  {
    title: 'Growth',
    description: 'Boost your online sales via promotions, analyse your performance using reports and so much more.',
    icon: growth,
  },
];



</script>

<template>
  <MainLayout>
    <template #full>
      <section
        class="relative h-[400px] w-full bg-cover bg-center"
        :style="{ backgroundImage: `url(${sell_on_buyalot})` }"
      >
        <div class="absolute inset-0 bg-black/10"></div>

        <div class="relative h-full container mx-auto px-4 sm:px-6 lg:px-8">
          <div class="h-full flex items-center justify-center md:justify-start">
            <div
              class="bg-primary/70 backdrop-blur-sm rounded-lg p-6 md:p-8 lg:p-10 w-full md:max-w-2xl text-center md:text-left"
            >
              <div class="text-white space-y-4 md:space-y-6">
                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold leading-tight tracking-tight">
                  Join KENYA's Best Online <br class="hidden sm:block" />
                  Marketplace Platform
                </h1>
                <p class="text-lg md:text-xl leading-relaxed">
                  Sell to over 3 million happy shoppers
                </p>
                <div class="flex flex-wrap justify-center md:justify-start gap-4">
                  <button
                    class="bg-white text-primary font-semibold px-6 py-3 text-sm md:text-base md:px-8 md:py-3 rounded-md shadow-md hover:secondary transition transform hover:scale-105"
                  >
                    Apply to Sell
                  </button>
                  <button
                    class="border-2 border-white text-white font-semibold px-6 py-3 text-sm md:text-base md:px-8 md:py-3 rounded-md hover:bg-white hover:text-primary transition transform hover:scale-105"
                  >
                    See Pricing
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
   

   
    <section class="container px-4 mx-auto my-12 px-0">
  <div class="max-w-5xl mx-auto text-center mb-12 space-y-6">
    <h4 class="text-2xl md:text-3xl lg:text-3xl font-bold text-gray-900 leading-snug tracking-tight">
      Get the tools you need to increase sales and grow your business online
    </h4>
    <p class="text-lg md:text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
      Selling your products online has never been easier. Simply apply to sell as a Takealot seller today and easily reach online shoppers across South Africa.
    </p>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
    <div
      v-for="card in cards"
      :key="card.title"
      class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden h-full text-center"
    >
      <img
        :src="card.image"
        :alt="card.title"
        class="w-full h-48 object-cover"
        loading="lazy"
      />
      <div class="p-6 space-y-3">
        <h3 class="text-xl font-semibold text-primary">{{ card.title }}</h3>
        <p class="text-base text-gray-600 leading-relaxed">
          {{ card.description }}
        </p>
      </div>
    </div>
  </div>

  <div class="mt-8 flex justify-center">
    <button
      class="bg-primary text-white font-semibold px-8 py-3 rounded-md shadow-md hover:bg-secondary transition"
    >
      Apply to Sell
    </button>
  </div>
</section>
    

<section
  class="relative bg-cover bg-center w-full py-14 text-white"
  :style="{ backgroundImage: `url(${start_selling_bg})` }"
>
 
  <div class="absolute inset-0 bg-primary/90"></div>

  <div class="relative container mx-auto px-4 sm:px-6 lg:px-8">
    <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-center mb-12">
      Start selling online in just a few easy steps
    </h2>

    
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
      <div
        v-for="step in steps"
        :key="step.title"
        class="text-center"
      >
        <img :src="step.icon" :alt="step.title" class="mx-auto mb-4 w-16 h-16" />
        <h3 class="text-lg font-semibold mb-2">{{ step.title }}</h3>
        <p class="text-sm leading-relaxed">{{ step.description }}</p>
      </div>
    </div>
  </div>
</section>

<section class="container mx-auto my-20 px-4 sm:px-6 lg:px-8">
  <div class="text-center mb-12 space-y-4">
    <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900">
      Pricing
    </h2>
    <p class="text-gray-600 text-lg">
      We charge a monthly subscription fee of <strong>***</strong> per seller account/month. You can choose to cancel your account at any time.
    </p>
  </div>

  <div>
    <details class="group border border-gray-200 rounded-md overflow-hidden">
      <summary class="cursor-pointer px-6 py-4 bg-gray-50 hover:bg-gray-100 font-semibold text-gray-800">
        Success Fees
      </summary>
      <div class="px-6 py-4 text-gray-600">
        To be updated.
      </div>
    </details>

    <details class="group border border-gray-200 rounded-md overflow-hidden mt-4">
      <summary class="cursor-pointer px-6 py-4 bg-gray-50 hover:bg-gray-100 font-semibold text-gray-800">
        Fulfilment Fees
      </summary>
      <div class="px-6 py-4 text-gray-600">
        To be updated.
      </div>
    </details>

    <details class="group border border-gray-200 rounded-md overflow-hidden mt-4">
      <summary class="cursor-pointer px-6 py-4 bg-gray-50 hover:bg-gray-100 font-semibold text-gray-800">
        Storage Fees
      </summary>
      <div class="px-6 py-4 text-gray-600">
        To be updated.
      </div>
    </details>
  </div>
</section>


     
    </template>
  </MainLayout>
</template>
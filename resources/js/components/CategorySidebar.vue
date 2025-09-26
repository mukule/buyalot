<script setup lang="ts">
interface Subcategory {
    id: number;
    name: string;
}

interface Category {
    id: number;
    name: string;
    subcategories?: Subcategory[];
}

interface Props {
    categories: Category[];
}

const props = defineProps<Props>();
</script>

<style scoped>
.category-item {
    position: relative;
    padding: 0.5rem 1rem;
    cursor: pointer;
    user-select: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.category-item:hover {
    background-color: #f3f4f6;
}

.submenu {
    display: none;
    position: absolute;
    top: 0;
    left: 100%;
    min-width: 200px;
    background-color: white;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 6px rgb(0 0 0 / 0.1);
    padding: 0.5rem 1rem;
    z-index: 10;
}

.category-item:hover > .submenu {
    display: block;
}

.angle-right {
    margin-left: auto;
    border: solid currentColor;
    border-width: 0 2px 2px 0;
    display: inline-block;
    padding: 3px;
    transform: rotate(-45deg);
    -webkit-transform: rotate(-45deg);
}
</style>

<template>
    <aside class="w-full rounded-lg bg-white shadow-md lg:w-[20.83%]">
        <ul class="space-y-1">
            <li v-for="category in props.categories" :key="category.id" class="category-item">
                <a href="#" class="flex-1 text-sm text-gray-700 transition hover:text-primary">
                    {{ category.name }}
                </a>
                <span v-if="category.subcategories?.length" class="angle-right"></span>

                <ul v-if="category.subcategories?.length" class="submenu">
                    <li v-for="subcat in category.subcategories" :key="subcat.id" class="py-1">
                        <a href="#" class="text-sm text-gray-600 hover:text-primary">
                            {{ subcat.name }}
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </aside>
</template>

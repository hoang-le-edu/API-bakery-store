<template>
    <div
        v-if="isVisible"
        class="overlay fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center"
        @click="close"
    >
        <div
            class="bg-white rounded-lg shadow-lg w-[80%] lg:w-[25%] p-6 space-y-6 relative"
            @click.stop
        >
            <!-- Header -->
            <div class="flex items-center justify-between pb-4">
                <p
                    @click="close"
                    class="text-gray-600 text-sm font-bold cursor-pointer hover:text-gray-800"
                >
                    Cancel
                </p>
                <h2 class="text-black text-2xl font-semibold">Filter Products</h2>
                <p
                    @click="clearAll"
                    class="text-red-500 text-sm font-bold cursor-pointer hover:text-red-700"
                >
                    Clear All
                </p>
            </div>

            <!-- Price Range -->
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Price Range</label>
                <div class="flex flex-col gap-2">
                    <input
                        type="number"
                        v-model="filterSearch.minPrice"
                        placeholder="Min Price"
                        class="w-full border border-gray-100 rounded p-3 text-sm focus:ring-2 focus:ring-[#6B4226]"
                    />
                    <input
                        type="number"
                        v-model="filterSearch.maxPrice"
                        placeholder="Max Price"
                        class="w-full border border-gray-100 rounded p-3 text-sm focus:ring-2 focus:ring-[#6B4226]"
                    />
                </div>
            </div>

            <!-- Date Range -->
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Date Range</label>
                <div class="flex flex-col gap-2">
                    <input
                        type="date"
                        v-model="filterSearch.fromDate"
                        class="w-full border border-gray-100 rounded p-3 text-sm focus:ring-2 focus:ring-[#6B4226]"
                        placeholder="From Date"
                    />
                    <input
                        type="date"
                        v-model="filterSearch.toDate"
                        class="w-full border border-gray-100 rounded p-3 text-sm focus:ring-2 focus:ring-[#6B4226]"
                        placeholder="To Date"
                    />
                </div>
            </div>

            <!-- Tag Filter -->
            <div>
                <label class="block text-gray-700 font-semibold mb-2">Tags</label>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="(tag, index) in listTag"
                        :key="tag.id"
                        @click="toggleTag(tag.id)"
                        :class="[
              'px-4 py-2 text-sm rounded-full border transition-all',
              filterSearch.tag.includes(tag.id)
                ? 'bg-[#6B4226] text-white border-transparent'
                : 'bg-gray-200 text-gray-700 border-gray-100 hover:bg-gray-300',
            ]"
                    >
                        {{ tag.name }}
                    </button>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="flex justify-end">
                <button
                    @click="applyFilters"
                    class="w-full sm:w-auto justify-center text-white inline-flex btn-primary-color focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    Apply
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref} from 'vue';
import {defineProps, defineEmits} from 'vue';

const listTag = [
    {id: 't01', name: 'Summer'},
    {id: 't02', name: 'Winter'},
    {id: 't03', name: 'Hot'},
    {id: 't04', name: 'Cold'},
    {id: 't05', name: 'Sweet'},
    {id: 't06', name: 'Sour'},
    {id: 't07', name: 'Cool'},
];

const props = defineProps({
    isVisible: {type: Boolean, required: true}
});

const emit = defineEmits();
const close = () => {
    emit('closePopup');
};

const filterSearch = ref({
    tag: [],
    minPrice: '',
    maxPrice: '',
    fromDate: '',
    toDate: '',
});

// Toggle tag selection
const toggleTag = (id) => {
    const index = filterSearch.value.tag.indexOf(id);
    if (index > -1) {
        filterSearch.value.tag.splice(index, 1); // Remove from 'tag'
    } else {
        filterSearch.value.tag.push(id); // Add to 'tag'
    }
};

// Clear all filters
const clearAll = () => {
    filterSearch.value = {
        tag: [],
        minPrice: '',
        maxPrice: '',
        fromDate: '',
        toDate: '',
    };
};

// Apply filters
const applyFilters = () => {
    emit('applyFilters', filterSearch.value);
    close();
};
</script>

<style scoped>
</style>

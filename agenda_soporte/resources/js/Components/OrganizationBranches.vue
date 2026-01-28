<script setup>
defineProps({
    organizationMap: Object,
    stats: Object
});
</script>

<template>
    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Mi Mapa Organizacional</h3>
        
        <div v-for="(regions, company) in organizationMap" :key="company" class="mb-8">
            <div class="flex items-center mb-4">
                <div class="h-px flex-1 bg-gray-200"></div>
                <span class="px-4 text-sm font-bold text-gray-500 uppercase tracking-wider">{{ company }}</span>
                <div class="h-px flex-1 bg-gray-200"></div>
            </div>

            <div v-for="(branches, region) in regions" :key="region" class="ml-4 mb-6">
                <h4 class="text-md font-semibold text-blue-600 mb-3 flex items-center">
                    <span class="mr-2">📍</span> {{ region }}
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 ml-6">
                    <div v-for="branch in branches" :key="branch.id" 
                         class="border rounded-lg p-4 hover:shadow-md transition-shadow bg-gray-50">
                        <div class="flex justify-between items-start">
                            <span class="font-bold text-gray-800">{{ branch.name }}</span>
                            <span :class="[
                                'px-2 py-0.5 rounded text-xs font-bold uppercase',
                                branch.assignment_type === 'primary' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'
                            ]">
                                {{ branch.assignment_type }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">{{ branch.address }}</p>
                        <div v-if="branch.is_external" class="mt-2 text-[10px] text-purple-600 font-bold italic">
                            ⭐ Soporte Externo
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
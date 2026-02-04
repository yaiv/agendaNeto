<script setup>
const props = defineProps({
    organizationMap: { type: Object, required: true },
    stats: { type: Object, required: true }
});
</script>

<template>
    <div class="space-y-8">
        <div v-for="(regions, companyName) in organizationMap" :key="companyName" class="space-y-4">
            <div class="flex items-center gap-2 border-b pb-2">
                <span class="text-xs font-black uppercase tracking-widest text-gray-400">Compañía:</span>
                <h3 class="text-lg font-bold text-indigo-600">{{ companyName }}</h3>
            </div>

            <div v-for="(branches, regionName) in regions" :key="regionName" class="bg-gray-50 rounded-xl p-5 border border-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="font-bold text-gray-700 flex items-center gap-2">
                        <div class="w-2 h-6 bg-orange-500 rounded-full"></div>
                        Región: {{ regionName }}
                    </h4>
                    <span class="text-xs font-medium text-gray-500 bg-white px-3 py-1 rounded-full shadow-sm">
                        {{ branches.length }} Tiendas asignadas
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="branch in branches" :key="branch.id" 
                        class="bg-white p-4 rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-mono text-gray-400">#{{ branch.id }}</span>
                            <span :class="[
                                'text-[10px] px-2 py-0.5 rounded-full font-bold uppercase',
                                branch.assignment_type === 'primary' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'
                            ]">
                                {{ branch.assignment_type }}
                            </span>
                        </div>
                        <h5 class="font-bold text-gray-800 mt-1 uppercase">{{ branch.name }}</h5>
                        <p class="text-[11px] text-gray-500 truncate mt-1">{{ branch.address }}</p>
                        
                        <div class="mt-3 flex items-center justify-between border-t pt-2">
                            <span v-if="branch.is_external" class="text-[10px] text-purple-600 font-bold italic">Externo</span>
                            <span class="text-[10px] text-gray-400 italic">Asignado: {{ branch.assigned_at }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
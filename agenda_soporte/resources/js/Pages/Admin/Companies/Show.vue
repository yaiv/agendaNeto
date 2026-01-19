<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SectionTitle from '@/Components/SectionTitle.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    company: Object,
});
</script>

<template>
    <AppLayout :title="company.name">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gestionar Estructura: {{ company.name }}
                </h2>
                <Link :href="route('admin.companies.index')" class="text-sm text-gray-600 hover:text-gray-900">
                    ← Volver al Directorio
                </Link>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:px-6 flex justify-between items-center bg-gray-50 border-b border-gray-200">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Detalles Corporativos</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Configuración general y propiedad.</p>
                    </div>
                    <div class="flex items-center">
                         <img class="h-8 w-8 rounded-full object-cover mr-2" :src="company.owner.profile_photo_url" :alt="company.owner.name" />
                         <span class="text-sm font-bold text-gray-700">{{ company.owner.name }}</span>
                         <span class="text-xs text-gray-500 ml-1">(Coordinador)</span>
                    </div>
                </div>
            </div>

            <SectionTitle class="mb-4">
                <template #title>Regiones Operativas</template>
                <template #description>
                    Estas son las divisiones territoriales de la compañía. Cada región contiene sus propias sucursales.
                </template>
            </SectionTitle>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <div v-for="region in company.regions" :key="region.id" class="bg-white overflow-hidden shadow rounded-lg border border-gray-100 hover:shadow-md transition">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900 truncate">
                                {{ region.name }}
                            </h3>
                            <span v-if="region.code" class="px-2 py-1 text-xs font-bold bg-gray-100 text-gray-600 rounded">
                                {{ region.code }}
                            </span>
                        </div>
                        
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            {{ region.branches_count }} Sucursales registradas
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-4 sm:px-6 border-t border-gray-100 flex justify-between items-center">
                        <div class="text-xs text-gray-500">
                            ID: {{ region.id }}
                        </div>
                        <button class="text-indigo-600 hover:text-indigo-900 text-sm font-bold">
                            Ver Sucursales →
                        </button>
                    </div>
                </div>

                <div class="bg-gray-50 overflow-hidden shadow rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center p-6 hover:border-indigo-500 hover:bg-indigo-50 transition cursor-pointer group">
                    <div class="text-center">
                        <svg class="mx-auto h-8 w-8 text-gray-400 group-hover:text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span class="mt-2 block text-sm font-medium text-gray-900 group-hover:text-indigo-600">
                            Añadir otra región
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
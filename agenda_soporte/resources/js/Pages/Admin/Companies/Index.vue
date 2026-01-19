<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    companies: Array,
});

// Función simple para formatear fechas
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('es-MX', { 
        year: 'numeric', month: 'long', day: 'numeric' 
    });
};
</script>

<template>
    <Head title="Gestión de Compañías" />

    <AppLayout title="Directorio Corporativo">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Compañías y Estructura
                </h2>
                <Link :href="route('admin.companies.create')">
                    <PrimaryButton>
                        + Nueva Compañía
                    </PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="companies.length === 0" class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-10 text-center text-gray-500">
                    <p class="text-lg">No hay compañías corporativas registradas.</p>
                    <p class="text-sm mt-2">Comienza creando la primera para definir la estructura.</p>
                </div>

                <div v-else class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Compañía
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estructura (Regiones)
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Coordinador (Owner)
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Gestionar</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="company in companies" :key="company.id" class="hover:bg-gray-50 transition">
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-indigo-100 text-indigo-700 rounded-full font-bold">
                                                {{ company.name.charAt(0) }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ company.name }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    Registrada el {{ formatDate(company.created_at) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="company.regions_count > 0" class="flex items-center">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ company.regions_count }} Regiones
                                            </span>
                                        </div>
                                        <div v-else class="flex items-center text-red-500 text-xs font-bold">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            Sin Estructura
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 rounded-full overflow-hidden bg-gray-200">
                                                 <img v-if="company.owner.profile_photo_url" :src="company.owner.profile_photo_url" :alt="company.owner.name">
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm text-gray-900">{{ company.owner.name }}</div>
                                                <div class="text-xs text-gray-500">{{ company.owner.email }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span v-if="!company.personal_team" class="text-indigo-600 font-medium">Corporativo</span>
                                        <span v-else class="text-gray-400">Personal</span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link 
                                            :href="route('admin.companies.show', company.id)" 
                                            class="text-indigo-600 hover:text-indigo-900 font-bold"
                                        >
                                            Gestionar Estructura →
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
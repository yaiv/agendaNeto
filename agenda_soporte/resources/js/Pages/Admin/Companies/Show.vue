<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
    company: Object,
});

// Formulario para eliminar
const deleteForm = useForm({});

const confirmDelete = () => {
    if (confirm(`¿Estás SEGURO de eliminar ${props.company.name}? Se borrarán todas sus regiones y sucursales asociadas.`)) {
        deleteForm.delete(route('admin.companies.destroy', props.company.id));
    }
};
</script>

<template>
    <AppLayout :title="`Gestionar ${company.name}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ company.name }}
                </h2>
                <Link :href="route('admin.companies.index')" class="text-sm text-[#00408F] font-bold hover:underline">
                    &larr; Volver al Directorio
                </Link>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 space-y-10">
            
            <div class="bg-white overflow-hidden shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Regiones Operativas</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">División territorial de esta compañía.</p>
                    </div>
                    
                    <Link :href="route('regions.create', { team_id: company.id })">
                        <PrimaryButton>
                            + Agregar Región
                        </PrimaryButton>
                    </Link>
                </div>
                
                <div class="px-4 py-5 sm:p-6">
                    <ul v-if="company.regions.length > 0" class="divide-y divide-gray-200">
                        <li v-for="region in company.regions" :key="region.id" class="py-4 flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="bg-blue-50 p-2 rounded-full mr-3">
                                    <AppIcon name="map" class="h-5 w-5 text-[#00408F]" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ region.name }}</p>
                                    <p class="text-xs text-gray-500">{{ region.branches.length }} Sucursales</p>
                                </div>
                            </div>
                            <Link :href="route('regions.edit', region.id)" class="text-sm text-gray-500 hover:text-[#00408F]">
                                Editar
                            </Link>
                        </li>
                    </ul>
                    <div v-else class="text-center py-6 text-gray-500 text-sm">
                        No hay regiones asignadas a esta compañía.
                    </div>
                </div>
            </div>

 <SectionBorder />

<div v-if="company.id !== 8" class="bg-white shadow sm:rounded-lg border border-red-100 overflow-hidden">
    <div class="px-4 py-5 sm:px-6 bg-red-50 border-b border-red-100">
        <h3 class="text-lg leading-6 font-medium text-red-700">Eliminar Compañía</h3>
        <p class="mt-1 text-sm text-red-500">Acción irreversible.</p>
    </div>
    <div class="px-4 py-5 sm:p-6">
        <p class="text-sm text-gray-600 mb-4">
            Al eliminar esta compañía, se perderá el acceso a todos sus datos históricos, regiones y asignaciones de personal. 
            Asegúrate de haber respaldado la información necesaria.
        </p>
        <DangerButton @click="confirmDelete" :class="{ 'opacity-25': deleteForm.processing }" :disabled="deleteForm.processing">
            Eliminar {{ company.name }} Definitivamente
        </DangerButton>
    </div>
</div>

<div v-else class="bg-gray-50 rounded-lg p-4 border border-gray-200 flex items-center justify-center text-gray-500 text-sm italic">
    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
    </svg>
    Esta compañía es el Núcleo del Sistema y está protegida contra eliminación.
</div>
           

        </div>
    </AppLayout>
</template>
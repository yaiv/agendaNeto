<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue'; // 👈 IMPORTANTE: Importamos la reactividad
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

// Recibimos los datos del Controlador
const props = defineProps({
    teams: Array,          // Lista de equipos disponibles
    all_regions: Array,    // TODAS las regiones (con su team_id)
    is_global_admin: Boolean,
});

const form = useForm({
    // Si solo hay un equipo (Coordinador), lo pre-seleccionamos. Si hay varios (Admin), se deja vacío.
    team_id: props.teams.length === 1 ? props.teams[0].id : '',
    name: '',
    email: '',
    password: '',
    employee_code: '',
    phone1: '',
    primary_region_id: '',
    support_region_ids: [],
});

// 🧠 CEREBRO DEL FILTRO:
// Filtra la lista maestra de regiones basándose en el equipo seleccionado
const availableRegions = computed(() => {
    if (!form.team_id) return []; // Si no hay equipo, lista vacía
    return props.all_regions.filter(region => region.team_id == form.team_id);
});

// 👁️ OBSERVADOR:
// Si cambiamos de equipo, limpiamos las regiones seleccionadas para no mezclar datos
watch(() => form.team_id, () => {
    form.primary_region_id = '';
    form.support_region_ids = [];
});

const submit = () => {
    form.post(route('engineers.store'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AppLayout title="Alta de Ingeniero">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Registrar Nuevo Ingeniero
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        
                        <div v-if="teams.length > 1" class="mb-6 bg-indigo-50 border border-indigo-200 p-4 rounded-md">
                            <InputLabel for="team" value="Asignar a Compañía / Equipo" class="text-indigo-800 font-bold" />
                            <select 
                                id="team" 
                                v-model="form.team_id" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            >
                                <option value="" disabled>-- Seleccione una Compañía --</option>
                                <option v-for="team in teams" :key="team.id" :value="team.id">
                                    {{ team.name }}
                                </option>
                            </select>
                            <p class="text-xs text-indigo-600 mt-1">Selecciona la compañía para cargar sus regiones operativas.</p>
                            <InputError :message="form.errors.team_id" class="mt-2" />
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">1. Credenciales de Usuario</h3>
                        <div class="grid grid-cols-1 gap-4 mb-6">
                            <div>
                                <InputLabel for="name" value="Nombre Completo" />
                                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="email" value="Correo Electrónico" />
                                <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.email" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="password" value="Contraseña Temporal" />
                                <TextInput id="password" v-model="form.password" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.password" class="mt-2" />
                            </div>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">2. Información RRHH</h3>
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <InputLabel for="employee_code" value="No. Empleado / ID" />
                                <TextInput id="employee_code" v-model="form.employee_code" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.employee_code" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="phone1" value="Teléfono Contacto" />
                                <TextInput id="phone1" v-model="form.phone1" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.phone1" class="mt-2" />
                            </div>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">3. Asignación Operativa</h3>
                        <div class="mb-6">
                            
                            <div v-if="!form.team_id" class="p-4 mb-4 text-sm text-yellow-700 bg-yellow-100 rounded-lg" role="alert">
                                <span class="font-medium">Atención:</span> Por favor selecciona una <strong>Compañía</strong> arriba para ver las regiones disponibles.
                            </div>

                            <div v-else class="animate-pulse-once"> <div class="mb-4">
                                    <InputLabel for="primary_region" value="Región Base (Principal)" />
                                    <select id="primary_region" v-model="form.primary_region_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                        <option value="" disabled>Seleccione una región...</option>
                                        <option v-for="region in availableRegions" :key="region.id" :value="region.id">
                                            {{ region.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.primary_region_id" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="support_regions" value="Regiones de Apoyo (Opcional - Mantén Ctrl para seleccionar varias)" />
                                    <select id="support_regions" v-model="form.support_region_ids" multiple class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm h-32">
                                        <option v-for="region in availableRegions" :key="'support-'+region.id" :value="region.id">
                                            {{ region.name }}
                                        </option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Regiones disponibles de la compañía seleccionada.</p>
                                    <InputError :message="form.errors.support_region_ids" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Registrar Ingeniero
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
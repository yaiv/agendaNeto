<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    engineer: Object,
    teams: Array,
    regions: Array,      // Para Coordinador
    all_regions: Array,  // Para Admin
    is_global_admin: Boolean,
});

const form = useForm({
    team_id: props.engineer.current_team_id, // Carga el equipo actual
    name: props.engineer.name,
    email: props.engineer.email,
    employee_code: props.engineer.employee_code,
    phone1: props.engineer.phone1,
    status: props.engineer.status,
    primary_region_id: props.engineer.primary_region_id || '', 
    support_region_ids: props.engineer.support_region_ids || [], 
});

// 🧠 Lógica Dinámica (Igual que en Create)
const availableRegions = computed(() => {
    // Si es Admin Global, filtramos de la lista maestra
    if (props.is_global_admin) {
        if (!form.team_id) return [];
        return props.all_regions.filter(region => region.team_id == form.team_id);
    }
    // Si es Coordinador, usamos la lista directa
    return props.regions;
});

// 👁️ Watcher: Si el Admin cambia el equipo, limpiamos las regiones seleccionadas
// (Solo si el cambio lo hace el usuario, no al cargar)
watch(() => form.team_id, (newValue, oldValue) => {
    if (oldValue !== undefined && newValue !== props.engineer.current_team_id) {
        // Solo limpiamos si estamos CAMBIANDO de equipo activamente
        // Si es la carga inicial, respetamos los valores de la BD
        // Pero espera... si cambio de equipo, las regiones viejas NO son validas.
        // Así que mejor limpiamos siempre que cambie el ID y no coincida con el original.
         // form.primary_region_id = '';
         // form.support_region_ids = [];
         // (Opcional: puedes dejarlo manual para evitar borrados accidentales)
    }
});

const submit = () => {
    form.put(route('engineers.update', props.engineer.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Editar Ingeniero">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editando a: <span class="text-indigo-600">{{ engineer.name }}</span>
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        
                        <div class="mb-6 bg-yellow-50 border border-yellow-200 p-4 rounded-md flex justify-between items-center">
                            <div>
                                <h3 class="text-sm font-medium text-yellow-800">Estado Operativo</h3>
                                <p class="text-xs text-yellow-600">Desactivar para impedir el acceso.</p>
                            </div>
                            <div class="w-1/3">
                                <select v-model="form.status" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="active">🟢 Activo</option>
                                    <option value="inactive">🔴 Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="is_global_admin" class="mb-6 bg-indigo-50 border border-indigo-200 p-4 rounded-md">
                            <InputLabel for="team" value="Compañía Asignada" class="text-indigo-800 font-bold" />
                            <select id="team" v-model="form.team_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option v-for="team in teams" :key="team.id" :value="team.id">
                                    {{ team.name }}
                                </option>
                            </select>
                            <p class="text-xs text-indigo-600 mt-1">
                                ⚠️ Si cambias la compañía, asegúrate de re-asignar las regiones abajo.
                            </p>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">1. Credenciales</h3>
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
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">2. Información RRHH</h3>
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <InputLabel for="code" value="ID Empleado" />
                                <TextInput id="code" v-model="form.employee_code" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.employee_code" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="phone" value="Teléfono" />
                                <TextInput id="phone" v-model="form.phone1" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.phone1" class="mt-2" />
                            </div>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">3. Zona de Operación</h3>
                        <div class="mb-6">
                            
                            <div v-if="availableRegions.length === 0" class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
                                <span class="font-bold">Sin Regiones:</span> La compañía seleccionada no tiene regiones operativas. Por favor cambia la compañía arriba (ej. a "CENTRO").
                            </div>

                            <div v-else>
                                <div class="mb-4">
                                    <InputLabel for="primary" value="Región Base (Principal)" />
                                    <select id="primary" v-model="form.primary_region_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="" disabled>Seleccione una región...</option>
                                        <option v-for="region in availableRegions" :key="region.id" :value="region.id">
                                            {{ region.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.primary_region_id" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="support" value="Regiones de Apoyo (Ctrl+Click)" />
                                    <select id="support" v-model="form.support_region_ids" multiple class="mt-1 block w-full border-gray-300 rounded-md shadow-sm h-32">
                                        <option v-for="region in availableRegions" :key="'sup-'+region.id" :value="region.id">
                                            {{ region.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Guardar Cambios
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
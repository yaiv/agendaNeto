<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    teams: { type: Array, default: () => [] },
    all_regions: { type: Array, default: () => [] },  // Todas las regiones (con team_id)
    all_branches: { type: Array, default: () => [] },   // 👈 NUEVO: Todas las tiendas disponibles para filtrar
    is_global_admin: Boolean,
});

const form = useForm({
    team_id: props.teams.length === 1 ? props.teams[0].id : '',
    name: '',
    email: '',
    password: '',
    employee_code: '',
    phone1: '',
    primary_region_id: '',
    support_region_ids: [],
    selected_branch_ids: [], // 👈 NUEVO: IDs de la tabla engineer_branch
});


const filteredTeams = computed(() => {
    // Solo mostramos equipos que NO sean personales (Compañías reales)
    return props.teams.filter(team => !team.personal_team);
});
// 🧠 FILTRO DE REGIONES: Basado en la Compañía
const availableRegions = computed(() => {
    if (!form.team_id) return [];
    return props.all_regions.filter(region => region.team_id == form.team_id);
});

// 🏪 FILTRO DE TIENDAS: Solo muestra tiendas de las regiones seleccionadas (Base + Apoyo)
const availableBranches = computed(() => {
    const selectedRegions = [form.primary_region_id, ...form.support_region_ids].filter(Boolean);
    if (selectedRegions.length === 0) return [];
    return props.all_branches.filter(branch => selectedRegions.includes(branch.region_id));
});

// 👁️ OBSERVADOR: Limpieza de cascada si cambia la compañía
watch(() => form.team_id, () => {
    form.primary_region_id = '';
    form.support_region_ids = [];
    form.selected_branch_ids = [];
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
            <h2 class="font-semibold text-xl text-[#333333] leading-tight">
                Registrar Nuevo Ingeniero
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8 border-t-4 border-[#00408F]">
                    <form @submit.prevent="submit">
                        
                        <div v-if="teams.length > 1" class="mb-8 bg-blue-50 border border-[#00408F]/20 p-4 rounded-md">
                            <InputLabel for="team" value="Compañía / Unidad de Negocio" class="text-[#00408F] font-bold" />
                            <select id="team" v-model="form.team_id" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#FF5501] focus:border-[#FF5501]" required>
                                <option value="" disabled>-- Seleccione una Compañía --</option>
                                <option v-for="team in filteredTeams" :key="team.id" :value="team.id">{{ team.name }}</option>
                            </select>
                            <InputError :message="form.errors.team_id" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div class="md:col-span-2"><h3 class="text-lg font-bold text-[#00408F] border-b border-[#BBBBBB] pb-1">Datos de Identidad</h3></div>
                            <div>
                                <InputLabel for="name" value="Nombre Completo" />
                                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <InputLabel for="email" value="Correo Corporativo" />
                                <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <InputLabel for="employee_code" value="No. de Empleado" />
                                <TextInput id="employee_code" v-model="form.employee_code" type="text" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <InputLabel for="phone1" value="Teléfono de Contacto" />
                                <TextInput id="phone1" v-model="form.phone1" type="text" class="mt-1 block w-full" required />
                            </div>
                            <div class="md:col-span-2">
                                <InputLabel for="password" value="Contraseña de Acceso (Temporal)" />
                                <TextInput id="password" v-model="form.password" type="text" class="mt-1 block w-full text-[#FF5501]" required />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div class="md:col-span-2"><h3 class="text-lg font-bold text-[#00408F] border-b border-[#BBBBBB] pb-1">Asignación Operativa</h3></div>
                            
                            <div class="space-y-4">
                                <div>
                                    <InputLabel for="primary_region" value="Región Base (Principal)" />
                                    <select id="primary_region" v-model="form.primary_region_id" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-[#00408F] focus:border-[#00408F]" required>
                                        <option value="" disabled>Seleccione región...</option>
                                        <option v-for="region in availableRegions" :key="region.id" :value="region.id">{{ region.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="support_regions" value="Regiones de Apoyo" />
                                    <select id="support_regions" v-model="form.support_region_ids" multiple 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm h-32 text-sm">
                                        <option v-for="region in availableRegions" :key="'sup-'+region.id" :value="region.id">{{ region.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-md border border-[#BBBBBB]">
                                <InputLabel value="Tiendas Específicas Asignadas" class="mb-2 text-[#333333] font-bold" />
                                <div v-if="availableBranches.length > 0" class="space-y-2 max-h-48 overflow-y-auto pr-2">
                                    <div v-for="branch in availableBranches" :key="branch.id" class="flex items-center">
                                        <input type="checkbox" :id="'br-'+branch.id" v-model="form.selected_branch_ids" :value="branch.id"
                                            class="rounded border-gray-300 text-[#FF5501] focus:ring-[#FF5501]">
                                        <label :for="'br-'+branch.id" class="ml-2 text-sm text-[#333333]">{{ branch.name }} <span class="text-[10px] text-[#BBBBBB]">({{ branch.external_id_eco }})</span></label>
                                    </div>
                                </div>
                                <div v-else class="text-xs text-[#BBBBBB] italic text-center py-8">
                                    Seleccione regiones para cargar tiendas...
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-10 pt-6 border-t border-[#BBBBBB]">
                            <PrimaryButton class="bg-[#FF5501] hover:bg-[#00408F] transition-colors" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Finalizar Alta de Ingeniero
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
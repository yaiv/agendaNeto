<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    branch: Object,
    regions: Array, // Lista de regiones para el select (filtrada por el controller)
});

// Inicializamos el formulario con los datos existentes
const form = useForm({
    name: props.branch.name,
    address: props.branch.address,
    zone_name: props.branch.zone_name,
    external_id_eco: props.branch.external_id_eco,
    external_id_ceco: props.branch.external_id_ceco,
    latitude: props.branch.latitude,
    longitude: props.branch.longitude,
    region_id: props.branch.region_id,
});

const submit = () => {
    // Usamos PUT para actualizar
    form.put(route('branches.update', props.branch.id), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <AppLayout title="Editar Sucursal">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar Sucursal: {{ branch.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div class="col-span-1 md:col-span-2">
                                <InputLabel for="region_id" value="Región Operativa" />
                                <select 
                                    id="region_id" 
                                    v-model="form.region_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option v-for="region in regions" :key="region.id" :value="region.id">
                                        {{ region.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.region_id" class="mt-2" />
                                <p class="text-xs text-gray-500 mt-1">
                                    Nota: Cambiar la región reasignará automáticamente la compañía.
                                </p>
                            </div>

                            <div>
                                <InputLabel for="name" value="Nombre de Sucursal" />
                                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="external_id_eco" value="ID ECO (Único por Cía)" />
                                <TextInput id="external_id_eco" v-model="form.external_id_eco" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.external_id_eco" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="zone_name" value="Nombre de Zona" />
                                <TextInput id="zone_name" v-model="form.zone_name" type="text" class="mt-1 block w-full" />
                                <InputError :message="form.errors.zone_name" class="mt-2" />
                            </div>

                             <div>
                                <InputLabel for="external_id_ceco" value="CECO (Opcional)" />
                                <TextInput id="external_id_ceco" v-model="form.external_id_ceco" type="text" class="mt-1 block w-full" />
                                <InputError :message="form.errors.external_id_ceco" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="latitude" value="Latitud" />
                                <TextInput id="latitude" v-model="form.latitude" type="text" class="mt-1 block w-full" />
                                <InputError :message="form.errors.latitude" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="longitude" value="Longitud" />
                                <TextInput id="longitude" v-model="form.longitude" type="text" class="mt-1 block w-full" />
                                <InputError :message="form.errors.longitude" class="mt-2" />
                            </div>

                            <div class="col-span-1 md:col-span-2">
                                <InputLabel for="address" value="Dirección Completa" />
                                <TextInput id="address" v-model="form.address" type="text" class="mt-1 block w-full" />
                                <InputError :message="form.errors.address" class="mt-2" />
                            </div>

                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <Link :href="route('regions.branches.index', branch.region_id)" class="text-sm text-gray-600 hover:text-gray-900 mr-4">
                                Cancelar
                            </Link>

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
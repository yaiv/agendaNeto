<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SectionTitle from '@/Components/SectionTitle.vue';

// Definimos la clase estándar para elementos que NO son componentes (Select y Textarea)
const inputClass = "border-gray-300 bg-white text-gray-900 focus:border-[#00408F] focus:ring-[#00408F] rounded-md shadow-sm";

const props = defineProps({
    regions: Array,
    preselectedRegionId: [String, Number],
});

const form = useForm({
    region_id: props.preselectedRegionId || '',
    name: '',
    external_id_eco: '',
    external_id_ceco: '',
    zone_name: '',
    address: '',
    latitude: '',
    longitude: '',
});

const submit = () => {
    form.post(route('branches.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <AppLayout title="Nueva Tienda">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Registrar Tienda Nueva
            </h2>
        </template>

        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <form @submit.prevent="submit">
                
                <div class="md:grid md:grid-cols-3 md:gap-6 mb-10">
                    <SectionTitle>
                        <template #title>Identificación Corporativa</template>
                        <template #description>
                            Datos de identificación interna (ECO/CECO) y asignación regional.
                        </template>
                    </SectionTitle>

                    <div class="mt-5 md:mt-0 md:col-span-2 bg-white shadow sm:rounded-md p-6">
                        <div class="grid grid-cols-6 gap-6">
                            
                            <div class="col-span-6 sm:col-span-4">
                                <InputLabel for="region_id" value="Región Asignada" />
                                <select
                                    id="region_id"
                                    v-model="form.region_id"
                                    :class="[inputClass, 'mt-1 block w-full']"
                                    required
                                >
                                    <option value="" disabled>-- Selecciona una región --</option>
                                    <option 
                                        v-for="region in regions" 
                                        :key="region.id" 
                                        :value="region.id"
                                    >
                                        {{ region.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.region_id" class="mt-2" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="external_id_eco" value="ECO (ID Tienda)" />
                                <TextInput
                                    id="external_id_eco"
                                    v-model="form.external_id_eco"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Ej. 2943"
                                    required
                                />
                                <InputError :message="form.errors.external_id_eco" class="mt-2" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="external_id_ceco" value="CECO" />
                                <TextInput
                                    id="external_id_ceco"
                                    v-model="form.external_id_ceco"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Ej. 652943"
                                />
                                <InputError :message="form.errors.external_id_ceco" class="mt-2" />
                            </div>

                            <div class="col-span-6">
                                <InputLabel for="name" value="Nombre de la Tienda" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Ej. Aviación"
                                    required
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="zone_name" value="Nombre de Zona" />
                                <TextInput
                                    id="zone_name"
                                    v-model="form.zone_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Ej. San Luis Potosí Capital"
                                />
                                <InputError :message="form.errors.zone_name" class="mt-2" />
                            </div>

                        </div>
                    </div>
                </div>

                <div class="hidden sm:block" aria-hidden="true">
                    <div class="py-5">
                        <div class="border-t border-gray-200" />
                    </div>
                </div>

                <div class="md:grid md:grid-cols-3 md:gap-6 mt-10 sm:mt-0">
                    <SectionTitle>
                        <template #title>Geolocalización</template>
                        <template #description>
                            Coordenadas GPS y dirección física para la operación de ingenieros.
                        </template>
                    </SectionTitle>

                    <div class="mt-5 md:mt-0 md:col-span-2 bg-white shadow sm:rounded-md p-6">
                        <div class="grid grid-cols-6 gap-6">
                            
                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="latitude" value="Latitud" />
                                <TextInput
                                    id="latitude"
                                    v-model="form.latitude"
                                    type="number"
                                    step="any"
                                    class="mt-1 block w-full"
                                    placeholder="22.1746683"
                                />
                                <InputError :message="form.errors.latitude" class="mt-2" />
                            </div>

                            <div class="col-span-6 sm:col-span-3">
                                <InputLabel for="longitude" value="Longitud" />
                                <TextInput
                                    id="longitude"
                                    v-model="form.longitude"
                                    type="number"
                                    step="any"
                                    class="mt-1 block w-full"
                                    placeholder="-100.993506"
                                />
                                <InputError :message="form.errors.longitude" class="mt-2" />
                            </div>

                            <div class="col-span-6">
                                <InputLabel for="address" value="Dirección Completa (Opcional)" />
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="3"
                                    :class="[inputClass, 'mt-1 block w-full']"
                                    placeholder="Calle, Número, Colonia..."
                                ></textarea>
                                <InputError :message="form.errors.address" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end px-4 py-3 bg-gray-50 text-right sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md mt-6">
                    <SecondaryButton class="mr-3" @click="$inertia.visit(route('regions.index'))">
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Guardar Tienda
                    </PrimaryButton>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SectionBorder from '@/Components/SectionBorder.vue';

const props = defineProps({
    region: Object,
    teams: {
        type: Array,
        default: () => [],
    },
});

// Formulario de Edición
const form = useForm({
    name: props.region.name,
    team_id: props.region.team_id,
});

const submit = () => {
    form.put(route('regions.update', props.region.id));
};

// Formulario de Eliminación (Separado para seguridad)
const deleteForm = useForm({});

const confirmDelete = () => {
    if (confirm('¿Estás seguro de que deseas eliminar esta región? Esta acción no se puede deshacer.')) {
        deleteForm.delete(route('regions.destroy', props.region.id));
    }
};
</script>

<template>
    <AppLayout title="Editar Región">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar Región: {{ region.name }}
            </h2>
        </template>

        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <div class="md:col-span-1">
                    <div class="px-4 sm:px-0">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Detalles de la Región</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Actualiza el nombre o la asignación de la región.
                        </p>
                    </div>
                </div>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    <form @submit.prevent="submit">
                        <div class="px-4 py-5 bg-white sm:p-6 shadow sm:rounded-tl-md sm:rounded-tr-md">
                            <div class="grid grid-cols-6 gap-6">
                                
                                <div class="col-span-6 sm:col-span-4">
                                    <InputLabel for="name" value="Nombre de la Región" />
                                    <TextInput
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                </div>

                                <div v-if="teams.length > 0" class="col-span-6 sm:col-span-4">
                                    <InputLabel for="team_id" value="Compañía Asignada" />
                                    <select
                                        id="team_id"
                                        v-model="form.team_id"
                                        class="mt-1 block w-full border-gray-300 bg-white text-gray-900 focus:border-[#00408F] focus:ring-[#00408F] rounded-md shadow-sm"
                                        required
                                    >
                                        <option v-for="team in teams" :key="team.id" :value="team.id">
                                            {{ team.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.team_id" class="mt-2" />
                                    <p class="mt-2 text-xs text-orange-600">
                                        ⚠ Cambiar la compañía moverá también todas las sucursales asociadas.
                                    </p>
                                </div>

                            </div>
                        </div>

                        <div class="flex items-center justify-end px-4 py-3 bg-gray-50 text-right sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md">
                            <Link :href="route('regions.index')" class="mr-3 text-sm text-gray-600 underline hover:text-gray-900">
                                Volver
                            </Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Guardar Cambios
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>

            <SectionBorder />

            <div class="mt-10 sm:mt-0">
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <div class="px-4 sm:px-0">
                            <h3 class="text-lg font-medium leading-6 text-red-600">Eliminar Región</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                Eliminar esta región de forma permanente.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="px-4 py-5 bg-white sm:p-6 shadow sm:rounded-lg border border-red-100">
                            <div class="max-w-xl text-sm text-gray-600">
                                Una vez que se elimine la región, todos sus datos y asociaciones podrían verse afectados.
                                Asegúrate de que no tenga sucursales activas antes de proceder.
                            </div>
                            <div class="mt-5">
                                <DangerButton @click="confirmDelete" :class="{ 'opacity-25': deleteForm.processing }" :disabled="deleteForm.processing">
                                    Eliminar Región
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
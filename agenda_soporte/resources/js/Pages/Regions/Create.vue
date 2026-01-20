<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

// Recibimos 'teams' del controlador. 
// Si viene vacío, significa que el usuario es Nivel 2 (Coordinador).
const props = defineProps({
    teams: Array,
    preselectedTeamId: [Number, String], // 👈 Nueva prop

});

const form = useForm({
    name: '',
    team_id: props.preselectedTeamId || '', 
});

const submit = () => {
    form.post(route('regions.store'), {
        onFinish: () => form.reset('name'),
    });
};
</script>

<template>
    <AppLayout title="Nueva Región">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Registrar Nueva Región
            </h2>
        </template>

        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <div class="md:grid md:grid-cols-3 md:gap-6">
                
                <div class="md:col-span-1">
                    <div class="px-4 sm:px-0">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Información de la Región</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Define el nombre de la zona operativa. 
                            <span v-if="teams.length === 0">
                                Se asignará automáticamente a tu compañía actual.
                            </span>
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
                                        placeholder="Ej. Bajío, Norte, Centro Sur"
                                        required
                                        autofocus
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
                                        <option value="" disabled>-- Selecciona una compañía --</option>
                                        <option v-for="team in teams" :key="team.id" :value="team.id">
                                            {{ team.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.team_id" class="mt-2" />
                                    <p class="mt-2 text-xs text-gray-500">
                                        * Como administrador global, debes asignar la región a una compañía.
                                    </p>
                                </div>

                            </div>
                        </div>

                        <div class="flex items-center justify-end px-4 py-3 bg-gray-50 text-right sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md">
                            <Link :href="route('regions.index')" class="mr-3 text-sm text-gray-600 underline hover:text-gray-900">
                                Cancelar
                            </Link>
                            
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Guardar Región
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SectionTitle from '@/Components/SectionTitle.vue';

// Recibimos la lista aquí de posibles coordinadores (owners)
const props = defineProps({
    potentialCoordinators: Array, 
});

// Inicializamos el formulario con la estructura esperada por el StoreCompanyRequest
const form = useForm({
    name: '',
    owner_id: '',
    // Iniciamos con una región vacía para incentivar la creación de estructura inmediata
    regions: [
        { name: '', code: '' }
    ]
});

// Lógica del "Repeater" (Agregar/Quitar filas)
const addRegion = () => {
    form.regions.push({ name: '', code: '' });
};

const removeRegion = (index) => {
    // Evitamos dejar el array vacío (opcional, decisión de UX)
    if (form.regions.length > 1) {
        form.regions.splice(index, 1);
    }
};

const submit = () => {
    form.post(route('admin.companies.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <AppLayout title="Nueva Compañía">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Registrar Nueva Compañía Corporativa
            </h2>
        </template>

        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="md:grid md:grid-cols-3 md:gap-6">
                
                <SectionTitle>
                    <template #title>Datos de la Compañía</template>
                    <template #description>
                        Define la identidad de la nueva organización. Se creará un entorno aislado (Team).
                    </template>
                </SectionTitle>

                <div class="mt-5 md:mt-0 md:col-span-2 space-y-6 bg-white p-6 shadow sm:rounded-md">
                    <div>
                        <InputLabel for="name" value="Nombre de la Compañía" />
                        <TextInput 
                            id="name" 
                            v-model="form.name" 
                            type="text" 
                            class="mt-1 block w-full" 
                            autofocus 
                            placeholder="Ej. Corporativo Tech Global S.A."
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

<div>
                        <InputLabel for="owner_id" value="Asignar Coordinador (Dueño del Team)" />
                        <p class="text-xs text-gray-500 mb-2">Este usuario tendrá control total sobre la compañía (Nivel 2).</p>
                        
                        <select 
                            id="owner_id" 
                            v-model="form.owner_id"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
                        >
                            <option value="" disabled>-- Selecciona un usuario --</option>
                            <option :value="null">Asignarme a mí (Admin Global)</option>
                            
                            <option 
                                v-for="user in potentialCoordinators" 
                                :key="user.id" 
                                :value="user.id"
                            >
                                {{ user.name }} ({{ user.email }})
                            </option>
                        </select>
                        <InputError :message="form.errors.owner_id" class="mt-2" />
                    </div>

                    <div class="border-t border-gray-200 my-6"></div>

                    <div class="border-t border-gray-200 my-6"></div>

                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Estructura Regional Inicial</h3>
                            <SecondaryButton @click="addRegion" type="button" class="text-xs">
                                + Agregar Región
                            </SecondaryButton>
                        </div>

                        <p class="text-sm text-gray-500 mb-4">
                            Define las divisiones territoriales base. Podrás agregar más tarde.
                        </p>
                        
                        <div class="space-y-3">
                            <div 
                                v-for="(region, index) in form.regions" 
                                :key="index" 
                                class="flex gap-4 items-start bg-gray-50 p-3 rounded-lg border border-gray-100"
                            >
                                <div class="flex-1">
                                    <InputLabel :for="'region-name-' + index" value="Nombre Región" class="text-xs text-gray-500"/>
                                    <TextInput 
                                        :id="'region-name-' + index"
                                        v-model="region.name"
                                        type="text"
                                        class="mt-1 block w-full h-9 text-sm"
                                        placeholder="Ej. Norte"
                                    />
                                    <InputError :message="form.errors[`regions.${index}.name`]" class="mt-1" />
                                </div>

                                <div class="w-32">
                                    <InputLabel :for="'region-code-' + index" value="Código" class="text-xs text-gray-500"/>
                                    <TextInput 
                                        :id="'region-code-' + index"
                                        v-model="region.code"
                                        type="text"
                                        class="mt-1 block w-full h-9 text-sm uppercase"
                                        placeholder="NOR"
                                        maxlength="5"
                                    />
                                    <InputError :message="form.errors[`regions.${index}.code`]" class="mt-1" />
                                </div>

                                <div class="pt-6">
                                    <button 
                                        type="button" 
                                        @click="removeRegion(index)"
                                        class="text-red-400 hover:text-red-600 transition"
                                        :disabled="form.regions.length === 1"
                                        :class="{'opacity-50 cursor-not-allowed': form.regions.length === 1}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-4">
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            Crear Compañía y Estructura
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
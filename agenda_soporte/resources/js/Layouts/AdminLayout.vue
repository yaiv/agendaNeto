<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';


defineProps({
    title: String,
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-900 text-gray-100">
        <Head :title="title" />

        <nav class="border-b border-gray-700 bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex flex-shrink-0 items-center">
                            <Link :href="route('dashboard')">
                                <ApplicationMark class="block h-9 w-auto text-white" />
                            </Link>
                            <span class="ml-4 font-bold text-xl tracking-wide text-yellow-500">
                                NIVEL 1: ADMIN
                            </span>
                        </div>

                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex items-center">
                            <Link href="#" class="text-sm font-medium text-gray-300 hover:text-white transition">
                                Dashboard Global
                            </Link>

                             <Link :href="route('admin.companies.index')" :active="route().current('admin.companies.*')">
                                Compañias
                            </Link>

                            <Link :href="route('engineers.index')" :active="route().current('engineers.*')">
                                Ingenieros
                            </Link>


                        </div>
                    </div>

                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <div class="relative ml-3">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                        <img class="h-8 w-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                    </button>
                                </template>

                                <template #content>
                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        {{ $page.props.auth.user.global_role?.toUpperCase() ?? 'ADMIN' }}
                                    </div>
                                    <DropdownLink :href="route('dashboard')">
                                        Ir a Vista Operativa
                                    </DropdownLink>
                                    <div class="border-t border-gray-200" />
                                    <form @submit.prevent="logout">
                                        <DropdownLink as="button">
                                            Cerrar Sesión
                                        </DropdownLink>
                                    </form>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main>
            <div class="py-6">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>
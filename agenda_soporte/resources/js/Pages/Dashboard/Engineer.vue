<script setup>
import { ref } from 'vue'

import AppIcon from '@/Components/AppIcon.vue'
import StatCard from '@/Components/StatCard.vue'
import OrganizationBranches from '@/Components/OrganizationBranches.vue'

import CecoCard from '@/Components/Ceco/CecoCard.vue'
import CecoDetailModal from '@/Components/Ceco/CecoDetailModal.vue'

/**
 * Props
 */
const props = defineProps({
    user: {
        type: Object,
        required: true
    },

    organizationMap: {
        type: Object,
        default: () => ({})
    },

    stats: {
        type: Object,
        default: () => ({
            totalTiendas: 0,
            totalPrimarias: 0,
            totalSoporte: 0,
            totalExternas: 0
        })
    },

    cecos: {
        type: Array,
        default: () => []
    }
})

/**
 * State
 */
const selectedCeco = ref(null)

/**
 * Methods
 */
const openCeco = (ceco) => {
    selectedCeco.value = ceco
}

const closeCeco = () => {
    selectedCeco.value = null
}
</script>

<template>
    <div class="space-y-6">

        <!-- HEADER -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">
                        Panel Operativo
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Ingeniero de Sitio asignado a:
                        <span class="font-bold text-green-700">
                            {{ user.current_team?.name }}
                        </span>
                    </p>
                </div>

                <span class="px-3 py-1 text-xs font-bold text-green-800 bg-green-100 rounded-full">
                    NIVEL 3
                </span>
            </div>
        </div>

        <!-- STATS -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <StatCard
                title="Tiendas Activas"
                :value="stats.totalTiendas"
                color="blue"
            >
                <template #icon>
                    <AppIcon name="store" class="w-6 h-6" />
                </template>
            </StatCard>

            <StatCard
                title="Primarias"
                :value="stats.totalPrimarias"
                color="green"
            >
                <template #icon>
                    <AppIcon name="star" class="w-6 h-6" />
                </template>
            </StatCard>

            <StatCard
                title="Soporte"
                :value="stats.totalSoporte"
                color="orange"
            >
                <template #icon>
                    <AppIcon name="tool" class="w-6 h-6" />
                </template>
            </StatCard>

            <StatCard
                title="Externas"
                :value="stats.totalExternas"
                color="purple"
            >
                <template #icon>
                    <AppIcon name="link" class="w-6 h-6" />
                </template>
            </StatCard>
        </div>

        <!-- ORGANIZATION MAP -->
        <OrganizationBranches
            :organization-map="organizationMap"
            :stats="stats"
        />

        <!-- EMPTY STATE -->
        <div
            v-if="stats.totalTiendas === 0"
            class="bg-yellow-50 border-l-4 border-yellow-400 p-4"
        >
            <div class="flex">
                <AppIcon name="clipboard" class="h-5 w-5 text-yellow-400" />
                <p class="ml-3 text-sm text-yellow-700">
                    No tienes sucursales asignadas actualmente.
                    Mantente atento a nuevas asignaciones de tu coordinador.
                </p>
            </div>
        </div>

        <!-- CECO CARDS -->
        <div
            v-if="cecos.length"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
        >
            <CecoCard
                v-for="ceco in cecos"
                :key="ceco.id"
                :ceco="ceco"
                @select="openCeco"
            />
        </div>

    </div>

    <!-- CECO DETAIL MODAL -->
    <CecoDetailModal
        :show="!!selectedCeco"
        :ceco="selectedCeco"
        @close="closeCeco"
    />
</template>

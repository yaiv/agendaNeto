<script setup>
defineProps({
    show: Boolean,
    ceco: Object
})

defineEmits(['close'])
</script>

<template>
    <transition name="fade">
        <div
            v-if="show"
            class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center"
        >
            <div class="bg-white rounded-xl w-full max-w-md p-6 relative">
                
                <button
                    class="absolute top-3 right-3 text-gray-400 hover:text-gray-700"
                    @click="$emit('close')"
                >
                    ✕
                </button>

                <h2 class="text-lg font-semibold mb-4">
                    Detalle CECO
                </h2>

                <div class="space-y-2 text-sm">
                    <div>
                        <span class="text-gray-500">CECO:</span>
                        <span class="ml-1 font-medium">{{ ceco?.ceco }}</span>
                    </div>

                    <div>
                        <span class="text-gray-500">Nombre:</span>
                        <span class="ml-1 font-medium">{{ ceco?.name }}</span>
                    </div>

                    <div>
                        <span class="text-gray-500">Latitud:</span>
                        <span class="ml-1">{{ ceco?.latitude ?? 'No definida' }}</span>
                    </div>

                    <div>
                        <span class="text-gray-500">Longitud:</span>
                        <span class="ml-1">{{ ceco?.longitude ?? 'No definida' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

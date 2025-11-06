<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-gray-50 p-6 md:p-10">
        <img src="/bg.png" alt="Background" aria-hidden="true" class="pointer-events-none absolute inset-0 h-full w-full object-cover" />
        <div class="relative z-10 w-full max-w-md rounded-xl  bg-white/80 p-6 ">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="route('home')" class="flex flex-col items-center gap-2 font-medium">
                        <div class="mb-1 flex h-10 w-42 items-center justify-center">
                            <img src="/logo.svg" alt="Avatar" class="h-10 w-42 object-cover" />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl font-medium text-blue-500">{{ title }}</h1>
                        <p class="text-muted-foreground text-center text-sm">{{ description }}</p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

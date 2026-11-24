<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import type { PageProps } from '@inertiajs/core';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Photo = { id: number; name: string; url: string };

defineProps<{ photos: Photo[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Foto’s beheren', href: '/dashboard' }];
const form = useForm<{ photos: File[] }>({ photos: [] });
const fileInput = ref<HTMLInputElement | null>(null);
const page = usePage<PageProps & SharedData>();
const uploadError = computed(() => form.errors.photos || Object.entries(form.errors).find(([field]) => field.startsWith('photos.'))?.[1]);

function selectFiles(event: Event): void {
    form.photos = Array.from((event.target as HTMLInputElement).files ?? []);
}

function uploadPhotos(): void {
    if (!form.photos.length) return;

    form.post(route('photos.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}

function deletePhoto(photo: Photo): void {
    if (!window.confirm(`Weet je zeker dat je "${photo.name}" wilt verwijderen?`)) return;

    router.delete(route('photos.destroy', { photo: photo.id }), { preserveScroll: true });
}
</script>

<template>
    <Head title="Foto’s beheren" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <main class="mx-auto flex w-full max-w-5xl flex-col gap-8 p-6">
            <header>
                <h1 class="text-2xl font-semibold">Foto’s beheren</h1>
                <p class="mt-2 text-sm text-muted-foreground">Upload nieuwe afbeeldingen of verwijder foto’s uit de galerij.</p>
            </header>

            <form
                class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border sm:flex-row sm:items-end"
                @submit.prevent="uploadPhotos"
            >
                <div class="flex-1">
                    <label for="photos" class="mb-2 block text-sm font-medium">Afbeeldingen</label>
                    <input
                        id="photos"
                        ref="fileInput"
                        class="block w-full text-sm file:mr-4 file:rounded-md file:border-0 file:bg-neutral-100 file:px-4 file:py-2 file:font-medium dark:file:bg-neutral-800"
                        type="file"
                        accept="image/*"
                        multiple
                        @change="selectFiles"
                    />
                    <p class="mt-2 text-xs text-muted-foreground">Maximaal 20 afbeeldingen per keer, tot 10 MB per afbeelding.</p>
                    <p v-if="uploadError" class="mt-2 text-sm text-destructive" role="alert">{{ uploadError }}</p>
                </div>
                <button
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:cursor-not-allowed disabled:opacity-50"
                    type="submit"
                    :disabled="!form.photos.length || form.processing"
                >
                    {{ form.processing ? `Uploaden ${form.progress?.percentage ?? 0}%` : 'Foto’s uploaden' }}
                </button>
            </form>

            <section aria-labelledby="photos-heading">
                <div class="mb-4 flex items-baseline justify-between">
                    <h2 id="photos-heading" class="text-lg font-semibold">Galerij</h2>
                    <span class="text-sm text-muted-foreground">{{ photos.length }} foto’s</span>
                </div>
                <p v-if="page.props.flash?.success" class="mb-4 text-sm text-green-600">{{ page.props.flash.success }}</p>
                <ul
                    v-if="photos.length"
                    class="divide-y divide-sidebar-border rounded-xl border border-sidebar-border/70 dark:divide-sidebar-border dark:border-sidebar-border"
                >
                    <li v-for="photo in photos" :key="photo.id" class="flex items-center gap-4 p-3">
                        <img class="h-16 w-20 rounded-md bg-muted object-cover" :src="photo.url" :alt="photo.name" loading="lazy" />
                        <span class="min-w-0 flex-1 truncate text-sm">{{ photo.name }}</span>
                        <button
                            class="rounded-md border border-destructive/40 px-3 py-2 text-sm text-destructive hover:bg-destructive/10"
                            type="button"
                            @click="deletePhoto(photo)"
                        >
                            Verwijderen
                        </button>
                    </li>
                </ul>
                <p v-else class="rounded-xl border border-dashed border-sidebar-border p-8 text-center text-sm text-muted-foreground">
                    Er staan nog geen foto’s in de galerij.
                </p>
            </section>
        </main>
    </AppLayout>
</template>

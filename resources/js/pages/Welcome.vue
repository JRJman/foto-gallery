<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Pause, Play, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

type Photo = { id: number; name: string; url: string };
const props = defineProps<{ photos: Photo[] }>();

const isViewerOpen = ref(false);
const isPlaying = ref(false);
const activeIndex = ref(0);
const shuffledPhotos = ref<Photo[]>([]);

let slideshowTimer: number | undefined;

const activePhoto = computed(() => shuffledPhotos.value[activeIndex.value]);

// Fisher-Yates shuffle: maakt een willekeurige volgorde.
function shufflePhotos(): Photo[] {
    const photos = [...props.photos];

    for (let i = photos.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [photos[i], photos[j]] = [photos[j], photos[i]];
    }

    return photos;
}

// Open slideshow met een nieuwe willekeurige volgorde.
function openSlideshow(): void {
    if (!props.photos.length) return;

    shuffledPhotos.value = shufflePhotos();
    activeIndex.value = 0;
    isViewerOpen.value = true;
    isPlaying.value = true;
}

// Open een specifieke foto vanuit het grid.
function openPhoto(photo: Photo): void {
    shuffledPhotos.value = shufflePhotos();

    activeIndex.value = shuffledPhotos.value.findIndex((item) => item.id === photo.id);

    isViewerOpen.value = true;
    isPlaying.value = false;
}

function closeViewer(): void {
    isViewerOpen.value = false;
    isPlaying.value = false;
}

function showNext(): void {
    if (shuffledPhotos.value.length) {
        activeIndex.value = (activeIndex.value + 1) % shuffledPhotos.value.length;
    }
}

function showPrevious(): void {
    if (shuffledPhotos.value.length) {
        activeIndex.value = (activeIndex.value - 1 + shuffledPhotos.value.length) % shuffledPhotos.value.length;
    }
}

function handleKeydown(event: KeyboardEvent): void {
    if (!isViewerOpen.value) return;

    if (event.key === 'Escape') closeViewer();
    if (event.key === 'ArrowRight') showNext();
    if (event.key === 'ArrowLeft') showPrevious();

    if (event.key === ' ') {
        event.preventDefault();
        isPlaying.value = !isPlaying.value;
    }
}

// Slideshow automatisch laten wisselen.
watch([isViewerOpen, isPlaying], () => {
    window.clearInterval(slideshowTimer);

    if (isViewerOpen.value && isPlaying.value && shuffledPhotos.value.length > 1) {
        slideshowTimer = window.setInterval(showNext, 5000);
    }
});

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    window.clearInterval(slideshowTimer);
});
</script>

<template>
    <Head title="Fotoarchief" />

    <main class="gallery">
        <div class="gallery-content">
            <header class="heading">
                <h1>Fotoarchief</h1>

                <button class="slideshow-button" type="button" :disabled="!photos.length" @click="openSlideshow">
                    <Play :size="17" fill="currentColor" />
                    Slideshow
                </button>
            </header>

            <!-- Responsive fotogrid -->
            <div v-if="photos.length" class="photo-grid" aria-label="Foto's">
                <button v-for="photo in photos" :key="photo.id" class="photo-item" type="button" :aria-label="'Open foto'" @click="openPhoto(photo)">
                    <img :src="photo.url" alt="Foto" loading="lazy" />
                </button>
            </div>

            <p v-else class="empty-state">Er staan nog geen foto's in de galerij.</p>
        </div>

        <!-- Slideshow / foto viewer -->
        <Transition name="viewer">
            <div
                v-if="isViewerOpen && activePhoto"
                class="viewer"
                role="dialog"
                aria-modal="true"
                aria-label="Foto bekijken"
                @click.self="closeViewer"
            >
                <div class="viewer-topline">
                    <span></span>

                    <span>
                        {{ (activeIndex + 1).toString().padStart(2, '0') }}
                        <i>/</i>
                        {{ shuffledPhotos.length.toString().padStart(2, '0') }}
                    </span>

                    <button class="viewer-icon" type="button" aria-label="Sluiten" @click="closeViewer">
                        <X :size="22" />
                    </button>
                </div>

                <button
                    v-if="shuffledPhotos.length > 1"
                    class="viewer-arrow viewer-previous"
                    type="button"
                    aria-label="Vorige foto"
                    @click="showPrevious"
                >
                    <ChevronLeft :size="28" />
                </button>

                <img :key="activePhoto.id" class="viewer-image" :src="activePhoto.url" alt="Foto" />

                <button v-if="shuffledPhotos.length > 1" class="viewer-arrow viewer-next" type="button" aria-label="Volgende foto" @click="showNext">
                    <ChevronRight :size="28" />
                </button>

                <div class="viewer-controls">
                    <span>
                        {{ isPlaying ? 'Diavoorstelling' : 'Gepauzeerd' }}
                    </span>

                    <button class="play-toggle" type="button" :aria-label="isPlaying ? 'Pauzeren' : 'Hervatten'" @click="isPlaying = !isPlaying">
                        <Pause v-if="isPlaying" :size="18" />
                        <Play v-else :size="18" fill="currentColor" />
                    </button>
                </div>
            </div>
        </Transition>
    </main>
</template>

<style scoped>
:global(*) {
    box-sizing: border-box;
}

:global(body) {
    margin: 0;
    background: #000;
}

.gallery {
    min-height: 100vh;
    padding: 40px 24px;
    color: #f5f5f5;
    background: #000;
    font-family: Arial, sans-serif;
}

.gallery-content {
    width: min(1200px, 100%);
    margin: 0 auto;
}

.heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 24px;
    margin-bottom: 24px;
    border-bottom: 1px solid #292929;
}

.heading h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 500;
}

.slideshow-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 42px;
    padding: 0 16px;
    color: #111;
    background: #f5f5f5;
    border: 0;
    border-radius: 5px;
    font: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.slideshow-button:hover:not(:disabled) {
    background: #d7d7d7;
}

.slideshow-button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

/* Responsive grid */
.photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
}

.photo-item {
    position: relative;
    display: block;
    width: 100%;
    aspect-ratio: 1 / 1;
    padding: 0;
    overflow: hidden;
    background: #171717;
    border: 0;
    border-radius: 5px;
    cursor: pointer;
}

.photo-item img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.25s ease;
}

.photo-item:hover img {
    transform: scale(1.05);
}

.photo-item:focus-visible {
    outline: 2px solid white;
    outline-offset: 3px;
}

.empty-state {
    padding: 28px 0;
    color: #999;
    font-size: 14px;
}

/* Viewer */
.viewer {
    position: fixed;
    z-index: 50;
    inset: 0;
    display: grid;
    grid-template-rows: 64px 1fr 72px;
    color: #f5f5f5;
    background: #080808;
}

.viewer-topline {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    padding: 0 25px;
    color: #c1c1c1;
    font-size: 12px;
}

.viewer-topline > span:nth-child(2) {
    color: #eee;
    font-variant-numeric: tabular-nums;
}

.viewer-topline i {
    padding: 0 6px;
    color: #777;
    font-style: normal;
}

.viewer-icon,
.viewer-arrow,
.play-toggle {
    display: grid;
    place-items: center;
    color: inherit;
    background: rgb(255 255 255 / 8%);
    border: 1px solid rgb(255 255 255 / 14%);
    cursor: pointer;
}

.viewer-icon {
    width: 38px;
    height: 38px;
    justify-self: end;
    border-radius: 50%;
}

.viewer-image {
    width: 100%;
    height: 100%;
    min-height: 0;
    padding: 12px 90px;
    object-fit: contain;
}

.viewer-arrow {
    position: absolute;
    top: 50%;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    transform: translateY(-50%);
}

.viewer-previous {
    left: 22px;
}

.viewer-next {
    right: 22px;
}

.viewer-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    color: #b7b7b7;
    font-size: 12px;
}

.play-toggle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: #fff;
}

.viewer-enter-active,
.viewer-leave-active {
    transition: opacity 0.2s ease;
}

.viewer-enter-from,
.viewer-leave-to {
    opacity: 0;
}

/* Mobiele weergave */
@media (max-width: 600px) {
    .gallery {
        padding: 24px 12px;
    }

    .heading {
        padding-bottom: 18px;
        margin-bottom: 18px;
    }

    .heading h1 {
        font-size: 20px;
    }

    .slideshow-button {
        min-height: 40px;
        padding: 0 12px;
    }

    .photo-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
    }

    .photo-item {
        border-radius: 3px;
    }

    .viewer {
        grid-template-rows: 58px 1fr 66px;
    }

    .viewer-topline {
        padding: 0 14px;
    }

    .viewer-image {
        padding: 10px 0;
    }

    .viewer-arrow {
        width: 40px;
        height: 40px;
    }

    .viewer-previous {
        left: 10px;
    }

    .viewer-next {
        right: 10px;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

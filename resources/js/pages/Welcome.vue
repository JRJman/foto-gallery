<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Images, Pause, Play, Upload, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

type Photo = { id: number; name: string; url: string };
const props = defineProps<{ photos: Photo[]; canUpload: boolean }>();
const form = useForm<{ photos: File[] }>({ photos: [] });
const fileInput = ref<HTMLInputElement | null>(null);
const isViewerOpen = ref(false);
const isPlaying = ref(false);
const activeIndex = ref(0);
let slideshowTimer: number | undefined;

const activePhoto = computed(() => props.photos[activeIndex.value]);
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

function openViewer(index: number, play = false): void {
    activeIndex.value = index;
    isViewerOpen.value = true;
    isPlaying.value = play;
}

function closeViewer(): void {
    isViewerOpen.value = false;
    isPlaying.value = false;
}

function showNext(): void {
    if (props.photos.length) activeIndex.value = (activeIndex.value + 1) % props.photos.length;
}

function showPrevious(): void {
    if (props.photos.length) activeIndex.value = (activeIndex.value - 1 + props.photos.length) % props.photos.length;
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

watch([isViewerOpen, isPlaying], () => {
    window.clearInterval(slideshowTimer);
    if (isViewerOpen.value && isPlaying.value && props.photos.length > 1) {
        slideshowTimer = window.setInterval(showNext, 5000);
    }
});

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    window.clearInterval(slideshowTimer);
});
</script>

<template>
    <Head title="Fotoarchief">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="gallery-page">
        <header class="topbar">
            <a class="brand" href="/" aria-label="Fotoboek startpagina"
                ><span class="brand-mark"><Images :size="19" /></span>Fotoboek</a
            >
            <nav class="account-links" aria-label="Account">
                <template v-if="!canUpload">
                    <Link :href="route('login')" class="login-link">Inloggen</Link>
                    <Link :href="route('register')" class="register-link">Account maken</Link>
                </template>
                <Link v-else :href="route('dashboard')" class="login-link">Dashboard</Link>
            </nav>
        </header>

        <main class="gallery-main">
            <section class="intro" aria-labelledby="page-title">
                <div>
                    <p class="eyebrow"><span></span> PERSOONLIJK FOTOARCHIEF</p>
                    <h1 id="page-title">Jouw momenten,<br /><em>bij elkaar.</em></h1>
                    <p class="intro-copy">Een plek voor foto's om te bewaren, terug te kijken en te delen op het grote scherm.</p>
                </div>
                <button v-if="photos.length" class="slideshow-button" type="button" @click="openViewer(0, true)">
                    <Play :size="17" fill="currentColor" />Start diavoorstelling
                </button>
            </section>

            <section v-if="canUpload" class="upload-section" aria-label="Foto's uploaden">
                <div class="upload-copy">
                    <span class="upload-icon"><Upload :size="18" /></span>
                    <div>
                        <h2>Voeg foto's toe</h2>
                        <p>Kies een of meer afbeeldingen vanaf je apparaat.</p>
                    </div>
                </div>
                <input ref="fileInput" class="file-input" type="file" accept="image/*" multiple @change="selectFiles" />
                <div class="upload-actions">
                    <span v-if="form.photos.length" class="file-count">{{ form.photos.length }} geselecteerd</span>
                    <button class="choose-button" type="button" @click="fileInput?.click()">Bestanden kiezen</button>
                    <button class="upload-button" type="button" :disabled="!form.photos.length || form.processing" @click="uploadPhotos">
                        <Upload :size="16" />{{ form.processing ? `Uploaden ${form.progress?.percentage ?? 0}%` : 'Uploaden' }}
                    </button>
                </div>
                <p v-if="uploadError" class="form-error" role="alert">{{ uploadError }}</p>
            </section>

            <section class="collection" aria-labelledby="collection-title">
                <div class="collection-heading">
                    <div>
                        <p class="eyebrow">DE COLLECTIE</p>
                        <h2 id="collection-title">
                            Alle foto's <span>{{ photos.length.toString().padStart(2, '0') }}</span>
                        </h2>
                    </div>
                    <button v-if="photos.length" class="text-action" type="button" @click="openViewer(0)">
                        Bekijk foto's <ChevronRight :size="16" />
                    </button>
                </div>
                <div v-if="photos.length" class="photo-grid">
                    <button
                        v-for="(photo, index) in photos"
                        :key="photo.id"
                        class="photo-tile"
                        type="button"
                        :aria-label="`Bekijk ${photo.name}`"
                        @click="openViewer(index)"
                    >
                        <img :src="photo.url" :alt="photo.name" loading="lazy" /><span class="photo-caption">{{ photo.name }}</span>
                    </button>
                </div>
                <div v-else class="empty-state">
                    <div class="empty-art" aria-hidden="true">
                        <span class="art-sun"></span><span class="art-hill art-hill-back"></span><span class="art-hill art-hill-front"></span
                        ><Images :size="24" />
                    </div>
                    <h3>Nog geen foto's</h3>
                    <p>{{ canUpload ? 'Je collectie begint met één mooie herinnering.' : 'Log in om foto’s toe te voegen aan deze collectie.' }}</p>
                    <button v-if="canUpload" class="empty-action" type="button" @click="fileInput?.click()">
                        Kies je eerste foto <ChevronRight :size="16" />
                    </button>
                    <Link v-else :href="route('login')" class="empty-action">Inloggen <ChevronRight :size="16" /></Link>
                </div>
            </section>
        </main>

        <footer class="page-footer">
            <span>FOTOBOEK</span><span>{{ photos.length }} {{ photos.length === 1 ? 'herinnering' : 'herinneringen' }}</span>
        </footer>

        <Transition name="viewer">
            <div
                v-if="isViewerOpen && activePhoto"
                class="viewer"
                role="dialog"
                aria-modal="true"
                :aria-label="activePhoto.name"
                @click.self="closeViewer"
            >
                <div class="viewer-topline">
                    <span>{{ activePhoto.name }}</span>
                    <span>{{ (activeIndex + 1).toString().padStart(2, '0') }} <i>/</i> {{ photos.length.toString().padStart(2, '0') }}</span>
                    <button class="viewer-icon" type="button" aria-label="Sluiten" title="Sluiten" @click="closeViewer"><X :size="22" /></button>
                </div>
                <button
                    v-if="photos.length > 1"
                    class="viewer-arrow viewer-previous"
                    type="button"
                    aria-label="Vorige foto"
                    title="Vorige foto"
                    @click="showPrevious"
                >
                    <ChevronLeft :size="28" />
                </button>
                <img :key="activePhoto.id" class="viewer-image" :src="activePhoto.url" :alt="activePhoto.name" />
                <button
                    v-if="photos.length > 1"
                    class="viewer-arrow viewer-next"
                    type="button"
                    aria-label="Volgende foto"
                    title="Volgende foto"
                    @click="showNext"
                >
                    <ChevronRight :size="28" />
                </button>
                <div class="viewer-controls">
                    <span>{{ isPlaying ? 'Diavoorstelling' : 'Foto bekijken' }}</span>
                    <button
                        class="play-toggle"
                        type="button"
                        :aria-label="isPlaying ? 'Pauzeren' : 'Diavoorstelling starten'"
                        :title="isPlaying ? 'Pauzeren' : 'Diavoorstelling starten'"
                        @click="isPlaying = !isPlaying"
                    >
                        <Pause v-if="isPlaying" :size="18" /><Play v-else :size="18" fill="currentColor" />
                    </button>
                    <span v-if="isPlaying" class="play-hint">Wisselt elke 5 seconden</span>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
:global(*) {
    box-sizing: border-box;
}
:global(body) {
    margin: 0;
}
.gallery-page {
    min-height: 100vh;
    color: #202c29;
    background: #f5f7f4;
    font-family: 'DM Sans', sans-serif;
}
.topbar {
    height: 76px;
    padding: 0 clamp(22px, 6vw, 92px);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #dfe5df;
}
.brand {
    display: inline-flex;
    align-items: center;
    gap: 11px;
    color: #202c29;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
}
.brand-mark {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    color: #f5f7f4;
    background: #263f39;
    border-radius: 4px;
}
.account-links {
    display: flex;
    align-items: center;
    gap: 22px;
    font-size: 13px;
    font-weight: 600;
}
.login-link {
    color: #59645f;
    text-decoration: none;
}
.register-link {
    padding: 10px 15px;
    color: white;
    background: #d65b43;
    border-radius: 3px;
    text-decoration: none;
}
.gallery-main {
    width: min(1120px, calc(100% - 44px));
    margin: 0 auto;
}
.intro {
    min-height: 276px;
    padding: 58px 0 43px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 30px;
}
.eyebrow {
    margin: 0 0 13px;
    color: #73817a;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.4px;
}
.eyebrow span {
    display: inline-block;
    width: 7px;
    height: 7px;
    margin-right: 7px;
    background: #e4a344;
    border-radius: 50%;
}
h1,
.collection-heading h2,
.empty-state h3 {
    margin: 0;
}
h1 {
    color: #243832;
    font-family: 'Playfair Display', Georgia, serif;
    font-size: clamp(42px, 5vw, 61px);
    font-weight: 500;
    line-height: 1.02;
}
h1 em {
    color: #d65b43;
    font-weight: 500;
}
.intro-copy {
    max-width: 430px;
    margin: 15px 0 0;
    color: #73817a;
    font-size: 14px;
    line-height: 1.65;
}
.slideshow-button,
.upload-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    border: 0;
    border-radius: 3px;
    color: #fff;
    background: #263f39;
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}
.slideshow-button {
    padding: 13px 17px;
    margin-bottom: 4px;
    white-space: nowrap;
}
.slideshow-button:hover,
.upload-button:not(:disabled):hover {
    background: #34574e;
}
.upload-section {
    min-height: 90px;
    margin: 0 0 55px;
    padding: 16px 19px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    border: 1px solid #dce4de;
    border-left: 3px solid #e4a344;
    background: #fff;
}
.upload-copy {
    display: flex;
    align-items: center;
    gap: 13px;
}
.upload-icon {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    color: #d65b43;
    background: #faeee9;
    border-radius: 3px;
}
.upload-copy h2 {
    margin: 0 0 4px;
    font-size: 14px;
    font-weight: 700;
}
.upload-copy p {
    margin: 0;
    color: #78837e;
    font-size: 12px;
}
.file-input {
    display: none;
}
.upload-actions {
    display: flex;
    align-items: center;
    gap: 9px;
}
.file-count {
    color: #78837e;
    font-size: 12px;
}
.choose-button {
    padding: 10px 13px;
    color: #34443e;
    background: #fff;
    border: 1px solid #dce4de;
    border-radius: 3px;
    font: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}
.upload-button {
    padding: 11px 14px;
    font-size: 12px;
}
.upload-button:disabled {
    opacity: 0.48;
    cursor: not-allowed;
}
.form-error {
    width: 100%;
    margin: -4px 0 0;
    color: #bd483c;
    font-size: 12px;
}
.collection {
    padding-bottom: 78px;
}
.collection-heading {
    min-height: 67px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    border-bottom: 1px solid #dfe5df;
}
.collection-heading .eyebrow {
    margin-bottom: 6px;
}
.collection-heading h2 {
    padding-bottom: 14px;
    color: #263f39;
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 27px;
    font-weight: 500;
}
.collection-heading h2 span {
    margin-left: 7px;
    color: #9aa49e;
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    font-weight: 500;
    vertical-align: middle;
}
.text-action,
.empty-action {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 0 0 15px;
    color: #d65b43;
    background: none;
    border: 0;
    font: inherit;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
}
.photo-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
}
.photo-tile {
    position: relative;
    aspect-ratio: 1.2;
    min-width: 0;
    padding: 0;
    overflow: hidden;
    background: #dfe5df;
    border: 0;
    border-radius: 3px;
    cursor: zoom-in;
    animation: rise-in 0.45s both;
}
.photo-tile:nth-child(5n + 1) {
    aspect-ratio: 1;
}
.photo-tile img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    transition: transform 0.45s cubic-bezier(0.2, 0.7, 0.2, 1);
}
.photo-tile:hover img {
    transform: scale(1.045);
}
.photo-caption {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    padding: 24px 12px 11px;
    overflow: hidden;
    color: #fff;
    background: linear-gradient(transparent, rgb(15 24 20 / 72%));
    font-size: 11px;
    text-align: left;
    text-overflow: ellipsis;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.2s ease;
}
.photo-tile:hover .photo-caption,
.photo-tile:focus-visible .photo-caption {
    opacity: 1;
}
.empty-state {
    min-height: 325px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}
.empty-art {
    position: relative;
    width: 105px;
    height: 88px;
    margin-bottom: 19px;
    overflow: hidden;
    display: grid;
    place-items: center;
    color: #f5f7f4;
    background: #315349;
    border-radius: 52px 52px 4px 4px;
}
.empty-art svg {
    position: relative;
    z-index: 1;
    margin-top: 21px;
}
.art-sun {
    position: absolute;
    top: 17px;
    right: 22px;
    width: 20px;
    height: 20px;
    background: #e7ad55;
    border-radius: 50%;
}
.art-hill {
    position: absolute;
    bottom: -34px;
    width: 105px;
    height: 70px;
    border-radius: 50% 50% 0 0;
    transform: rotate(-17deg);
}
.art-hill-back {
    left: -44px;
    background: #648272;
}
.art-hill-front {
    right: -42px;
    bottom: -44px;
    background: #d65b43;
}
.empty-state h3 {
    color: #293d36;
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 23px;
    font-weight: 500;
}
.empty-state p {
    margin: 8px 0 14px;
    color: #7d8982;
    font-size: 13px;
}
.empty-action {
    padding: 0;
}
.page-footer {
    min-height: 63px;
    padding: 0 clamp(22px, 6vw, 92px);
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #8a9690;
    border-top: 1px solid #dfe5df;
    font-size: 10px;
}
.page-footer span:first-child {
    font-weight: 700;
    letter-spacing: 1.2px;
}
.viewer {
    position: fixed;
    z-index: 50;
    inset: 0;
    display: grid;
    grid-template-rows: 64px 1fr 72px;
    color: #f5f7f4;
    background: #101714;
}
.viewer-topline {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    padding: 0 25px;
    color: #c1cbc4;
    font-size: 12px;
}
.viewer-topline > span:first-child {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.viewer-topline > span:nth-child(2) {
    color: #eef2ee;
    font-variant-numeric: tabular-nums;
}
.viewer-topline i {
    padding: 0 6px;
    color: #65726a;
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
    object-fit: contain;
    padding: 12px 90px;
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
    color: #b7c1ba;
    font-size: 11px;
}
.play-toggle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: #fff;
}
.play-hint {
    color: #718078;
}
.viewer-enter-active,
.viewer-leave-active {
    transition: opacity 0.2s ease;
}
.viewer-enter-from,
.viewer-leave-to {
    opacity: 0;
}
@keyframes rise-in {
    from {
        opacity: 0;
        transform: translateY(9px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
@media (max-width: 760px) {
    .topbar {
        height: 66px;
        padding: 0 20px;
    }
    .gallery-main {
        width: min(100% - 40px, 580px);
    }
    .intro {
        min-height: auto;
        padding: 44px 0 30px;
        align-items: flex-start;
        flex-direction: column;
        gap: 21px;
    }
    h1 {
        font-size: 47px;
    }
    .intro-copy {
        max-width: 360px;
        font-size: 13px;
    }
    .slideshow-button {
        margin: 0;
    }
    .upload-section {
        align-items: flex-start;
        flex-direction: column;
        margin-bottom: 39px;
    }
    .upload-actions {
        width: 100%;
        flex-wrap: wrap;
    }
    .file-count {
        width: 100%;
    }
    .collection {
        padding-bottom: 55px;
    }
    .photo-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
    }
    .photo-tile {
        aspect-ratio: 0.95;
    }
    .photo-caption {
        opacity: 1;
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
    .play-hint {
        display: none;
    }
}
@media (max-width: 390px) {
    .account-links {
        gap: 12px;
        font-size: 12px;
    }
    .register-link {
        padding: 9px 10px;
    }
    .upload-actions {
        gap: 7px;
    }
    .choose-button,
    .upload-button {
        padding: 10px;
    }
}
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

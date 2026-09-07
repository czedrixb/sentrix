<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { catalog, content, reference as referenceApi } from '@/api';
import { useReferenceStore } from '@/stores/reference';
import ProductCard from '@/components/ProductCard.vue';
import { formatDate } from '@/support/format';

const reference = useReferenceStore();

const banners = ref([]);
const gallery = ref([]);
const featured = ref([]);
const posts = ref([]);
const activeBanner = ref(0);

/**
 * The hero crossfades between banners rather than switching instantly. Cleared
 * on unmount so navigating away does not leave a timer running.
 */
let bannerTimer = null;

function startBannerRotation() {
    stopBannerRotation();

    if (banners.value.length < 2) {
        return;
    }

    bannerTimer = window.setInterval(() => {
        activeBanner.value = (activeBanner.value + 1) % banners.value.length;
    }, 6000);
}

function stopBannerRotation() {
    if (bannerTimer !== null) {
        window.clearInterval(bannerTimer);
        bannerTimer = null;
    }
}

function showBanner(index) {
    activeBanner.value = index;
    startBannerRotation();
}

async function loadFeatured() {
    const response = await catalog.products({
        featured: 1,
        per_page: 8,
        branch: reference.selectedBranchSlug ?? undefined,
    });

    featured.value = response.data;
}

/**
 * The solutions row shows top-level categories only; the reference's arch tiles
 * are wide enough for four of them and no more.
 */
const solutions = computed(() => reference.categories.slice(0, 4));

/**
 * Testimonials are static copy. There is no testimonial model in the schema and
 * inventing one for a marketing block would be the wrong place to put a
 * migration; if the client wants these editable they become a CMS type.
 */
const testimonials = [
    {
        quote: 'They surveyed the shop before quoting and told us four cameras would do what we had been sold eight for elsewhere. Two years on, nothing has needed a call-out.',
        name: 'Maria Elena Santos',
        role: 'Owner, hardware retailer — Davao City',
        featured: false,
    },
    {
        quote: 'The install was finished in a day and they walked our supervisors through the playback and export before they left. When we needed footage for an insurance claim, we could pull it ourselves.',
        name: 'Engr. Rolando Ceniza',
        role: 'Site Manager, construction — Mandaue City',
        featured: true,
    },
    {
        quote: 'We asked for ninety days of retention because our permit requires it. They sized the drives for it and showed us the arithmetic rather than just selling us the biggest one.',
        name: 'Anna Liza Reyes',
        role: 'Administrator, private school — Iloilo City',
        featured: false,
    },
    {
        quote: 'Our old system had been dropping cameras every night for months. They traced it to the cable in an afternoon, replaced two runs, and it has not missed a night since.',
        name: 'Benedict Tan',
        role: 'Operations, cold storage — Cebu City',
        featured: false,
    },
    {
        quote: 'Being able to see what is actually on the shelf at our branch before driving over has saved us more trips than I can count.',
        name: 'Joseph Abadilla',
        role: 'Purchasing, cooperative — General Santos City',
        featured: false,
    },
];

onMounted(async () => {
    [banners.value, gallery.value, posts.value] = await Promise.all([
        referenceApi.banners(),
        referenceApi.gallery(),
        content.posts({ per_page: 3 }).then((response) => response.data),
    ]);

    startBannerRotation();
});

onBeforeUnmount(stopBannerRotation);

watch(() => reference.selectedBranchSlug, loadFeatured, { immediate: true });
</script>

<template>
    <div>
        <!-- Hero: one dark surface, inset from the page ground and heavily rounded. -->
        <section class="mx-auto max-w-7xl px-4 pt-4">
            <div class="relative isolate overflow-hidden rounded-[2rem] bg-ink-900 sm:rounded-[2.5rem]">
                <TransitionGroup name="fade">
                    <img
                        v-for="(banner, index) in banners"
                        v-show="index === activeBanner"
                        :key="banner.id"
                        :src="banner.url"
                        :alt="banner.headline ?? ''"
                        class="absolute inset-0 size-full object-cover"
                    >
                </TransitionGroup>

                <!-- Scrim: the headline has to stay legible over any photograph. -->
                <div class="absolute inset-0 bg-gradient-to-r from-ink-900/90 via-ink-900/65 to-ink-900/20"></div>

                <div class="relative px-6 py-20 sm:px-14 sm:py-28 lg:px-20 lg:py-36">
                    <p class="eyebrow text-brand-200">Trusted since 2005</p>

                    <h1 class="mt-5 max-w-2xl text-4xl font-normal leading-[1.12] text-white sm:text-5xl lg:text-6xl">
                        Securing your world with advanced surveillance solutions
                    </h1>

                    <p class="mt-6 max-w-lg text-base leading-relaxed text-ink-300">
                        Cameras, recorders, alarms and access control &mdash; specified for your site, stocked at
                        {{ reference.pickupBranches.length }} branches, and installed by people who will come back if
                        it stops working.
                    </p>

                    <div class="mt-9 flex flex-wrap gap-3">
                        <RouterLink :to="{ name: 'shop' }" class="btn group bg-white text-ink-900 hover:bg-brand-200">
                            Shop now
                            <span class="btn-arrow" aria-hidden="true">&rarr;</span>
                        </RouterLink>
                        <RouterLink :to="{ name: 'branches' }" class="btn border border-white/25 text-white hover:bg-white/10">
                            Find a branch
                        </RouterLink>
                    </div>

                    <div v-if="banners.length > 1" class="mt-12 flex gap-2">
                        <button
                            v-for="(banner, index) in banners"
                            :key="banner.id"
                            type="button"
                            class="h-1 rounded-full transition-all duration-500 ease-soft"
                            :class="index === activeBanner ? 'w-10 bg-white' : 'w-5 bg-white/35 hover:bg-white/60'"
                            :aria-label="`Banner ${index + 1}`"
                            @click="showBanner(index)"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- Expertise: eyebrow, heading, a stat mark, then an asymmetric image pair. -->
        <section v-reveal class="section">
            <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
                <p class="eyebrow lg:col-span-3 lg:pt-3">Expertise &amp; Experience</p>

                <h2 class="text-3xl font-normal leading-tight text-ink-900 sm:text-4xl lg:col-span-6">
                    Your trusted partner in advanced security solutions
                </h2>

                <div class="flex items-center gap-4 lg:col-span-3 lg:justify-end">
                    <p class="font-display text-5xl font-medium leading-none text-ink-900">20<span class="text-brand-500">+</span></p>
                    <p class="max-w-[7rem] text-xs leading-snug text-ink-400">Years of experience on Philippine sites</p>
                </div>
            </div>

            <div class="mt-12 grid gap-6 lg:grid-cols-12 lg:items-end">
                <div v-reveal="1" class="lg:col-span-5">
                    <div class="overflow-hidden rounded-3xl bg-ink-100">
                        <img
                            v-if="gallery[0]"
                            :src="gallery[0].url"
                            :alt="gallery[0].caption ?? 'Installation work'"
                            loading="lazy"
                            class="aspect-[4/3] w-full object-cover transition-transform duration-700 ease-soft hover:scale-[1.04]"
                        >
                    </div>

                    <p class="mt-6 max-w-md text-sm leading-relaxed text-ink-500">
                        With more than twenty years fitting camera, alarm and access control systems across the
                        Philippines, we survey before we quote and we size the storage before we sell the drive.
                        Every branch holds its own stock, so what the site shows is what is on the shelf.
                    </p>

                    <RouterLink :to="{ name: 'about' }" class="btn-ghost group mt-5 px-0 hover:bg-transparent hover:text-brand-600">
                        About us
                        <span class="btn-arrow" aria-hidden="true">&rarr;</span>
                    </RouterLink>
                </div>

                <div v-reveal="2" class="lg:col-span-7">
                    <div class="overflow-hidden rounded-3xl bg-ink-100">
                        <img
                            v-if="gallery[1]"
                            :src="gallery[1].url"
                            :alt="gallery[1].caption ?? 'Showroom'"
                            loading="lazy"
                            class="aspect-[16/10] w-full object-cover transition-transform duration-700 ease-soft hover:scale-[1.04]"
                        >
                    </div>
                </div>
            </div>
        </section>

        <!-- Solutions: the reference's arch tiles, one per top-level category. -->
        <section v-reveal class="section pt-0">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Our Core Expertise</p>
                    <h2 class="mt-3 max-w-md text-3xl font-normal leading-tight text-ink-900 sm:text-4xl">
                        Specialized security solutions
                    </h2>
                </div>

                <RouterLink :to="{ name: 'shop' }" class="btn-secondary group">
                    Browse the catalogue
                    <span class="btn-arrow" aria-hidden="true">&rarr;</span>
                </RouterLink>
            </div>

            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <RouterLink
                    v-for="(category, index) in solutions"
                    :key="category.id"
                    v-reveal="index"
                    :to="{ name: 'shop', query: { category: category.slug } }"
                    class="arch group bg-gradient-to-b from-brand-200 to-brand-500 p-8 pt-16 text-center hover:-translate-y-1"
                >
                    <p class="font-heading text-lg font-medium text-ink-900">{{ category.name }}</p>
                    <p class="mx-auto mt-3 max-w-[15rem] text-xs leading-relaxed text-ink-800/75">
                        {{ category.description }}
                    </p>
                    <span
                        class="mt-6 inline-flex size-9 items-center justify-center rounded-full bg-white/70 text-ink-900 transition-colors duration-300 ease-soft group-hover:bg-white"
                        aria-hidden="true"
                    >&rarr;</span>
                </RouterLink>
            </div>
        </section>

        <!-- Featured -->
        <section v-if="featured.length" v-reveal class="section pt-0">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">In Stock Now</p>
                    <h2 class="mt-3 text-3xl font-normal text-ink-900 sm:text-4xl">Featured equipment</h2>
                    <p class="mt-2 text-sm text-ink-400">At {{ reference.selectedBranch?.name }}</p>
                </div>

                <RouterLink :to="{ name: 'shop' }" class="btn-ghost group px-0 hover:bg-transparent hover:text-brand-600">
                    View all
                    <span class="btn-arrow" aria-hidden="true">&rarr;</span>
                </RouterLink>
            </div>

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <ProductCard
                    v-for="(product, index) in featured"
                    :key="product.id"
                    v-reveal="index % 4"
                    :product="product"
                />
            </div>
        </section>

        <!-- Testimonials: a masonry column layout, one dark card among the light ones. -->
        <section v-reveal class="section pt-0">
            <div class="text-center">
                <p class="eyebrow">Client Success Stories</p>
                <h2 class="mt-3 text-3xl font-normal text-ink-900 sm:text-4xl">Your security, our priority</h2>
            </div>

            <div class="mt-10 gap-5 sm:columns-2 lg:columns-3">
                <figure
                    v-for="testimonial in testimonials"
                    :key="testimonial.name"
                    class="mb-5 break-inside-avoid rounded-3xl border p-7"
                    :class="testimonial.featured
                        ? 'border-transparent bg-ink-900 text-ink-300'
                        : 'border-ink-200/70 bg-white text-ink-500'"
                >
                    <div class="flex gap-0.5 text-brand-400" aria-label="Five out of five">
                        <svg v-for="star in 5" :key="star" class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M10 1.6l2.47 5.3 5.53.66-4.1 3.9 1.08 5.68L10 14.4l-4.98 2.74L6.1 11.46 2 7.56l5.53-.66L10 1.6Z" />
                        </svg>
                    </div>

                    <blockquote class="mt-4 text-sm leading-relaxed">{{ testimonial.quote }}</blockquote>

                    <figcaption class="mt-5">
                        <p class="font-heading text-sm font-medium" :class="testimonial.featured ? 'text-white' : 'text-ink-900'">
                            {{ testimonial.name }}
                        </p>
                        <p class="mt-0.5 text-xs text-ink-400">{{ testimonial.role }}</p>
                    </figcaption>
                </figure>
            </div>
        </section>

        <!-- News -->
        <section v-if="posts.length" v-reveal class="section pt-0">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">From the Workshop</p>
                    <h2 class="mt-3 text-3xl font-normal text-ink-900 sm:text-4xl">Latest news</h2>
                </div>

                <RouterLink :to="{ name: 'news' }" class="btn-ghost group px-0 hover:bg-transparent hover:text-brand-600">
                    All news
                    <span class="btn-arrow" aria-hidden="true">&rarr;</span>
                </RouterLink>
            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-3">
                <RouterLink
                    v-for="(post, index) in posts"
                    :key="post.id"
                    v-reveal="index"
                    :to="{ name: 'post', params: { slug: post.slug } }"
                    class="card group overflow-hidden hover:-translate-y-1 hover:border-brand-300"
                >
                    <div class="aspect-video overflow-hidden bg-ink-100">
                        <img
                            v-if="post.thumbnail_url"
                            :src="post.thumbnail_url"
                            :alt="post.title"
                            loading="lazy"
                            class="size-full object-cover transition-transform duration-700 ease-soft group-hover:scale-105"
                        >
                    </div>
                    <div class="p-6">
                        <p class="text-xs text-ink-400">{{ formatDate(post.published_at) }}</p>
                        <p class="mt-2 font-heading font-medium text-ink-900">{{ post.title }}</p>
                        <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-ink-500">{{ post.excerpt }}</p>
                    </div>
                </RouterLink>
            </div>
        </section>
    </div>
</template>

<style scoped>
/* Hero banner crossfade. */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.9s var(--ease-soft);
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>

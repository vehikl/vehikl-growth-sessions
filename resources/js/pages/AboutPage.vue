<script setup lang="ts">
import PageContainer from '@/components/PageContainer.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Head } from '@inertiajs/vue3';

interface ChatLine {
    speaker: 'hiker' | 'lumberjack';
    text: string;
}

const conversation: ChatLine[] = [
    { speaker: 'hiker', text: `What's the problem?` },
    { speaker: 'lumberjack', text: `My saw's blunt and won't cut the tree properly.` },
    { speaker: 'hiker', text: `Why don't you just sharpen it?` },
    { speaker: 'lumberjack', text: `Because then I'd have to stop sawing! I don't have time to stop — this tree isn't going to fell itself.` },
    { speaker: 'hiker', text: `But if you sharpened your saw, you could cut more efficiently and effectively than before.` },
    { speaker: 'lumberjack', text: `But I don't have time to stop!` },
];

interface FeatureCard {
    icon: string;
    title: string;
    body: string;
    link?: { label: string; href: string; external?: boolean };
}

const featureCards: FeatureCard[] = [
    {
        icon: '🌱',
        title: 'How it works',
        body: 'Anyone can host a session, on any topic — pairing on a side project, a book club chapter, or something brand new. Public or private, spectators welcome or just the crew.',
    },
    {
        icon: '🚀',
        title: 'Getting started',
        body: `Pick a time, describe what you're working on, and others can join, watch, or find you later in the recap.`,
    },
    {
        icon: '💡',
        title: 'Suggestions',
        body: `Got an idea for a topic, a host, or a feature for this board? File it and we'll take a look.`,
        link: { label: 'Open a GitHub issue', href: 'https://github.com/vehikl/vehikl-growth-sessions/issues', external: true },
    },
    {
        icon: '💬',
        title: 'Feedback',
        body: `Loved a session, or think something could work better? Every bit helps us improve the program.`,
        link: { label: 'Email us', href: 'mailto:gsfeedback@vehikl.com' },
    },
];
</script>

<template>
    <Head title="About" />

    <PageContainer>
        <PageHeader
            eyebrow="About"
            badge="True story (kind of)"
            title="What is a Growth Session?"
        >
            <template #lead>
                In <strong class="gs-text-strong font-semibold">"The 7 Habits of Highly Effective People,"</strong> Stephen Covey tells the story of a
                hiker who comes across a frustrated lumberjack, sawing away at a tree — swearing, labouring, getting nowhere.
            </template>
        </PageHeader>

        <!-- Chat card -->
        <div class="gs-card gs-border rounded-2xl border p-6 shadow-sm sm:p-8">
            <div class="space-y-5">
                <div
                    v-for="(line, index) in conversation"
                    :key="index"
                    class="flex items-end gap-3"
                    :class="line.speaker === 'lumberjack' ? 'flex-row-reverse' : 'flex-row'"
                >
                    <span
                        class="flex h-9 w-9 flex-none items-center justify-center rounded-full text-sm text-white shadow-sm"
                        :class="line.speaker === 'hiker' ? 'bg-blue-500' : 'bg-[#8a5a3c]'"
                        aria-hidden="true"
                    >
                        <i :class="['fa', line.speaker === 'hiker' ? 'fa-male' : 'fa-tree']"></i>
                    </span>
                    <p
                        class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed sm:text-base"
                        :class="
                            line.speaker === 'lumberjack'
                                ? 'gs-accent-bg rounded-br-sm font-medium text-white'
                                : 'gs-secondary-bg gs-text-strong rounded-bl-sm'
                        "
                    >
                        {{ line.text }}
                    </p>
                </div>
            </div>

            <div class="gs-divider-color mt-7 border-t pt-5">
                <p class="gs-text-muted text-sm italic sm:text-base">
                    The hiker shook his head and kept walking — leaving the lumberjack to his pointless, exhausting frustration.
                </p>
            </div>
        </div>

        <!-- Highlight quote -->
        <blockquote
            class="border-vehikl-orange/20 from-vehikl-orange/12 to-vehikl-orange/5 relative mt-8 overflow-hidden rounded-2xl border bg-gradient-to-br p-8 sm:p-10"
        >
            <i class="fa fa-quote-right gs-accent-text absolute top-6 right-6 text-3xl opacity-25" aria-hidden="true"></i>
            <p class="gs-text-strong max-w-4xl text-xl leading-relaxed font-medium tracking-wide italic sm:text-2xl">
                Growth Sessions are our time to sharpen the saw — and maybe discover the chainsaw along the way. A space for experimenting, getting
                unstuck, learning from each other, and sharing what we've found.
            </p>
        </blockquote>

        <!-- Feature cards -->
        <div class="mt-12 grid grid-cols-1 gap-5 md:grid-cols-2">
            <div v-for="card in featureCards" :key="card.title" class="gs-card gs-border flex flex-col rounded-2xl border p-6 shadow-sm sm:p-7">
                <span class="bg-vehikl-orange/10 mb-4 flex h-11 w-11 items-center justify-center rounded-xl text-xl" aria-hidden="true">
                    {{ card.icon }}
                </span>
                <h2 class="gs-text-strong text-sm font-semibold tracking-[0.08em] uppercase">{{ card.title }}</h2>
                <p class="gs-text-sub mt-2 text-sm leading-relaxed">{{ card.body }}</p>
                <a
                    v-if="card.link"
                    :href="card.link.href"
                    :target="card.link.external ? '_blank' : undefined"
                    :rel="card.link.external ? 'noopener noreferrer' : undefined"
                    class="transition-smooth gs-accent-text mt-5 inline-flex items-center gap-1.5 text-sm font-semibold underline decoration-transparent underline-offset-2 hover:decoration-current"
                >
                    {{ card.link.label }}
                    <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>
    </PageContainer>
</template>

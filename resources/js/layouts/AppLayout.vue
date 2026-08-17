<script setup lang="ts">
import VAvatar from '@/components/legacy/VAvatar.vue';
import { useTheme } from '@/composables/useTheme';
import { loginUrl } from '@/lib/loginUrl';
import VehiklLogo from '@/svgs/VehiklLogo.vue';
import type { BreadcrumbItemType, IUser } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { onClickOutside } from '@vueuse/core';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useEcho } from '@laravel/echo-vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

interface Shortcut {
    name: string;
    caption?: string;
    shortcut: string;
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { theme, toggleTheme } = useTheme();
const isDark = computed(() => theme.value === 'dark');
const shortcutModifier = typeof navigator !== 'undefined' && /Mac|iPhone|iPad|iPod/.test(navigator.platform) ? '⌘' : 'Ctrl ';
const shortcuts: Shortcut[] = [
    { name: 'Search sessions', caption: 'Available on the Board', shortcut: `${shortcutModifier}K` },
    { name: 'Day view', caption: 'Available on the Board', shortcut: 'D' },
    { name: 'Week view', caption: 'Available on the Board', shortcut: 'W' },
    { name: 'Switch theme', shortcut: 'T' },
    { name: 'Show keyboard shortcuts', shortcut: '?' },
];

const navLinkClass = 'mr-2 cursor-pointer text-sm font-semibold uppercase tracking-[0.08em] transition-smooth hover:text-white sm:mr-3';

function navigationClass(routeName: string): string {
    return `${navLinkClass} ${route().current(routeName) ? 'text-white' : 'text-white/55'}`;
}

const page = usePage<{
    auth: {
        user: IUser | null;
    };
}>();
const user = computed(() => page.props.auth.user ?? null);
const userMenuOpen = ref(false);
const userMenu = ref<HTMLElement | null>(null);
const mobileMenuOpen = ref(false);
const mobileMenu = ref<HTMLElement | null>(null);
const shortcutsPopover = ref<HTMLElement | null>(null);
onClickOutside(userMenu, () => (userMenuOpen.value = false));
onClickOutside(mobileMenu, () => (mobileMenuOpen.value = false));

if (user.value) {
    useEcho(`notifications.${user.value.id}`, '.notification.created', (e) => {
        console.log("Notification received:", e)
    }, [], 'private')
}

function handleThemeShortcut(event: KeyboardEvent) {
    if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey || event.key.toLowerCase() !== 't') return;

    const target = event.target;
    if (
        target instanceof HTMLInputElement ||
        target instanceof HTMLTextAreaElement ||
        target instanceof HTMLSelectElement ||
        (target instanceof HTMLElement && target.isContentEditable)
    ) {
        return;
    }

    toggleTheme();
}

function handleShortcutsDialog(event: KeyboardEvent) {
    if (event.key !== '?' || event.altKey || event.ctrlKey || event.metaKey) return;

    const target = event.target;
    if (
        target instanceof HTMLInputElement ||
        target instanceof HTMLTextAreaElement ||
        target instanceof HTMLSelectElement ||
        (target instanceof HTMLElement && target.isContentEditable)
    ) {
        return;
    }

    event.preventDefault();
    shortcutsPopover.value?.togglePopover();
}

function focusSessionSearch() {
    window.dispatchEvent(new CustomEvent('gs:focus-search'));
}

function handleSearchShortcut(event: KeyboardEvent) {
    if (event.key.toLowerCase() !== 'k' || (!event.metaKey && !event.ctrlKey) || event.altKey) return;

    event.preventDefault();
    focusSessionSearch();
}

onMounted(() => {
    window.addEventListener('keydown', handleThemeShortcut);
    window.addEventListener('keydown', handleShortcutsDialog);
    window.addEventListener('keydown', handleSearchShortcut);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleThemeShortcut);
    window.removeEventListener('keydown', handleShortcutsDialog);
    window.removeEventListener('keydown', handleSearchShortcut);
});
</script>

<template>
    <header class="gs-header-bg flex items-center justify-between gap-3 px-5 py-3.5 sm:px-7">
        <div class="flex items-center gap-4">
            <Link :href="route('home')" class="transition-smooth flex items-center hover:opacity-80">
                <VehiklLogo />
            </Link>
            <span class="h-6 w-px bg-white/15 md:block"></span>
            <span class="text-xs leading-none font-medium tracking-[0.28em] text-white/50 uppercase md:block"> Growth Sessions </span>
        </div>

        <div class="hidden items-center justify-end gap-x-3 lg:flex lg:gap-x-4">
            <template v-if="$page.props.auth.user">
                <!-- Dashboard leads: it is the only "you" surface, and the rest are "everyone" surfaces. -->
                <Link :href="route('dashboard')" :class="navigationClass('dashboard')">Dashboard</Link>
                <Link :href="route('home')" :class="navigationClass('home')">Board</Link>
                <Link v-if="$page.props.auth.user.is_vehikl_member" :href="route('statistics.index')" :class="navigationClass('statistics.index')">
                    Statistics
                </Link>
                <Link :href="route('about')" :class="navigationClass('about')">About</Link>

                <span class="mx-1 h-6 w-px bg-white/15 sm:mx-2"></span>

                <div ref="userMenu" class="relative">
                    <button
                        type="button"
                        class="transition-smooth flex cursor-pointer items-center rounded-full ring-white/60 hover:opacity-90 focus:outline-none focus-visible:ring-2"
                        :aria-expanded="userMenuOpen"
                        aria-haspopup="menu"
                        @click="userMenuOpen = !userMenuOpen"
                    >
                        <span class="sr-only">Open user menu</span>
                        <v-avatar class="mr-0" :src="$page.props.auth.user.avatar" :alt="$page.props.auth.user.name" :size="6" />
                    </button>

                    <Transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="userMenuOpen"
                            class="gs-card gs-border absolute right-0 z-20 mt-2 w-56 origin-top-right rounded-xl border py-1 shadow-lg"
                            role="menu"
                        >
                            <div class="gs-border border-b px-4 py-3">
                                <p class="gs-text-muted text-xs">Signed in as</p>
                                <p class="gs-text-strong mt-0.5 truncate text-sm font-semibold">
                                    {{ $page.props.auth.user.email ?? $page.props.auth.user.name }}
                                </p>
                            </div>
                            <button
                                type="button"
                                role="menuitem"
                                class="gs-text-body transition-smooth flex w-full cursor-pointer items-center justify-between px-4 py-2.5 text-left text-sm hover:bg-black/5 dark:hover:bg-white/5"
                                popovertarget="keyboard-shortcuts-popover"
                                @click="userMenuOpen = false"
                            >
                                <span class="flex items-center gap-2">
                                    <i class="fa fa-keyboard-o text-base" aria-hidden="true"></i>
                                    Keyboard shortcuts
                                </span>
                                <kbd class="gs-text-muted text-xs">?</kbd>
                            </button>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                role="menuitem"
                                class="gs-text-body transition-smooth flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm hover:bg-black/5 dark:hover:bg-white/5"
                                @click="userMenuOpen = false"
                            >
                                <i class="fa fa-sign-out text-base" aria-hidden="true"></i>
                                Logout
                            </Link>
                        </div>
                    </Transition>
                </div>
            </template>

            <button
                type="button"
                :aria-label="isDark ? 'Switch to light theme' : 'Switch to dark theme'"
                :title="`${isDark ? 'Switch to light theme' : 'Switch to dark theme'} (T)`"
                class="transition-smooth flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-white/75 hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                @click="toggleTheme"
            >
                <svg
                    v-if="isDark"
                    class="h-[18px] w-[18px]"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <circle cx="12" cy="12" r="3.5" />
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42" />
                </svg>
                <svg
                    v-else
                    class="h-[18px] w-[18px]"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M20.5 14.1A8.5 8.5 0 0 1 9.9 3.5 8.5 8.5 0 1 0 20.5 14.1Z" />
                </svg>
            </button>
        </div>

        <div ref="mobileMenu" class="relative flex items-center gap-3 lg:hidden">
            <v-avatar v-if="$page.props.auth.user" class="mr-0" :src="$page.props.auth.user.avatar" :alt="$page.props.auth.user.name" :size="7" />
            <button
                type="button"
                class="transition-smooth flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg text-white/75 hover:bg-white/10 hover:text-white"
                :aria-expanded="mobileMenuOpen"
                aria-controls="mobile-navigation"
                aria-label="Toggle navigation"
                @click="mobileMenuOpen = !mobileMenuOpen"
            >
                <svg
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    aria-hidden="true"
                >
                    <path v-if="mobileMenuOpen" d="M6 6l12 12M18 6 6 18" />
                    <path v-else d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>

            <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="scale-95 opacity-0"
                enter-to-class="scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="scale-100 opacity-100"
                leave-to-class="scale-95 opacity-0"
            >
                <div
                    v-if="mobileMenuOpen"
                    id="mobile-navigation"
                    class="gs-card gs-border absolute top-12 right-0 z-40 w-64 origin-top-right rounded-xl border p-2 shadow-xl"
                >
                    <nav v-if="$page.props.auth.user" class="space-y-1" aria-label="Mobile navigation">
                        <Link
                            :href="route('dashboard')"
                            class="gs-text-body block rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-black/5 dark:hover:bg-white/5"
                            @click="mobileMenuOpen = false"
                        >
                            Dashboard
                        </Link>
                        <Link
                            :href="route('home')"
                            class="gs-text-body block rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-black/5 dark:hover:bg-white/5"
                            @click="mobileMenuOpen = false"
                        >
                            Board
                        </Link>
                        <Link
                            v-if="$page.props.auth.user.is_vehikl_member"
                            :href="route('statistics.index')"
                            class="gs-text-body block rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-black/5 dark:hover:bg-white/5"
                            @click="mobileMenuOpen = false"
                        >
                            Statistics
                        </Link>
                        <Link
                            :href="route('about')"
                            class="gs-text-body block rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-black/5 dark:hover:bg-white/5"
                            @click="mobileMenuOpen = false"
                        >
                            About
                        </Link>
                    </nav>

                    <div v-else class="space-y-1">
                        <a
                            :href="loginUrl()"
                            class="gs-text-body flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-semibold hover:bg-black/5 dark:hover:bg-white/5"
                        >
                            <i class="fa fa-github" aria-hidden="true"></i>
                            Login with GitHub
                        </a>
                    </div>

                    <div class="gs-border mt-2 space-y-1 border-t pt-2">
                        <button
                            type="button"
                            class="gs-text-body flex w-full cursor-pointer items-center justify-between rounded-lg px-3 py-2.5 text-left text-sm font-semibold hover:bg-black/5 dark:hover:bg-white/5"
                            popovertarget="keyboard-shortcuts-popover"
                            @click="mobileMenuOpen = false"
                        >
                            Keyboard shortcuts
                            <kbd class="gs-text-muted text-xs">?</kbd>
                        </button>
                        <button
                            type="button"
                            class="gs-text-body flex w-full cursor-pointer items-center justify-between rounded-lg px-3 py-2.5 text-left text-sm font-semibold hover:bg-black/5 dark:hover:bg-white/5"
                            @click="
                                toggleTheme();
                                mobileMenuOpen = false;
                            "
                        >
                            {{ isDark ? 'Light theme' : 'Dark theme' }}
                            <kbd class="gs-text-muted text-xs">T</kbd>
                        </button>
                    </div>
                    <div v-if="$page.props.auth.user" class="gs-border mt-2 space-y-1 border-t pt-2">
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="gs-text-body flex w-full cursor-pointer items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm font-semibold hover:bg-black/5 dark:hover:bg-white/5"
                            @click="mobileMenuOpen = false"
                        >
                            <i class="fa fa-sign-out" aria-hidden="true"></i>
                            Logout
                        </Link>
                    </div>
                </div>
            </Transition>
        </div>
    </header>

    <section
        id="keyboard-shortcuts-popover"
        ref="shortcutsPopover"
        popover="auto"
        class="gs-card gs-border fixed top-16 right-5 left-auto m-0 w-[calc(100vw-2.5rem)] max-w-sm rounded-2xl border p-5 shadow-2xl backdrop:bg-transparent"
        aria-labelledby="keyboard-shortcuts-title"
    >
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 id="keyboard-shortcuts-title" class="gs-text-strong text-lg font-bold">Keyboard shortcuts</h2>
                <p class="gs-text-muted mt-1 text-sm">Move around Growth Sessions faster.</p>
            </div>
            <button
                type="button"
                class="gs-text-muted transition-smooth flex h-8 w-8 cursor-pointer items-center justify-center rounded-full hover:bg-black/5 hover:text-current dark:hover:bg-white/10"
                aria-label="Close keyboard shortcuts"
                popovertarget="keyboard-shortcuts-popover"
                popovertargetaction="hide"
            >
                <span class="text-xl leading-none" aria-hidden="true">×</span>
            </button>
        </div>

        <dl class="space-y-2">
            <div v-for="shortcut in shortcuts" :key="shortcut.name" class="gs-secondary-bg flex items-center justify-between rounded-xl px-4 py-3">
                <div>
                    <dt class="gs-text-strong text-sm font-semibold">{{ shortcut.name }}</dt>
                    <dd v-if="shortcut.caption" class="gs-text-muted text-xs">{{ shortcut.caption }}</dd>
                </div>
                <kbd class="gs-card gs-border gs-text-strong min-w-8 rounded-md border px-2 py-1 text-center text-xs font-bold shadow-sm">
                    {{ shortcut.shortcut }}
                </kbd>
            </div>
        </dl>
    </section>

    <slot />
</template>

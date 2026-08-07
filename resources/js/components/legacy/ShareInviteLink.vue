<script lang="ts" setup>
import { Check, Copy } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';

// Rendered only when the payload carries a share URL. The server decides who that is — the owner and Vehikl members,
// the people who might hand the invitation out — so this component needs no viewer check of its own.
const props = defineProps<{ shareUrl: string }>();

const copied = ref(false);
let copiedReset: ReturnType<typeof setTimeout> | undefined;

async function copyShareUrl() {
    if (!(await writeToClipboard(props.shareUrl))) {
        alert('Could not copy the link. Select it and copy it manually.');
        return;
    }

    copied.value = true;
    clearTimeout(copiedReset);
    copiedReset = setTimeout(() => (copied.value = false), 2000);
}

async function writeToClipboard(text: string): Promise<boolean> {
    try {
        // Undefined outside a secure context — local development is served over plain HTTP — and refusable besides.
        await navigator.clipboard.writeText(text);
        return true;
    } catch {
        return copyViaHiddenTextarea(text);
    }
}

function copyViaHiddenTextarea(text: string): boolean {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    try {
        return document.execCommand('copy');
    } catch {
        return false;
    } finally {
        textarea.remove();
    }
}

onUnmounted(() => clearTimeout(copiedReset));
</script>

<template>
    <div>
        <div class="gs-border flex items-stretch overflow-hidden rounded-lg border">
            <span class="share-url gs-text-strong min-w-0 flex-1 truncate px-3.5 py-2.5 font-mono text-sm" :title="shareUrl">{{ shareUrl }}</span>
            <button
                type="button"
                class="copy-share-url-button gs-border gs-accent-text transition-smooth flex flex-none cursor-pointer items-center gap-1.5 border-l px-4 text-sm font-bold hover:bg-black/5 dark:hover:bg-white/8"
                @click="copyShareUrl"
            >
                <component :is="copied ? Check : Copy" :size="16" :stroke-width="2" aria-hidden="true" />
                {{ copied ? 'Copied' : 'Copy' }}
            </button>
        </div>
        <p class="gs-text-muted mt-2 text-sm leading-[1.5]">Anyone with this link can view this growth session and join after logging in.</p>
    </div>
</template>

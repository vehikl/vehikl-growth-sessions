<script lang="ts" setup>
import { GrowthSession } from '@/classes/GrowthSession';
import { User } from '@/classes/User';
import VAvatar from '@/components/legacy/VAvatar.vue';
import { parseCommentContent } from '@/lib/commentContent';
import { IComment, IUser } from '@/types';
import { computed, ref } from 'vue';

interface IProps {
    growthSession: GrowthSession;
    user?: IUser | null;
}

const props = defineProps<IProps>();
const newComment = ref('');
const brokenImageUrls = ref(new Set<string>());
const allowsNewCommentSubmission = computed<boolean>(() => !!props.user && !!newComment.value);
const commentFormPlaceholder = computed<string>(() => (props.user ? 'Leave a comment...' : 'You must be logged in to comment...'));

async function createNewComment() {
    await props.growthSession.postComment(newComment.value);
    newComment.value = '';
}

function getGithubURL(comment: IComment): string {
    return new User(comment.user).githubURL;
}

/** Compact the backend's "1 hour ago" style timestamp into "1h ago". */
function shortTimestamp(timeStamp: string): string {
    if (!timeStamp) return '';
    return timeStamp
        .replace(/\ban?\b/, '1')
        .replace(/\s*seconds?/, 's')
        .replace(/\s*minutes?/, 'm')
        .replace(/\s*hours?/, 'h')
        .replace(/\s*days?/, 'd')
        .replace(/\s*weeks?/, 'w')
        .replace(/\s*months?/, 'mo')
        .replace(/\s*years?/, 'y');
}
</script>

<template>
    <div>
        <h2 class="gs-text-muted mb-3 text-xs font-bold tracking-[0.06em] uppercase">Comments ({{ growthSession.comments.length }})</h2>
        <ul class="mb-4 flex flex-col gap-4">
            <li v-for="comment in growthSession.comments" :key="comment.id" class="flex gap-3">
                <a :href="getGithubURL(comment)" aria-label="visit-their-github" class="flex-none">
                    <v-avatar :src="comment.user.avatar" :size="6" :alt="`${comment.user.name}'s avatar`" />
                </a>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <i v-if="growthSession.isOwner(comment.user)" class="fa fa-star gs-accent-text text-xs" aria-hidden="true" />
                        <h3 class="gs-text-strong min-w-0 truncate text-sm font-semibold" v-text="comment.user.name" />
                        <span class="gs-text-muted flex-none text-xs" v-text="shortTimestamp(comment.time_stamp)"></span>
                        <button
                            v-show="growthSession.canDeleteComment(user, comment)"
                            class="transition-smooth ml-auto flex-none cursor-pointer text-red-500 hover:text-red-600"
                            aria-label="delete-comment"
                            @click="growthSession.deleteComment(comment)"
                        >
                            <i class="fa fa-minus-circle" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p class="gs-text-body mt-1 text-sm leading-relaxed break-words whitespace-pre-wrap">
                        <template v-for="(segment, index) in parseCommentContent(comment.content)" :key="index">
                            <img
                                v-if="segment.type === 'image' && !brokenImageUrls.has(segment.value)"
                                :src="segment.value"
                                alt="Shared image"
                                referrerpolicy="no-referrer"
                                loading="lazy"
                                class="my-1 block max-h-64 max-w-full rounded-md"
                                @error="brokenImageUrls.add(segment.value)"
                            />
                            <template v-else>{{ segment.value }}</template>
                        </template>
                    </p>
                </div>
            </li>
        </ul>
        <form class="mb-2" @submit.prevent="createNewComment">
            <label class="sr-only" for="new-comment">Comment:</label>
            <textarea
                id="new-comment"
                v-model="newComment"
                class="gs-input w-full resize-none rounded-lg px-3 py-2.5 text-sm"
                :disabled="!user"
                :placeholder="commentFormPlaceholder"
                rows="3"
            ></textarea>
            <button
                id="submit-new-comment"
                class="gs-btn-secondary mt-2 w-full rounded-md py-2.5 text-sm font-semibold"
                :class="{
                    'cursor-pointer': allowsNewCommentSubmission,
                    'cursor-not-allowed opacity-50': !allowsNewCommentSubmission,
                }"
                :disabled="!allowsNewCommentSubmission"
            >
                <i class="fa fa-commenting-o mr-2" aria-hidden="true"></i> Submit
            </button>
        </form>
    </div>
</template>

<script lang="ts" setup>
import {GrowthSession} from "../classes/GrowthSession"
import {User} from "../classes/User"
import VAvatar from "./VAvatar.vue"
import {IComment, IUser} from "../types"
import {computed, ref} from "vue"

interface IProps {
    growthSession: GrowthSession,
    user?: IUser;
}

const props = defineProps<IProps>()
const newComment = ref("")
const allowsNewCommentSubmission = computed<boolean>(() => !!props.user && !!newComment.value)
const commentFormPlaceholder = computed<string>(() => props.user
    ? "Leave a comment..."
    : "You must be logged in to comment..."
)

async function createNewComment() {
    await props.growthSession.postComment(newComment.value)
    newComment.value = ""
}

function getGithubURL(comment: IComment): string {
    return new User(comment.user).githubURL
}
</script>

<template>
    <div>
        <h2 class="text-lg font-bold tracking-wide mb-4 text-slate-500">Comments</h2>
        <ul>
            <li v-for="comment in growthSession.comments" :key="comment.id" class="py-4 border-b flex">
                <a :href="getGithubURL(comment)" aria-label="visit-their-github" class="mr-4">
                    <v-avatar :src="comment.user.avatar"
                              :size="16"
                              :alt="`${comment.user.name}'s avatar`"/>
                </a>
                <div>
                    <div class="flex items-center">
                        <i class="fa fa-star text-orange-500 mr-2"
                           v-if="growthSession.isOwner(comment.user)"
                           aria-hidden="true"/>
                        <h3 class="flex-1 font-bold tracking-wider uppercase text-slate-600" v-text="comment.user.name"/>
                        <button v-show="growthSession.canDeleteComment(user, comment)"
                                class="text-red-600 hover:text-red-800"
                                @click="growthSession.deleteComment(comment)">
                            <i class="fa fa-minus-circle" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="text-gray-700 text-sm mb-2" v-text="comment.time_stamp"></div>
                  <pre
                      class="inline-block text-left break-words-fixed whitespace-pre-wrap max-h-64 overflow-y-auto overflow-x-hidden font-sans text-slate-600 tracking-wide leading-relaxed">{{
                            comment.content
                        }}</pre>
                </div>
            </li>
        </ul>
        <form @submit.prevent="createNewComment" class="mb-6">
                <label class="sr-only" for="new-comment">
                    Comment:
                </label>
                <textarea
                    class="shadow resize-none appearance-none border w-full py-2 px-3 text-slate-700 leading-tight focus:outline-none focus:shadow-outline"
                    id="new-comment"
                    v-model="newComment"
                    :disabled="!user"
                    :placeholder="commentFormPlaceholder"
                    rows="3"></textarea>
                <button
                    class="w-full hover:bg-gray-700 bg-white text-gray-700 hover:text-white border-4 border-gray-700 font-bold tracking-wider py-1 px-4 focus:outline-none focus:shadow-outline"
                    id="submit-new-comment"
                    :class="{ 'opacity-75 cursor-not-allowed': !allowsNewCommentSubmission }"
                    :disabled="!allowsNewCommentSubmission">
                    <i class="fa fa-commenting-o mr-2 -ml-6" aria-hidden="true"></i> Submit
                </button>
            </form>
    </div>
</template>

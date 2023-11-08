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
        <form @submit.prevent="createNewComment">
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
                class="w-full hover:bg-blue-700 bg-white text-blue-700 hover:text-white border-4 border-blue-700 font-bold tracking-wider py-1 px-4 focus:outline-none focus:shadow-outline"
                id="submit-new-comment"
                :class="{'opacity-75 cursor-not-allowed' : !allowsNewCommentSubmission}"
                :disabled="! allowsNewCommentSubmission">
                <i class="fa fa-commenting-o mr-2 -ml-6" aria-hidden="true"></i> Submit
            </button>
        </form>


        <ul class="mt-6">
            <li v-for="comment in growthSession.comments" :key="comment.id" class="py-4 border-b">
                <a :href="getGithubURL(comment)" aria-label="visit-their-github">
                    <v-avatar :src="comment.user.avatar"
                              class="mr-4"
                              :size="16"
                              :alt="`${comment.user.name}'s avatar`"/>
                </a>
                <div class="mx-6">
                    <div class="flex items-center">
                        <i class="fa fa-star text-amber-500 mr-2"
                           v-if="growthSession.isOwner(comment.user)"
                           aria-hidden="true"/>
                        <h3 class="font-bold flex-1" v-text="comment.user.name"/>
                        <button v-show="growthSession.canDeleteComment(user, comment)"
                                class="text-red-600 hover:text-red-800"
                                @click="growthSession.deleteComment(comment)">
                            <i class="fa fa-minus-circle" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="text-blue-400 text-sm" v-text="comment.time_stamp"></div>
                    <pre class="mx-4 mt-3 font-sans m-5 break-words-fixed whitespace-pre-wrap max-h-48 overflow-y-auto">{{
                            comment.content
                        }}</pre>
                </div>
            </li>
        </ul>
    </div>
</template>

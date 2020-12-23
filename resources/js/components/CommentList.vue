<template>
    <div>
        <form @submit.prevent="createNewComment" class="flex">
            <label class="sr-only" for="new-comment">
                Comment:
            </label>
            <textarea
                class="shadow resize-none appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                id="new-comment"
                v-model="newComment"
                :disabled="!user"
                :placeholder="commentFormPlaceholder"
                rows="3"></textarea>
            <button
                class="bg-blue-500 hover:bg-blue-700 text-white text-xl font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                id="submit-new-comment"
                :class="{'opacity-25 cursor-not-allowed' : !allowsNewCommentSubmission}"
                :disabled="! allowsNewCommentSubmission">
                <i class="fa fa-commenting-o" aria-hidden="true"></i>
            </button>
        </form>


        <ul class="mt-6">
            <li v-for="comment in growthSession.comments" :key="comment.id" class="flex py-4 border-b border-blue-200">
                <a ref="commenter-avatar-link" :href="getGithubURL(comment)">
                    <v-avatar :src="comment.user.avatar"
                              class="mr-4"
                              :alt="`${comment.user.name}'s avatar`"/>
                </a>
                <div class="flex-1 mx-6">
                    <div class="flex items-center">
                        <i class="fa fa-star text-orange-500 mr-2"
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
                    <pre class="mx-4 mt-3 font-sans m-5 break-words-fixed whitespace-pre-wrap max-h-48 overflow-y-auto">{{comment.content}}</pre>
                </div>
            </li>
        </ul>
    </div>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {GrowthSession} from '../classes/GrowthSession';
    import {User} from '../classes/User';
    import VAvatar from './VAvatar.vue';
    import {IComment, IUser} from '../types';

    @Component({
        components: {VAvatar}
    })
    export default class CommentList extends Vue {
        @Prop({required: true}) growthSession!: GrowthSession;
        @Prop({required: false}) user!: IUser;
        newComment: string = '';

        async createNewComment() {
            await this.growthSession.postComment(this.newComment);
            this.newComment = '';
        }

        get allowsNewCommentSubmission(): boolean {
            return !!this.user && !!this.newComment;
        }

        get commentFormPlaceholder(): string {
            return this.user ? 'Leave a comment...' : 'You must be logged in to comment...';
        }

        getGithubURL(comment: IComment): string {
            return new User(comment.user).githubURL;
        }
    }
</script>

<style lang="scss" scoped>
</style>

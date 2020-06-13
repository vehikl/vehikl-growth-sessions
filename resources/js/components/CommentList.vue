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
                placeholder="Leave a comment..."
                rows="3"></textarea>
            <button
                class="bg-blue-500 hover:bg-blue-700 text-white text-xl font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                <i class="fa fa-commenting-o" aria-hidden="true"></i>
            </button>
        </form>


        <ul class="mt-6">
            <li v-for="comment in socialMob.comments" :key="comment.id" class="flex py-4 border-b border-blue-200">
                <v-avatar :image-src="comment.user.avatar"
                          class="mr-4"
                          :image-alt="`${comment.user.name}'s avatar`"/>
                <div class="flex-1 mx-6">
                    <div class="flex items-center">
                        <i class="fa fa-star text-orange-500 mr-2"
                           v-if="socialMob.isOwner(comment.user)"
                           aria-hidden="true"/>
                        <h3 class="font-bold flex-1" v-text="comment.user.name"/>
                        <button v-show="socialMob.canDeleteComment(user, comment)"
                                class="text-red-600 hover:text-red-800"
                                @click="socialMob.deleteComment(comment)">
                            <i class="fa fa-minus-circle" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="text-blue-400 text-sm" v-text="comment.time_stamp"></div>
                    <pre class="mx-4 mt-3 font-sans m-5 whitespace-pre-wrap">{{comment.content}}</pre>
                </div>
            </li>
        </ul>
    </div>
</template>

<script lang="ts">
    import {Component, Prop, Vue} from 'vue-property-decorator';
    import {SocialMob} from '../classes/SocialMob';
    import VAvatar from './VAvatar.vue';
    import {IUser} from '../types';

    @Component({
        components: {VAvatar}
    })
    export default class CommentList extends Vue {
        @Prop({required: true}) socialMob!: SocialMob;
        @Prop({required: false}) user!: IUser;
        newComment: string = '';

        async createNewComment() {
            await this.socialMob.postComment(this.newComment);
            this.newComment = '';
        }
    }
</script>

<style lang="scss" scoped>
</style>

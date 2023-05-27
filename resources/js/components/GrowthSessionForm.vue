<template>
    <form @submit.prevent class="create-growth-session edit-growth-session-form bg-white w-full p-4 text-left">
        <div class="mb-4 flex justify-between">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="date">
                    Date
                </label>
                <div :class="{'error-outline': getError('date')}"
                     class="border p-1 border-gray-400 flex justify-center">
                    <input id="date" v-model="date" type="date">
                </div>
            </div>

            <button
                :class="{'opacity-25 cursor-not-allowed': !isReadyToSubmit}"
                :disabled="! isReadyToSubmit"
                @click="onSubmit"
                class="mt-6 w-48 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                tabindex="6"
                type="submit"
                ref="submit-button"
                v-text="isCreating? 'Create' : 'Update'">
            </button>
        </div>

        <div class="mb-4 flex">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="start_time">
                    Start
                </label>
                <time-picker id="start-time" v-model="startTime" :class="{'error-outline': getError('start_time')}"/>
            </div>
            <div class="ml-12">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="end_time">
                    End
                </label>
                <time-picker id="end-time" v-model="endTime" :class="{'error-outline': getError('end_time')}"/>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                Title
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                   :class="{'error-outline': getError('title')}"
                   id="title"
                   maxlength="45"
                   placeholder="In a short sentence, what is this growth session about?"
                   tabindex="4"
                   type="text"
                   v-model="title"/>
        </div>

        <div class="mb-4 mt-6 flex items-center justify-between">
            <label class="block text-gray-700 text-sm font-bold mb-2">
                <input ref="is-public" v-model="isPublic" type="checkbox"> Is Public
            </label>

            <label class="block text-gray-700 text-sm font-bold mb-2">
                <input ref="no-limit" v-model="isLimitless" type="checkbox"> No Limit
            </label>

            <div v-if="!isLimitless" class="flex items-center">
                <label class="block text-gray-700 text-sm font-bold mr-4" for="limit">
                    Limit
                </label>
                <input
                    id="limit"
                    ref="attendee-limit"
                    v-model.number="attendeeLimit"
                    :class="{'error-outline': getError('limit')}"
                    class="w-24 text-center shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    min="4"
                    placeholder="Limit of participants"
                    tabindex="4"
                    type="number"/>
            </div>
        </div>

        <label class="block text-gray-700 text-sm font-bold mb-2">
            <input ref="allow-watchers" v-model="allowWatchers" type="checkbox"> Allow watchers
        </label>

        <div class="flex" v-if="anyDesks.length > 0">
            <div class="flex-1">
                <label class="block text-gray-700 text-sm font-bold mb-4">
                    <input ref="anydesks-toggle" v-model="anydesksToggle" type="checkbox"> Plan to use an AnyDesk?
                </label>
            </div>
            <div id="anydesks" class="flex-1" v-if="anydesksToggle">
                <v-select id="anydesk"
                          ref="anydesk"
                          name="anydesk"
                          max-height="100px"
                          :options="anyDesks"
                          v-model="anyDesk"
                          type="search"
                ></v-select>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="topic">
                Topic
            </label>
            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                      :class="{'error-outline': getError('topic')}"
                      id="topic"
                      placeholder="Do you want to provide more details about this growth session?"
                      rows="4"
                      tabindex="5"
                      v-model="topic"/>
        </div>

        <div class="mb-4" v-if="(discordChannels.length > 0)">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="discord_channel">
                Discord Channel
            </label>
            <v-select id="discord_channel"
                      ref="discord_channel"
                      name="discord_channel"
                      :options="discordChannels"
                      v-model="discordChannel"></v-select>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="location">
                Location
            </label>
            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                      :class="{'error-outline': getError('location')}"
                      id="location"
                      placeholder="Where should people go to participate?"
                      rows="2"
                      tabindex="6"
                      v-model="location"/>
        </div>
    </form>
</template>

<script lang="ts">
import {Component, Prop, Vue, Watch} from "vue-property-decorator"
import {IGrowthSession, IStoreGrowthSessionRequest, IUser, IValidationError} from "../types"
import {GrowthSessionApi} from "../services/GrowthSessionApi"
import {DateTime} from "../classes/DateTime"
import {DiscordChannelApi} from "../services/DiscordChannelApi"
import {IDropdownOption} from "../types/IDropdownOption"
import {AnydesksApi} from "../services/AnydesksApi"
import TimePicker from "./TimePicker.vue"

@Component({components: {TimePicker}})
export default class GrowthSessionForm extends Vue {
    @Prop({required: true}) owner!: IUser;
    @Prop({required: false, default: null}) growthSession!: IGrowthSession;
    @Prop({required: false, default: ''}) startDate!: string;

    startTime: string = '03:30 pm';
    endTime: string = '05:00 pm';
    location: string = '';
    title: string = '';
    attendeeLimit: number = 4;
    topic: string = '';
    date: string = '';
    isPublic: boolean = false;
    validationErrors: IValidationError | null = null;
    isLimitless: boolean = false;
    allowWatchers: boolean = true;
    discordChannel: IDropdownOption = {value: '', label: ''};
    discordChannels: Array<Object> = [];
    anyDesk: { label: string | undefined; value: number | undefined } | null = null;
    anyDesks: Array<Object> = [];
    anydesksToggle: boolean = !!this.growthSession?.anydesk
    foobar: string = ""
    mounted() {
        this.date = this.startDate;
        let input = document.getElementById('title');
        if (input) {
            input.focus();
        }

        this.getDiscordChannels();

        this.getAnyDesks();

        if (this.growthSession) {
            this.date = this.growthSession.date;
            this.startTime = DateTime.parseByTime(this.growthSession.start_time).toTimeString12Hours();
            this.endTime = DateTime.parseByTime(this.growthSession.end_time).toTimeString12Hours();
            this.location = this.growthSession.location;
            this.title = this.growthSession.title;
            this.topic = this.growthSession.topic;
            this.isLimitless = ! this.growthSession.attendee_limit;
            this.attendeeLimit = this.growthSession.attendee_limit || 4;
            this.isPublic = this.growthSession.is_public;
            this.anyDesk = this.growthSession.anydesk ? {value: this.growthSession.anydesk?.id, label: this.growthSession.anydesk?.name} : null;
            this.allowWatchers = this.growthSession.allow_watchers;
        }
    }

    onSubmit() {
        if (this.isCreating) {
            return this.createGrowthSession();
        }
        this.updateGrowthSession();
    }

    onRequestFailed(exception: any) {
        if (exception.response?.status === 422) {
            this.validationErrors = exception.response.data;
        } else {
            alert('Something went wrong :(');
        }
    }

    getError(field: string): string {
        let errors = this.validationErrors?.errors[field];
        return errors ? errors[0] : '';
    }

    async createGrowthSession() {
        try {
            let growthSession: IGrowthSession = await GrowthSessionApi.store(this.storeOrUpdatePayload);
            this.$emit('submitted', growthSession);
        } catch (e) {
            this.onRequestFailed(e);
        }
    }

    async updateGrowthSession() {
        try {
            let growthSession: IGrowthSession = await GrowthSessionApi.update(this.growthSession, this.storeOrUpdatePayload);
            this.$emit('submitted', growthSession);
        } catch (e) {
            this.onRequestFailed(e);
        }
    }

    async getDiscordChannels() {
        try {
            const discordChannels = await DiscordChannelApi.index();
            this.discordChannels = discordChannels.map(discordChannel => {
                return {
                    label: discordChannel.name,
                    value: discordChannel.id
                };
            });
        } catch (e) {
            this.onRequestFailed(e);
        }
    }

    async getAnyDesks() {
        try {
            const anyDesks = await AnydesksApi.getAllAnyDesks();
            this.anyDesks = anyDesks.map(anyDesk => {
                return {
                    label: anyDesk.name,
                    value: anyDesk.id
                };
            });
        } catch (e) {
            this.onRequestFailed(e);
        }
    }

    get isCreating(): boolean {
        return !this.growthSession?.id;
    }

    get isReadyToSubmit(): boolean {
        return !!this.startTime && !!this.endTime && !!this.date && !!this.location && !!this.topic && !!this.title;
    }

    get storeOrUpdatePayload(): IStoreGrowthSessionRequest {
        return {
            location: this.location,
            topic: this.topic,
            title: this.title,
            date: this.date,
            start_time: this.startTime,
            end_time: this.endTime,
            is_public: this.isPublic,
            attendee_limit: this.isLimitless ? undefined : this.attendeeLimit,
            discord_channel_id: this.discordChannel.value || undefined,
            anydesk_id: this.anyDesk?.value || undefined,
            allow_watchers: this.allowWatchers,
        }
    }

    @Watch('discordChannel')
    onDiscordChannelChanged(value: IDropdownOption) {
        if(!this.location || this.location.startsWith('Discord Channel: ')) {
            this.location = `Discord Channel: ${value.label}`
        }
    }

    @Watch('anydesksToggle')
    onAnydesksToggleChanged() {
        this.anyDesk = null;
    }
}
</script>

<style lang="scss" scoped>
.error-outline {
    outline: red solid 2px;
}
</style>

<style>
#anydesk .vs__dropdown-menu {
    max-height: 150px;
}
</style>

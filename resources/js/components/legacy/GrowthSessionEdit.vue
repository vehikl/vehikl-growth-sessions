<script lang="ts" setup>
import { GrowthSession } from '@/classes/GrowthSession';
import VAvatar from '@/components/legacy/VAvatar.vue';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { IGrowthSession, IUser, IValidationError } from '@/types';
import { ref } from 'vue';

interface IProps {
    user?: IUser;
    growthSessionJson: IGrowthSession;
}

const props = defineProps<IProps>();
const emit = defineEmits(['submitted']);

const growthSession = ref<GrowthSession>(new GrowthSession(props.growthSessionJson));
const validationErrors = ref<IValidationError | null>(null);

async function deleteGrowthSession() {
    await growthSession.value.delete();
    window.location.assign('/');
}

async function updateGrowthSession() {
    try {
        const payload = { ...growthSession.value };
        if (growthSession.value.isLimitless) {
            payload.attendee_limit = null;
        }
        const updatedGrowthSession: IGrowthSession = await GrowthSessionApi.update(growthSession.value, payload);
        emit('submitted', updatedGrowthSession);
        window.history.back();
    } catch (e) {
        onRequestFailed(e);
    }
}

function onRequestFailed(exception: any) {
    if (exception.response?.status === 422) {
        console.log(exception.response.data.errors);
        validationErrors.value = exception.response.data;
    } else {
        alert('Something went wrong :(');
    }
}

function getError(field: string): string {
    const errors = validationErrors.value?.errors[field];
    return errors ? errors[0] : '';
}
</script>

<template>
    <div class="max-w-5xl text-gray-600">
        <div class="mb-8 flex flex-col items-center lg:flex-row lg:justify-between">
            <h2 class="flex flex-1 items-center pr-6 font-sans text-2xl font-light text-gray-700 lg:text-3xl">
                <v-avatar :alt="`${growthSession.owner.name}'s Avatar`" :src="growthSession.owner.avatar" class="mr-4" />
                <input
                    class="w-full flex-1 appearance-none rounded border px-3 shadow"
                    placeholder="Please enter a growth session title"
                    type="text"
                    maxlength="45"
                    v-model="growthSession.title"
                />
            </h2>
            <div>
                <button
                    @click.stop="updateGrowthSession"
                    class="update-button bg-vehikl-orange hover:bg-vehikl-dark focus:bg-vehikl-dark w-32 cursor-pointer rounded px-4 py-2 font-bold text-white"
                >
                    Save
                </button>
                <button
                    @click.stop="deleteGrowthSession"
                    class="delete-button w-16 cursor-pointer rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700 focus:bg-red-700"
                >
                    <i aria-hidden="true" class="fa fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="flex flex-col flex-wrap lg:flex-row">
            <div class="mr-2 max-w-5xl flex-1">
                <h3 class="mb-3 font-sans text-2xl font-light text-gray-700">Topic</h3>
                <textarea
                    :class="{ 'error-outline': getError('topic') }"
                    class="focus:shadow-outline w-full appearance-none rounded border px-3 py-2 leading-tight text-slate-700 shadow focus:outline-none"
                    id="topic"
                    placeholder="What is this growth session about?"
                    rows="4"
                    v-model="growthSession.topic"
                />
            </div>
            <div class="max-w-md flex-none">
                <div class="mb-3">
                    <h3 class="mr-3 inline font-sans text-2xl font-light text-gray-700">Location:</h3>
                    <textarea
                        :class="{ 'error-outline': getError('location') }"
                        class="focus:shadow-outline w-full appearance-none rounded border px-3 py-2 leading-tight text-slate-700 shadow focus:outline-none"
                        id="location"
                        placeholder="Where should people go to participate?"
                        rows="2"
                        v-model="growthSession.location"
                    />
                </div>

                <div class="mb-3">
                    <h3 class="mr-3 inline font-sans text-2xl font-light text-gray-700">Time:</h3>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700" for="date"> Date </label>
                        <div :class="{ 'error-outline': getError('date') }" class="justify-center border border-slate-400 p-1">
                            <input id="date" v-model="growthSession.date" type="date" />
                        </div>

                        <label class="mb-2 block text-sm font-bold text-slate-700" for="start_time"> Start </label>
                        <vue-timepicker
                            :class="{ 'error-outline': getError('start_time') }"
                            :minute-interval="15"
                            advanced-keyboard
                            auto-scroll
                            format="hh:mm a"
                            hide-disabled-items
                            id="start_time"
                            v-model="growthSession.start_time"
                        />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700" for="end_time"> End </label>
                        <vue-timepicker
                            :class="{ 'error-outline': getError('end_time') }"
                            :minute-interval="15"
                            advanced-keyboard
                            auto-scroll
                            format="hh:mm a"
                            hide-disabled-items
                            id="end_time"
                            v-model="growthSession.end_time"
                        />
                    </div>
                </div>

                <h3 class="mb-3 font-sans text-2xl font-light text-gray-700">Attendees</h3>
                <ul>
                    <li v-for="attendee in growthSession.attendees" :key="attendee.id" class="my-4 ml-6 flex items-center">
                        <v-avatar :alt="`${attendee.name}'s Avatar`" :size="12" :src="attendee.avatar" class="mr-3" />
                        {{ attendee.name }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.error-outline {
    outline: red solid 2px;
}
</style>
